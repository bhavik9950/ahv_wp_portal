<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Jobs\DispatchCampaignBatchJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Validates, materialises, schedules and drives the lifecycle of a campaign.
 *
 * Materialisation snapshots the audience into campaign_recipients with the
 * variable values already rendered — so later contact edits or opt-outs do not
 * silently change an in-flight campaign, and pause/resume/cancel operate on a
 * fixed set of rows.
 */
final class CampaignLauncher
{
    private const MATERIALISE_CHUNK = 1000;

    public function __construct(
        private readonly CampaignAudienceResolver $audience,
        private readonly CampaignVariableRenderer $renderer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return list<string> blocking problems ('' if ready to launch)
     */
    public function validate(Campaign $campaign): array
    {
        $errors = [];

        $template = $campaign->template()->first();
        if ($template === null) {
            $errors[] = 'Select a message template.';
        } elseif (! $template->isSendable()) {
            $errors[] = 'The selected template is not APPROVED by Meta.';
        }

        if ($campaign->whatsapp_phone_number_id === null) {
            $errors[] = 'Select the sending phone number.';
        }

        if ($template !== null) {
            $placeholderCount = count($this->renderer->render($campaign, null));
            $map = $campaign->variable_map ?? [];
            $mappedCount = count(array_filter($map, fn ($v) => is_array($v) && isset($v['type'])));

            if ($placeholderCount > $mappedCount) {
                $errors[] = "Map all {$placeholderCount} template variable(s).";
            }
        }

        if ($this->audience->count($campaign) === 0) {
            $errors[] = 'The selected audience has no eligible recipients.';
        }

        return $errors;
    }

    /**
     * Freeze the audience into campaign_recipients rows.
     */
    public function materialise(Campaign $campaign): int
    {
        $created = 0;

        // Freeze the ELIGIBLE audience (consent filter already applied). Opted-out
        // contacts are not recipients; the count they represent is kept in
        // audience_summary for the report. A contact who opts out *after* this
        // point is caught at send time and the recipient becomes "opted_out".
        $this->audience->query($campaign)
            ->chunkById(self::MATERIALISE_CHUNK, function ($contacts) use ($campaign, &$created): void {
                $rows = [];
                /** @var Contact $contact */
                foreach ($contacts as $contact) {
                    $rows[] = [
                        'id' => (string) Str::ulid(),
                        'organization_id' => $campaign->organization_id,
                        'campaign_id' => $campaign->getKey(),
                        'contact_id' => $contact->getKey(),
                        'phone_e164' => $contact->phone_e164,
                        'rendered_variables' => json_encode($this->renderer->render($campaign, $contact)),
                        'status' => CampaignRecipientStatus::Pending->value,
                        'skip_reason' => null,
                        'attempts' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($rows !== []) {
                    // Ignore duplicates (campaign_id + phone_e164 unique) so re-runs are safe.
                    DB::table('campaign_recipients')->insertOrIgnore($rows);
                    $created += count($rows);
                }
            });

        $campaign->forceFill([
            'audience_summary' => $this->audience->summary($campaign),
        ])->save();

        return $created;
    }

    public function schedule(Campaign $campaign, ?Carbon $at): void
    {
        $this->guardLaunchable($campaign);

        $when = $at?->clone()->utc();

        DB::transaction(function () use ($campaign, $when): void {
            if ($campaign->recipients()->doesntExist()) {
                $this->materialise($campaign);
            }

            $campaign->forceFill([
                'status' => $when && $when->isFuture() ? CampaignStatus::Scheduled : CampaignStatus::Processing,
                'scheduled_at' => $when,
                'confirmed_at' => now(),
                'started_at' => $when && $when->isFuture() ? null : now(),
            ])->save();
        });

        if ($campaign->status === CampaignStatus::Processing) {
            DispatchCampaignBatchJob::dispatch($campaign->getKey())->onQueue('whatsapp-high');
        }

        $this->audit->log('campaign.launched', $campaign, [
            'scheduled_at' => optional($when)->toIso8601String(),
            'recipients' => $campaign->recipients()->count(),
        ]);
    }

    public function pause(Campaign $campaign): void
    {
        $updated = Campaign::query()->whereKey($campaign->getKey())
            ->whereIn('status', [CampaignStatus::Processing->value, CampaignStatus::Scheduled->value])
            ->update(['status' => CampaignStatus::Paused->value, 'paused_by' => Auth::id()]);

        if ($updated) {
            $this->audit->log('campaign.paused', $campaign);
        }
    }

    public function resume(Campaign $campaign): void
    {
        $updated = Campaign::query()->whereKey($campaign->getKey())
            ->where('status', CampaignStatus::Paused->value)
            ->update(['status' => CampaignStatus::Processing->value, 'paused_by' => null, 'started_at' => $campaign->started_at ?? now()]);

        if ($updated) {
            DispatchCampaignBatchJob::dispatch($campaign->getKey())->onQueue('whatsapp-high');
            $this->audit->log('campaign.resumed', $campaign);
        }
    }

    public function cancel(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $updated = Campaign::query()->whereKey($campaign->getKey())
                ->whereIn('status', [
                    CampaignStatus::Draft->value, CampaignStatus::Scheduled->value,
                    CampaignStatus::Processing->value, CampaignStatus::Paused->value,
                ])
                ->update(['status' => CampaignStatus::Cancelled->value, 'finished_at' => now()]);

            if ($updated) {
                $campaign->recipients()
                    ->whereIn('status', [CampaignRecipientStatus::Pending->value, CampaignRecipientStatus::Queued->value])
                    ->update(['status' => CampaignRecipientStatus::Skipped->value, 'skip_reason' => 'campaign_cancelled']);
            }
        });

        $campaign->forceFill(['totals' => $campaign->recomputeTotals()])->save();
        $this->audit->log('campaign.cancelled', $campaign);
    }

    private function guardLaunchable(Campaign $campaign): void
    {
        if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Paused], true)) {
            throw new \RuntimeException('This campaign cannot be launched from its current state.');
        }

        $errors = $this->validate($campaign);
        if ($errors !== []) {
            throw new \RuntimeException(implode(' ', $errors));
        }
    }
}
