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
 * @property string|null $campaign_id
 * @property string|null $campaign_recipient_id
 * @property string|null $contact_id
 * @property string|null $wamid
 * @property string|null $error_code
 * @property string|null $error_message
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

    /** @return BelongsTo<WhatsappPhoneNumber, $this> */
    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsappPhoneNumber::class, 'whatsapp_phone_number_id');
    }

    /** @return BelongsTo<WhatsappTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    /** Downloaded copy of an inbound image/video/audio/document, once fetched. */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /** The Meta media id referenced by this message's payload, if any. */
    public function metaMediaId(): ?string
    {
        return data_get($this->payload, "{$this->type->value}.id");
    }

    public function hasDownloadableMedia(): bool
    {
        return $this->type->isMedia() && $this->metaMediaId() !== null;
    }

    /** Best-effort plain-text rendition of this message, for chat bubbles / previews. */
    public function bodyText(): ?string
    {
        return match ($this->type) {
            MessageType::Text => data_get($this->payload, 'text.body'),
            MessageType::Image, MessageType::Video, MessageType::Document, MessageType::Audio => data_get($this->payload, "{$this->type->value}.caption"),
            MessageType::Interactive => data_get($this->payload, 'interactive.button_reply.title')
                ?? data_get($this->payload, 'interactive.list_reply.title')
                ?? data_get($this->payload, 'interactive.nfm_reply.body'),
            MessageType::Location => collect([
                data_get($this->payload, 'location.name'),
                data_get($this->payload, 'location.address'),
            ])->filter()->implode(' — ') ?: null,
            MessageType::Template => $this->renderedTemplateBody(),
        };
    }

    /** The stored template's body text with {{n}} placeholders swapped for the sent values. */
    private function renderedTemplateBody(): ?string
    {
        $body = $this->template?->bodyText();
        if ($body === null) {
            return null;
        }

        $bodyComponent = collect(data_get($this->payload, 'template.components', []))->firstWhere('type', 'body');

        foreach (($bodyComponent['parameters'] ?? []) as $i => $param) {
            $body = str_replace('{{'.($i + 1).'}}', (string) ($param['text'] ?? ''), $body);
        }

        return $body;
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
