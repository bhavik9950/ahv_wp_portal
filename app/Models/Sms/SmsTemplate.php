<?php

declare(strict_types=1);

namespace App\Models\Sms;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsTemplate extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'sms_sender_id',
        'name',
        'dlt_template_id',
        'template_type',
        'body',
        'variables',
        'status',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(SmsSenderId::class, 'sms_sender_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class, 'sms_template_id');
    }
}
