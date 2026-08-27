<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Templates;

/**
 * Turns the builder form data into Meta's `components` array and back, plus
 * structural validation that runs before any submission to Meta.
 *
 * This never tries to bypass Meta's review — it only catches obvious mistakes
 * early and shows a preview.
 */
final class TemplateComposer
{
    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public function toComponents(array $data): array
    {
        $components = [];

        $headerType = $data['header_type'] ?? 'none';
        if ($headerType === 'text' && filled($data['header_text'] ?? null)) {
            $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $data['header_text']];
        } elseif (in_array($headerType, ['image', 'video', 'document'], true)) {
            $components[] = ['type' => 'HEADER', 'format' => strtoupper($headerType)];
        }

        $components[] = ['type' => 'BODY', 'text' => (string) ($data['body'] ?? '')];

        if (filled($data['footer'] ?? null)) {
            $components[] = ['type' => 'FOOTER', 'text' => $data['footer']];
        }

        $buttons = $this->buttons($data['buttons'] ?? []);
        if ($buttons !== []) {
            $components[] = ['type' => 'BUTTONS', 'buttons' => $buttons];
        }

        return $components;
    }

    /**
     * @param  array<int, array<string, mixed>>  $raw
     * @return list<array<string, mixed>>
     */
    private function buttons(array $raw): array
    {
        $out = [];

        foreach ($raw as $b) {
            $type = $b['type'] ?? null;
            $text = trim((string) ($b['text'] ?? ''));
            if ($text === '' || $type === null) {
                continue;
            }

            $out[] = match ($type) {
                'quick_reply' => ['type' => 'QUICK_REPLY', 'text' => $text],
                'url' => ['type' => 'URL', 'text' => $text, 'url' => (string) ($b['url'] ?? '')],
                'phone' => ['type' => 'PHONE_NUMBER', 'text' => $text, 'phone_number' => (string) ($b['phone_number'] ?? '')],
                default => null,
            } ?? [];
        }

        return array_values(array_filter($out));
    }

    /**
     * Distinct, ordered {{n}} placeholders in the body text.
     *
     * @return list<int>
     */
    public function bodyPlaceholders(string $body): array
    {
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $body, $m);

        $nums = array_values(array_unique(array_map('intval', $m[1])));
        sort($nums);

        return $nums;
    }

    /**
     * @return list<string> human-readable structural problems ('' if none)
     */
    public function structuralErrors(string $body): array
    {
        $errors = [];
        $nums = $this->bodyPlaceholders($body);

        if ($nums !== [] && $nums !== range(1, count($nums))) {
            $errors[] = 'Body variables must be numbered sequentially starting at {{1}} with no gaps.';
        }

        if (preg_match('/\{\{[^}]*\}\}\s*\{\{/', $body)) {
            $errors[] = 'Two variables cannot be adjacent — put text between them.';
        }

        if (mb_strlen(trim($body)) === 0) {
            $errors[] = 'Body text is required.';
        }

        return $errors;
    }

    /**
     * Fill a template preview with sample values.
     */
    public function preview(string $body, array $sampleValues = []): string
    {
        return preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', function ($m) use ($sampleValues) {
            $i = (int) $m[1];

            return $sampleValues[$i - 1] ?? "[value {$i}]";
        }, $body) ?? $body;
    }
}
