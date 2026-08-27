<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only. Never updated.
 */
class MessageStatusEvent extends Model
{
    use BelongsToOrganization, HasUlids;

    public const UPDATED_AT = null;

    // Written only by trusted internal code (status updater / webhook job).
    protected $fillable = [
        'organization_id',
        'message_id',
        'wamid',
        'status',
        'error_code',
        'error_title',
        'error_message',
        'webhook_event_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
