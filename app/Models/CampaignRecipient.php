<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CampaignRecipientStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CampaignRecipientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $organization_id
 * @property string $campaign_id
 * @property string $phone_e164
 * @property CampaignRecipientStatus $status
 * @property array<int, string>|null $rendered_variables
 * @property int $attempts
 */
class CampaignRecipient extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<CampaignRecipientFactory> */
    use HasFactory;

    use HasUlids;

    // Written only by the campaign engine (materialiser / send job).
    protected $fillable = [
        'organization_id',
        'campaign_id',
        'contact_id',
        'phone_e164',
        'rendered_variables',
        'status',
        'message_id',
        'skip_reason',
        'error_code',
        'error_message',
        'attempts',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'rendered_variables' => 'array',
            'status' => CampaignRecipientStatus::class,
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
