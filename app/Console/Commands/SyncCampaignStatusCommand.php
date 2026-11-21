<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignRecipientStatus;
use App\Enums\MessageStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Message;
use App\Models\MessageStatusEvent;
use App\Models\WebhookEvent;
use Illuminate\Console\Command;

class SyncCampaignStatusCommand extends Command
{
    protected $signature = 'whatsapp:sync-campaign-status {campaign? : The campaign ID to sync}';

    protected $description = 'Reconcile campaign and direct message statuses from status events and webhooks';

    public function handle(): int
    {
        $campaignId = $this->argument('campaign');

        $query = Campaign::query()->withoutGlobalScopes();
        if ($campaignId) {
            $query->where('id', $campaignId);
        }

        $campaigns = $query->get();

        $rank = fn (mixed $s) => match ($s instanceof CampaignRecipientStatus ? $s->value : (string) $s) {
            'sent' => 1,
            'delivered' => 2,
            'read' => 3,
            default => 0,
        };

        if ($campaigns->isNotEmpty()) {
            foreach ($campaigns as $campaign) {
                $this->info("Syncing campaign [{$campaign->id}] '{$campaign->name}' (Status: {$campaign->status->value})...");

                $recipients = CampaignRecipient::query()
                    ->withoutGlobalScopes()
                    ->where('campaign_id', $campaign->id)
                    ->get();

                $updatedCount = 0;

                foreach ($recipients as $recipient) {
                    $message = null;

                    if ($recipient->message_id) {
                        $message = Message::query()->withoutGlobalScopes()->find($recipient->message_id);
                    }

                    if (! $message) {
                        $message = Message::query()->withoutGlobalScopes()
                            ->where('campaign_recipient_id', $recipient->id)
                            ->first();
                    }

                    if (! $message) {
                        $cleanPhone = ltrim($recipient->phone_e164, '+');
                        $message = Message::query()->withoutGlobalScopes()
                            ->where('campaign_id', $campaign->id)
                            ->where(function ($q) use ($recipient, $cleanPhone) {
                                $q->where('to_phone', $recipient->phone_e164)
                                    ->orWhere('to_phone', $cleanPhone)
                                    ->orWhere('to_phone', '+'.$cleanPhone);
                            })
                            ->latest()
                            ->first();
                    }

                    if (! $message) {
                        continue;
                    }

                    $events = MessageStatusEvent::query()->withoutGlobalScopes()
                        ->where('message_id', $message->id)
                        ->pluck('status')
                        ->all();

                    $highestStatus = null;
                    $errorCode = $message->error_code;
                    $errorMessage = $message->error_message;

                    if (in_array('read', $events, true) || $message->status === MessageStatus::Read) {
                        $highestStatus = CampaignRecipientStatus::Read;
                    } elseif (in_array('delivered', $events, true) || $message->status === MessageStatus::Delivered) {
                        $highestStatus = CampaignRecipientStatus::Delivered;
                    } elseif (in_array('failed', $events, true) || $message->status === MessageStatus::Failed) {
                        $highestStatus = CampaignRecipientStatus::Failed;
                    } elseif (in_array('sent', $events, true) || $message->status === MessageStatus::Sent) {
                        $highestStatus = CampaignRecipientStatus::Sent;
                    }

                    if ($highestStatus !== null) {
                        $shouldUpdate = false;

                        if ($highestStatus === CampaignRecipientStatus::Failed) {
                            $shouldUpdate = ($recipient->status !== CampaignRecipientStatus::Failed);
                        } elseif ($rank($highestStatus) > $rank($recipient->status)) {
                            $shouldUpdate = true;
                        }

                        if ($recipient->message_id !== $message->id) {
                            $recipient->message_id = $message->id;
                            $shouldUpdate = true;
                        }

                        if ($shouldUpdate) {
                            $recipient->forceFill([
                                'status' => $highestStatus->value,
                                'message_id' => $message->id,
                                'error_code' => $errorCode ?? $recipient->error_code,
                                'error_message' => $errorMessage ?? $recipient->error_message,
                            ])->save();

                            $updatedCount++;
                        }
                    }
                }

                $totals = $campaign->recomputeTotals();
                $campaign->forceFill(['totals' => $totals])->save();

                $this->line("  Updated {$updatedCount} recipients.");
                $this->line("  New Totals: Total: {$totals['total']} | Sent: {$totals['sent']} | Delivered: {$totals['delivered']} | Read: {$totals['read']} | Failed: {$totals['failed']}");
            }
        }

        // Now sync direct/normal messages (and any message still marked 'sent')
        $this->info('Syncing direct & outbound message delivery statuses...');
        $outboundMessages = Message::query()
            ->withoutGlobalScopes()
            ->where('direction', 'outbound')
            ->whereIn('status', ['sent', 'processing', 'queued'])
            ->get();

        $directUpdated = 0;
        foreach ($outboundMessages as $msg) {
            // Check status events first
            $events = MessageStatusEvent::query()->withoutGlobalScopes()
                ->where('message_id', $msg->id)
                ->orderBy('occurred_at')
                ->get();

            $readEvent = $events->firstWhere('status', 'read');
            $deliveredEvent = $events->firstWhere('status', 'delivered');
            $failedEvent = $events->firstWhere('status', 'failed');

            if ($readEvent) {
                $msg->forceFill([
                    'status' => MessageStatus::Read,
                    'read_at' => $readEvent->occurred_at ?? now(),
                    'delivered_at' => $deliveredEvent?->occurred_at ?? $readEvent->occurred_at ?? now(),
                ])->save();
                $directUpdated++;
                continue;
            }

            if ($deliveredEvent) {
                $msg->forceFill([
                    'status' => MessageStatus::Delivered,
                    'delivered_at' => $deliveredEvent->occurred_at ?? now(),
                ])->save();
                $directUpdated++;
                continue;
            }

            if ($failedEvent) {
                $msg->forceFill([
                    'status' => MessageStatus::Failed,
                    'failed_at' => $failedEvent->occurred_at ?? now(),
                    'error_code' => $failedEvent->error_code ?? $msg->error_code,
                    'error_message' => $failedEvent->error_message ?? $msg->error_message,
                ])->save();
                $directUpdated++;
                continue;
            }

            // If not found in message_status_events, check webhook_events payload for this wamid or phone
            if ($msg->wamid) {
                $webhook = WebhookEvent::query()->withoutGlobalScopes()
                    ->where('payload', 'like', '%'.$msg->wamid.'%')
                    ->latest()
                    ->first();

                if ($webhook) {
                    $statuses = data_get($webhook->payload, 'entry.0.changes.0.value.statuses', []);
                    foreach ($statuses as $st) {
                        if (($st['id'] ?? '') === $msg->wamid) {
                            $stVal = $st['status'] ?? '';
                            $ts = isset($st['timestamp']) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $st['timestamp']) : now();

                            if ($stVal === 'read') {
                                $msg->forceFill(['status' => MessageStatus::Read, 'read_at' => $ts, 'delivered_at' => $ts])->save();
                                $directUpdated++;
                                break;
                            } elseif ($stVal === 'delivered') {
                                $msg->forceFill(['status' => MessageStatus::Delivered, 'delivered_at' => $ts])->save();
                                $directUpdated++;
                                break;
                            } elseif ($stVal === 'failed') {
                                $err = $st['errors'][0] ?? [];
                                $msg->forceFill([
                                    'status' => MessageStatus::Failed,
                                    'failed_at' => $ts,
                                    'error_code' => $err['code'] ?? null,
                                    'error_message' => $err['message'] ?? ($err['error_data']['details'] ?? null),
                                ])->save();
                                $directUpdated++;
                                break;
                            }
                        }
                    }
                }
            }
        }

        $this->line("  Updated {$directUpdated} direct/outbound messages to their latest status.");
        $this->info('Message & Campaign status sync complete.');

        return self::SUCCESS;
    }
}
