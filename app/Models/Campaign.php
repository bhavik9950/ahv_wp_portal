<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            'totals' => 'array',
            'status' => CampaignStatus::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsappPhoneNumber::class, 'whatsapp_phone_number_id');
    }
}
