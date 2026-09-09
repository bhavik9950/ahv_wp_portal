<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\VoiceCallStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\VoiceCampaignFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $organization_id
 * @property CampaignStatus $status
 * @property array<string, mixed>|null $audience_filter
 * @property array<string, mixed>|null $audience_summary
 * @property array<string, int>|null $totals
 * @property int $delay_seconds
 * @property bool $consent_confirmed
 * @property string|null $provider_library_id
 * @property Carbon|null $scheduled_at
 * @property string $timezone
 */
class VoiceCampaign extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<VoiceCampaignFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'name',
        'audio_media_id',
        'audience_filter',
        'delay_seconds',
        'scheduled_at',
        'timezone',
        'consent_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'audience_filter' => 'array',
            'audience_summary' => 'array',
            'totals' => 'array',
            'status' => CampaignStatus::class,
            'consent_confirmed' => 'boolean',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Media, $this> */
    public function audio(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'audio_media_id');
    }

    /** @return HasMany<VoiceCallRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(VoiceCallRecipient::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return $this->status === CampaignStatus::Draft;
    }

    /**
     * @return array<string, int>
     */
    public function recomputeTotals(): array
    {
        $counts = $this->recipients()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $totals = ['total' => array_sum($counts)];
        foreach (VoiceCallStatus::values() as $s) {
            $totals[$s] = (int) ($counts[$s] ?? 0);
        }

        return $totals;
    }
}
