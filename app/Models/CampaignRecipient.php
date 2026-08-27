<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CampaignRecipientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecipient extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<CampaignRecipientFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'phone_e164',
        'rendered_variables',
        'status',
        'message_id',
        'skip_reason',
        'attempts',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'rendered_variables' => 'array',
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
