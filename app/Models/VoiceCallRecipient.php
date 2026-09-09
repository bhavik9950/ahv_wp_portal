<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VoiceCallStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $organization_id
 * @property VoiceCallStatus $status
 * @property string $phone_e164
 * @property string|null $provider_uuid
 * @property int|null $duration_seconds
 */
class VoiceCallRecipient extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'voice_campaign_id',
        'contact_id',
        'phone_e164',
        'phone_hash',
        'status',
        'provider_uuid',
        'error_message',
        'duration_seconds',
        'called_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VoiceCallStatus::class,
            'called_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<VoiceCampaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VoiceCampaign::class, 'voice_campaign_id');
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
