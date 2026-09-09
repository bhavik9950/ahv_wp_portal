<?php

declare(strict_types=1);

namespace App\Services\Voice;

use App\Enums\CampaignStatus;
use App\Enums\VoiceCallStatus;
use App\Jobs\DispatchVoiceBatchJob;
use App\Models\VoiceCampaign;
use App\Services\Audit\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Validates, materialises, schedules and drives a voice broadcast campaign.
 * Mirrors CampaignLauncher for WhatsApp but for recorded outbound calls.
 */
final class VoiceCampaignService
{
    private const MATERIALISE_CHUNK = 1000;

    public function __construct(
        private readonly VoiceAudienceResolver $audience,
        private readonly VoiceBroadcastManager $manager,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return list<string> blocking problems ('' if ready)
     */
    public function validate(VoiceCampaign $campaign): array
    {
        $errors = [];

        if ($campaign->audio_media_id === null) {
            $errors[] = 'Upload the audio clip to play.';
        }

        if (! $campaign->consent_confirmed) {
            $errors[] = 'Confirm that the recipients have consented and the campaign follows TRAI/DND rules.';
        }

        $count = $this->audience->count($campaign);
        $max = (int) config('services.voice.max_recipients', 2000);

        if ($count === 0) {
            $errors[] = 'The selected audience has no reachable recipients.';
        } elseif ($count > $max) {
            $errors[] = "The audience has {$count} recipients — the limit per voice campaign is {$max}.";
        }

        if (! $this->manager->isConfigured()) {
            $errors[] = 'The voice provider credentials are not configured on the server.';
        }

        return $errors;
    }

    public function materialise(VoiceCampaign $campaign): int
    {
        $created = 0;

        $this->audience->query($campaign)
            ->chunkById(self::MATERIALISE_CHUNK, function ($contacts) use ($campaign, &$created): void {
                $rows = [];
                foreach ($contacts as $contact) {
                    $rows[] = [
                        'id' => (string) Str::ulid(),
                        'organization_id' => $campaign->organization_id,
                        'voice_campaign_id' => $campaign->getKey(),
                        'contact_id' => $contact->getKey(),
                        'phone_e164' => $contact->phone_e164,
                        'phone_hash' => hash('sha256', (string) $contact->phone_e164),
                        'status' => VoiceCallStatus::Pending->value,
                        'attempts' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($rows !== []) {
                    DB::table('voice_call_recipients')->insertOrIgnore($rows);
                    $created += count($rows);
                }
            });

        $campaign->forceFill([
            'audience_summary' => ['total' => $campaign->recipients()->count()],
        ])->save();

        return $created;
    }

    public function launch(VoiceCampaign $campaign, ?Carbon $at): void
    {
        if (! in_array($campaign->status, [CampaignStatus::Draft, CampaignStatus::Paused], true)) {
            throw new RuntimeException('This campaign cannot be launched from its current state.');
        }

        $errors = $this->validate($campaign);
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $this->ensureAudioUploaded($campaign);

        $when = $at?->clone()->utc();

        DB::transaction(function () use ($campaign, $when): void {
            if ($campaign->recipients()->doesntExist()) {
                $this->materialise($campaign);
            }

            $campaign->forceFill([
                'status' => $when && $when->isFuture() ? CampaignStatus::Scheduled : CampaignStatus::Processing,
                'scheduled_at' => $when,
                'started_at' => $when && $when->isFuture() ? null : now(),
            ])->save();
        });

        if ($campaign->status === CampaignStatus::Processing) {
            DispatchVoiceBatchJob::dispatch($campaign->getKey())->onQueue('default');
        }

        $this->audit->log('voice_campaign.launched', $campaign, [
            'scheduled_at' => optional($when)->toIso8601String(),
            'recipients' => $campaign->recipients()->count(),
        ]);
    }

    public function pause(VoiceCampaign $campaign): void
    {
        $updated = VoiceCampaign::query()->whereKey($campaign->getKey())
            ->whereIn('status', [CampaignStatus::Processing->value, CampaignStatus::Scheduled->value])
            ->update(['status' => CampaignStatus::Paused->value]);

        if ($updated) {
            $this->audit->log('voice_campaign.paused', $campaign);
        }
    }

    public function resume(VoiceCampaign $campaign): void
    {
        $updated = VoiceCampaign::query()->whereKey($campaign->getKey())
            ->where('status', CampaignStatus::Paused->value)
            ->update(['status' => CampaignStatus::Processing->value, 'started_at' => $campaign->started_at ?? now()]);

        if ($updated) {
            DispatchVoiceBatchJob::dispatch($campaign->getKey())->onQueue('default');
            $this->audit->log('voice_campaign.resumed', $campaign);
        }
    }

    public function cancel(VoiceCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign): void {
            $updated = VoiceCampaign::query()->whereKey($campaign->getKey())
                ->whereIn('status', [
                    CampaignStatus::Draft->value, CampaignStatus::Scheduled->value,
                    CampaignStatus::Processing->value, CampaignStatus::Paused->value,
                ])
                ->update(['status' => CampaignStatus::Cancelled->value, 'finished_at' => now()]);

            if ($updated) {
                $campaign->recipients()
                    ->whereIn('status', [VoiceCallStatus::Pending->value, VoiceCallStatus::Queued->value])
                    ->update(['status' => VoiceCallStatus::Skipped->value, 'error_message' => 'campaign_cancelled']);
            }
        });

        $campaign->forceFill(['totals' => $campaign->recomputeTotals()])->save();
        $this->audit->log('voice_campaign.cancelled', $campaign);
    }

    /**
     * Push the local audio clip to the provider's library once, cache the id.
     */
    public function ensureAudioUploaded(VoiceCampaign $campaign): string
    {
        if ($campaign->provider_library_id !== null && $campaign->provider_library_id !== '') {
            return $campaign->provider_library_id;
        }

        $media = $campaign->audio()->first();
        if ($media === null) {
            throw new RuntimeException('This campaign has no audio clip.');
        }

        $contents = Storage::disk($media->disk)->get($media->path);
        if ($contents === null) {
            throw new RuntimeException('The stored audio file is missing.');
        }

        $libraryId = $this->manager->driver()->uploadAudio(
            $this->manager->credentials(),
            $contents,
            $media->original_name,
            'voice-campaign-'.$campaign->getKey(),
        );

        $campaign->forceFill(['provider_library_id' => $libraryId])->save();

        return $libraryId;
    }

    public function createDraft(string $name): VoiceCampaign
    {
        $campaign = new VoiceCampaign;
        $campaign->forceFill([
            'organization_id' => app(TenantContext::class)->id(),
            'name' => $name,
            'status' => CampaignStatus::Draft->value,
            'timezone' => config('app.timezone', 'Asia/Kolkata'),
            'created_by' => Auth::id(),
        ])->save();

        return $campaign;
    }
}
