<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $organization_id
 * @property CampaignStatus $status
 * @property array<array-key, mixed>|null $variable_map
 * @property array<string, mixed>|null $audience_filter
 * @property array<string, mixed>|null $audience_summary
 * @property array<string, int>|null $totals
 * @property int|null $send_delay_seconds
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $confirmed_at
 * @property string $timezone
 * @property string|null $whatsapp_phone_number_id
 * @property string|null $template_id
 */
class Campaign extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'whatsapp_phone_number_id',
        'template_id',
        'variable_map',
        'media_id',
        'audience_filter',
        'send_delay_seconds',
        'scheduled_at',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'variable_map' => 'array',
            'audience_filter' => 'array',
            'audience_summary' => 'array',
            'totals' => 'array',
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return HasMany<CampaignRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    /** @return BelongsTo<WhatsappTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    /** @return BelongsTo<WhatsappPhoneNumber, $this> */
    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsappPhoneNumber::class, 'whatsapp_phone_number_id');
    }

    /** @return BelongsTo<Media, $this> */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [CampaignStatus::Draft], true);
    }

    /**
     * Live counts by recipient status.
     *
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
        foreach (CampaignRecipientStatus::values() as $s) {
            $totals[$s] = (int) ($counts[$s] ?? 0);
        }

        return $totals;
    }
}
