<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignRecipientStatus;
use App\Enums\MessageStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Message;
use App\Models\MessageStatusEvent;
use Illuminate\Console\Command;

class SyncCampaignStatusCommand extends Command
{
    protected $signature = 'whatsapp:sync-campaign-status {campaign? : The campaign ID to sync}';

    protected $description = 'Reconcile campaign recipient delivery statuses and campaign totals from messages and status events';

    public function handle(): int
    {
        $campaignId = $this->argument('campaign');

        $query = Campaign::query()->withoutGlobalScopes();
        if ($campaignId) {
            $query->where('id', $campaignId);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            $this->warn('No campaigns found to sync.');

            return self::SUCCESS;
        }

        $rank = fn (mixed $s) => match ($s instanceof CampaignRecipientStatus ? $s->value : (string) $s) {
            'sent' => 1,
            'delivered' => 2,
            'read' => 3,
            default => 0,
        };

        foreach ($campaigns as $campaign) {
            $this->info("Syncing campaign [{$campaign->id}] '{$campaign->name}' (Status: {$campaign->status->value})...");

            $recipients = CampaignRecipient::query()
                ->withoutGlobalScopes()
                ->where('campaign_id', $campaign->id)
                ->get();

            $updatedCount = 0;

            foreach ($recipients as $recipient) {
                // Find associated message
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
                    // Normalize phone: check both with/without +
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

                // Check message status events
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

            // Recompute and persist totals
            $totals = $campaign->recomputeTotals();
            $campaign->forceFill(['totals' => $totals])->save();

            $this->line("  Updated {$updatedCount} recipients.");
            $this->line("  New Totals: Total: {$totals['total']} | Sent: {$totals['sent']} | Delivered: {$totals['delivered']} | Read: {$totals['read']} | Failed: {$totals['failed']}");
        }

        $this->info('Campaign status sync complete.');

        return self::SUCCESS;
    }
}
