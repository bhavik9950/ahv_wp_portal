<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property MessageStatus $status
 * @property MessageType $type
 * @property int $organization_id
 */
class Message extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'whatsapp_phone_number_id',
        'campaign_id',
        'campaign_recipient_id',
        'contact_id',
        'direction',
        'to_phone',
        'to_phone_hash',
        'type',
        'template_id',
        'payload',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'type' => MessageType::class,
            'status' => MessageStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(MessageStatusEvent::class)->orderBy('occurred_at');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsappPhoneNumber::class, 'whatsapp_phone_number_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, [
            MessageStatus::Sent,
            MessageStatus::Delivered,
            MessageStatus::Read,
        ], true);
    }
}
