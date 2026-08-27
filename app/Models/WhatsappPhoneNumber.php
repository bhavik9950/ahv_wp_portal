<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappPhoneNumber extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<\Database\Factories\WhatsappPhoneNumberFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'phone_number_id',
        'display_phone_number',
        'verified_name',
        'quality_rating',
        'messaging_limit_tier',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function businessAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsappBusinessAccount::class, 'whatsapp_business_account_id');
    }

    public function isSendable(): bool
    {
        return $this->status !== 'disabled';
    }
}
