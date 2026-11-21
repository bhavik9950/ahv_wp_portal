<?php

declare(strict_types=1);

namespace App\Models\Sms;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsSenderId extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'sender_id',
        'dlt_entity_id',
        'description',
        'status',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(SmsTemplate::class, 'sms_sender_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class, 'sms_sender_id');
    }
}
