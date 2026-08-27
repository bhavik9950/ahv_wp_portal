<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ScheduledMessage extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'whatsapp_phone_number_id',
        'contact_id',
        'to_phone',
        'type',
        'payload',
        'template_id',
        'media_id',
        'send_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'send_at' => 'datetime',
        ];
    }
}
