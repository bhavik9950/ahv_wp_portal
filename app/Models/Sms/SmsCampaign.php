<?php

declare(strict_types=1);

namespace App\Models\Sms;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'sms_sender_id',
        'sms_template_id',
        'name',
        'message_body',
        'audience_count',
        'sent_count',
        'delivered_count',
        'failed_count',
        'total_cost',
        'status',
        'scheduled_at',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_cost' => 'decimal:2',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SmsSenderId::class, 'sms_sender_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'sms_template_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'sms_campaign_id');
    }
}
