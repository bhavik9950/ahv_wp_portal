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

    /**
     * Build a template message with body variable values.
     *
     * @param  list<string>  $bodyParams  ordered values for {{1}}, {{2}}, …
     * @param  array<string, mixed>|null  $headerParam  e.g. ['type' => 'image', 'image' => ['id' => '...']]
     */
    public static function templateWithParams(Recipient $to, string $name, string $language, array $bodyParams = [], ?array $headerParam = null): self
    {
        $components = [];

        if ($headerParam !== null) {
            $components[] = ['type' => 'header', 'parameters' => [$headerParam]];
        }

        if ($bodyParams !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn (string $v) => ['type' => 'text', 'text' => $v], $bodyParams),
            ];
        }

        return self::template($to, $name, $language, $components);
    }

    /** @return array<string, mixed> */
    public function toGraphPayload(): array
    {
        return ['type' => $this->type->value] + $this->content;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'to' => $this->to->e164,
            'type' => $this->type->value,
            'content' => $this->content,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            new Recipient((string) $data['to']),
            MessageType::from((string) $data['type']),
            (array) $data['content'],
        );
    }
}
