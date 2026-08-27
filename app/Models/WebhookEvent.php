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
}
