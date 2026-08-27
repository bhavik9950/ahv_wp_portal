<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Data;

use App\Enums\MessageType;

/**
 * A message ready to hand to a driver. `toGraphPayload()` produces the exact
 * body sent to POST /{phone_number_id}/messages (minus messaging_product/to,
 * which the driver adds).
 */
final readonly class OutboundMessage
{
    /**
     * @param  array<string, mixed>  $content  type-specific content block
     */
    public function __construct(
        public Recipient $to,
        public MessageType $type,
        public array $content,
    ) {}

    public static function text(Recipient $to, string $body, bool $previewUrl = false): self
    {
        return new self($to, MessageType::Text, [
            'text' => ['body' => $body, 'preview_url' => $previewUrl],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $components  Meta template components
     */
    public static function template(Recipient $to, string $name, string $language, array $components = []): self
    {
        $template = ['name' => $name, 'language' => ['code' => $language]];

        if ($components !== []) {
            $template['components'] = $components;
        }

        return new self($to, MessageType::Template, ['template' => $template]);
    }

    /** @return array<string, mixed> */
    public function toGraphPayload(): array
    {
        return ['type' => $this->type->value] + $this->content;
    }
}
