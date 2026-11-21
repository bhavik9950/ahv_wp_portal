<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignRecipientStatus;
use App\Enums\MessageStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Message;
use App\Models\MessageStatusEvent;
use App\Models\Organization;
use App\Models\WebhookEvent;
use App\Support\TenantContext;
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

                // Bind tenant context for this campaign
                if ($campaign->organization_id) {
                    $org = Organization::query()->withoutGlobalScopes()->find($campaign->organization_id);
                    if ($org) {
                        app(TenantContext::class)->set($org);
                    }
                }

                $recipients = CampaignRecipient::query()
                    ->withoutGlobalScopes()
                    ->where('campaign_id', $campaign->id)
                    ->get();

                $updatedCount = 0;

                foreach ($recipients as $recipient) {
                    $cleanPhone = ltrim($recipient->phone_e164, '+');
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
                        $message = Message::query()->withoutGlobalScopes()
                            ->where('campaign_id', $campaign->id)
                            ->where(function ($q) use ($recipient, $cleanPhone) {
                                $q->where('to_phone', $recipient->phone_e164)
                                    ->orWhere('to_phone', $cleanPhone)
                                    ->orWhere('to_phone', '+'.$cleanPhone);
                            })
                            ->latest('created_at')
                            ->first();
                    }

                    if (! $message) {
                        // Check if an outbound message exists anywhere for this campaign and phone
                        $message = Message::query()->withoutGlobalScopes()
                            ->where(function ($q) use ($recipient, $cleanPhone) {
                                $q->where('to_phone', $recipient->phone_e164)
                                    ->orWhere('to_phone', $cleanPhone)
                                    ->orWhere('to_phone', '+'.$cleanPhone);
                            })
                            ->where('direction', 'outbound')
                            ->latest('created_at')
                            ->first();
                    }

                    $events = [];
                    $errorCode = $message?->error_code;
                    $errorMessage = $message?->error_message;

                    if ($message) {
                        $events = MessageStatusEvent::query()->withoutGlobalScopes()
                            ->where('message_id', $message->id)
                            ->pluck('status')
                            ->all();
                    }

                    // Check webhook_events payloads directly as fallback
                    if (empty($events) || (! in_array('read', $events, true) && ! in_array('delivered', $events, true) && ! in_array('failed', $events, true))) {
                        $webhooks = WebhookEvent::query()->withoutGlobalScopes()
                            ->where(function ($q) use ($message, $cleanPhone) {
                                if ($message && $message->wamid) {
                                    $q->where('payload', 'like', '%'.$message->wamid.'%');
                                }
                                $q->orWhere('payload', 'like', '%'.$cleanPhone.'%');
                            })
                            ->latest('received_at')
                            ->limit(10)
                            ->get();

                        foreach ($webhooks as $wh) {
                            $statuses = data_get($wh->payload, 'entry.0.changes.0.value.statuses', []);
                            foreach ($statuses as $st) {
                                $stVal = $st['status'] ?? '';
                                if ($stVal) {
                                    $events[] = $stVal;
                                    if ($stVal === 'failed') {
                                        $err = $st['errors'][0] ?? [];
                                        $errorCode ??= $err['code'] ?? null;
                                        $errorMessage ??= $err['message'] ?? ($err['error_data']['details'] ?? null);
                                    }
                                }
                            }
                        }
                    }

                    $highestStatus = null;

                    if (in_array('read', $events, true) || ($message && $message->status === MessageStatus::Read)) {
                        $highestStatus = CampaignRecipientStatus::Read;
                    } elseif (in_array('delivered', $events, true) || ($message && $message->status === MessageStatus::Delivered)) {
                        $highestStatus = CampaignRecipientStatus::Delivered;
                    } elseif (in_array('failed', $events, true) || ($message && $message->status === MessageStatus::Failed)) {
                        $highestStatus = CampaignRecipientStatus::Failed;
                    } elseif (in_array('sent', $events, true) || ($message && $message->status === MessageStatus::Sent)) {
                        $highestStatus = CampaignRecipientStatus::Sent;
                    }

                    if ($highestStatus !== null) {
                        $shouldUpdate = false;

                        if ($highestStatus === CampaignRecipientStatus::Failed) {
                            $shouldUpdate = ($recipient->status !== CampaignRecipientStatus::Failed);
                        } elseif ($rank($highestStatus) > $rank($recipient->status)) {
                            $shouldUpdate = true;
                        }

                        if ($message && $recipient->message_id !== $message->id) {
                            $recipient->message_id = $message->id;
                            $shouldUpdate = true;
                        }

                        if ($shouldUpdate) {
                            $recipient->forceFill([
                                'status' => $highestStatus->value,
                                'message_id' => $message?->id ?? $recipient->message_id,
                                'error_code' => $errorCode ?? $recipient->error_code,
                                'error_message' => $errorMessage ?? $recipient->error_message,
                            ])->save();

                            // Also sync back to message if available
                            if ($message) {
                                $mappedMsgStatus = match ($highestStatus) {
                                    CampaignRecipientStatus::Read => MessageStatus::Read,
                                    CampaignRecipientStatus::Delivered => MessageStatus::Delivered,
                                    CampaignRecipientStatus::Failed => MessageStatus::Failed,
                                    default => MessageStatus::Sent,
                                };
                                $message->forceFill([
                                    'status' => $mappedMsgStatus,
                                    'campaign_id' => $campaign->id,
                                    'campaign_recipient_id' => $recipient->id,
                                ])->save();
                            }

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

            // Check webhook_events by wamid or phone
            $cleanPhone = ltrim($msg->to_phone, '+');
            $webhook = WebhookEvent::query()->withoutGlobalScopes()
                ->where(function ($q) use ($msg, $cleanPhone) {
                    if ($msg->wamid) {
                        $q->where('payload', 'like', '%'.$msg->wamid.'%');
                    }
                    $q->orWhere('payload', 'like', '%'.$cleanPhone.'%');
                })
                ->latest('received_at')
                ->first();

            if ($webhook) {
                $statuses = data_get($webhook->payload, 'entry.0.changes.0.value.statuses', []);
                foreach ($statuses as $st) {
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

        $this->line("  Updated {$directUpdated} direct/outbound messages to their latest status.");
        $this->info('Message & Campaign status sync complete.');

        return self::SUCCESS;
    }
}
