<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Not tenant-scoped globally: the webhook arrives before the org is resolved.
 * organization_id is filled in during processing.
 */
class WebhookEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'source',
        'event_fingerprint',
        'signature_valid',
        'payload',
        'headers',
        'status',
        'processed_at',
        'error',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'signature_valid' => 'boolean',
            'processed_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /**
     * A coarse label for what this event carried, derived from the payload.
     */
    public function kind(): string
    {
        foreach ((array) data_get($this->payload, 'entry', []) as $entry) {
            foreach ((array) data_get($entry, 'changes', []) as $change) {
                $value = $change['value'] ?? [];

                if (! empty($value['messages'])) {
                    $type = data_get($value, 'messages.0.type', 'message');

                    return "inbound: {$type}";
                }
                if (! empty($value['statuses'])) {
                    return 'status: '.data_get($value, 'statuses.0.status', 'update');
                }
                if (($change['field'] ?? null) === 'message_template_status_update') {
                    return 'template status';
                }
                if ($change['field'] ?? null) {
                    return (string) $change['field'];
                }
            }
        }

        return 'unknown';
    }

    /**
     * One-line human summary (sender + snippet, or affected wamid).
     */
    public function summary(): string
    {
        $value = data_get($this->payload, 'entry.0.changes.0.value', []);

        if (! empty($value['messages'])) {
            $from = data_get($value, 'messages.0.from', '?');
            $text = data_get($value, 'messages.0.text.body');

            return $text ? "from +{$from}: ".mb_strimwidth($text, 0, 60, '…') : "from +{$from}";
        }
        if (! empty($value['statuses'])) {
            return data_get($value, 'statuses.0.status', '?').' — '.data_get($value, 'statuses.0.recipient_id', '');
        }
        if (! empty($value['message_template_name'])) {
            return $value['message_template_name'].' → '.($value['event'] ?? '?');
        }

        return '—';
    }
}
