<?php

declare(strict_types=1);

namespace App\Models\Sms;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'sms_campaign_id',
        'contact_id',
        'to_phone',
        'sender_id',
        'dlt_template_id',
        'message_body',
        'segments',
        'cost',
        'provider_cost',
        'status',
        'error_message',
        'provider_message_id',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'cost' => 'decimal:4',
        'provider_cost' => 'decimal:4',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
