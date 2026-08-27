<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WhatsappBusinessAccountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappBusinessAccount extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WhatsappBusinessAccountFactory> */
    use HasFactory;

    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'meta_business_account_id',
        'waba_id',
        'app_id',
        'access_token',
        'app_secret',
        'webhook_verify_token',
        'api_version',
        'default_country_code',
    ];

    /**
     * Encrypted-at-rest credentials + never exposed by default serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'access_token',
        'app_secret',
        'webhook_verify_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'app_secret' => 'encrypted',
            'webhook_verify_token' => 'encrypted',
            'last_error' => 'array',
            'is_active' => 'boolean',
            'token_last_checked_at' => 'datetime',
            'token_expires_at' => 'datetime',
        ];
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(WhatsappPhoneNumber::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsappTemplate::class);
    }

    /** Masked representation for UI / API — e.g. "••••••••abcd". */
    public function maskedAccessToken(): ?string
    {
        return self::mask($this->access_token);
    }

    public function maskedAppSecret(): ?string
    {
        return self::mask($this->app_secret);
    }

    public function hasWebhookVerifyToken(): bool
    {
        return filled($this->webhook_verify_token);
    }

    public function effectiveApiVersion(): string
    {
        return $this->api_version ?: (string) config('services.whatsapp.api_version');
    }

    private static function mask(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return str_repeat('•', 8).substr($value, -4);
    }
}
