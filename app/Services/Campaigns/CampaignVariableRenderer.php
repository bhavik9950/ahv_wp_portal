<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\WhatsappTemplate;

/**
 * Renders a campaign's body variable values for one contact.
 *
 * variable_map shape (keyed by placeholder number as a string):
 *   {
 *     "1": { "type": "contact_field", "value": "name" },
 *     "2": { "type": "static",        "value": "ORD-1000" },
 *     "3": { "type": "custom_field",  "value": "city" }
 *   }
 */
final class CampaignVariableRenderer
{
    /**
     * @return list<string> ordered values for {{1}}, {{2}}, …
     */
    public function render(Campaign $campaign, ?Contact $contact): array
    {
        $template = $campaign->template()->first();
        $placeholders = $template instanceof WhatsappTemplate
            ? $this->bodyPlaceholders($template)
            : array_map('intval', array_keys($campaign->variable_map ?? []));

        sort($placeholders);
        $map = $campaign->variable_map ?? [];
        $out = [];

        foreach ($placeholders as $n) {
            $spec = $map[(string) $n] ?? null;
            $out[] = $this->resolve(is_array($spec) ? $spec : null, $contact);
        }

        return $out;
    }

    /**
     * @param  array{type?:string, value?:string}|null  $spec
     */
    private function resolve(?array $spec, ?Contact $contact): string
    {
        if ($spec === null) {
            return '';
        }

        $value = (string) ($spec['value'] ?? '');

        return match ($spec['type'] ?? 'static') {
            'contact_field' => $contact === null ? '' : match ($value) {
                'name' => (string) ($contact->name ?? ''),
                'phone' => $contact->phone_e164,
                'email' => (string) ($contact->email ?? ''),
                default => '',
            },
            'custom_field' => $contact === null ? '' : (string) ($contact->custom_fields[$value] ?? ''),
            default => $value,
        };
    }

    /**
     * @return list<int>
     */
    private function bodyPlaceholders(WhatsappTemplate $template): array
    {
        $body = collect($template->components ?? [])->firstWhere('type', 'BODY');
        $text = is_array($body) ? (string) ($body['text'] ?? '') : '';
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $text, $m);

        return array_values(array_unique(array_map('intval', $m[1])));
    }
}
