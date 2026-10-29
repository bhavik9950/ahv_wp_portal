<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 */
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'timezone',
        'status',
        'plan',
        'subscription_expires_at',
        'is_lifetime',
        'allowed_modules',
    ];

    protected $casts = [
        'settings' => 'array',
        'subscription_expires_at' => 'datetime',
        'is_lifetime' => 'boolean',
        'allowed_modules' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $org): void {
            if (blank($org->slug)) {
                $org->slug = Str::slug($org->name).'-'.Str::lower(Str::random(6));
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationUser::class)
            ->withTimestamps();
    }

    public function whatsappBusinessAccounts(): HasMany
    {
        return $this->hasMany(WhatsappBusinessAccount::class);
    }

    /** @return HasMany<SubscriptionRequest, $this> */
    public function subscriptionRequests(): HasMany
    {
        return $this->hasMany(SubscriptionRequest::class);
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->is_lifetime) {
            return true;
        }

        // If not explicitly set (e.g. system seed / root admin org), default to active
        if ($this->subscription_expires_at === null) {
            return true;
        }

        return $this->subscription_expires_at->isFuture();
    }

    public function isSubscriptionExpired(): bool
    {
        return ! $this->hasActiveSubscription();
    }

    public function subscriptionStatus(): string
    {
        if ($this->is_lifetime) {
            return 'lifetime';
        }

        if ($this->hasActiveSubscription()) {
            return 'active';
        }

        return 'expired';
    }

    public function subscriptionDaysRemaining(): int
    {
        if ($this->is_lifetime || $this->subscription_expires_at === null) {
            return 9999;
        }

        if ($this->subscription_expires_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->subscription_expires_at, false);
    }

    public function hasModule(string $module): bool
    {
        if ($this->allowed_modules === null) {
            return true; // null = all modules permitted
        }

        return in_array($module, $this->allowed_modules, true);
    }

    public function extendSubscription(string $plan, ?int $days = null): void
    {
        if ($plan === 'lifetime') {
            $this->forceFill([
                'plan' => 'lifetime',
                'is_lifetime' => true,
                'subscription_expires_at' => null,
            ])->save();

            return;
        }

        $addDays = $days ?? match ($plan) {
            'monthly' => 30,
            'half_yearly' => 180,
            'yearly' => 365,
            default => 30,
        };

        $base = ($this->subscription_expires_at && $this->subscription_expires_at->isFuture())
            ? $this->subscription_expires_at
            : now();

        $this->forceFill([
            'plan' => $plan,
            'is_lifetime' => false,
            'subscription_expires_at' => $base->copy()->addDays($addDays),
        ])->save();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /** A retention setting (days) from settings JSON, or null to keep forever. */
    public function retentionDays(string $key): ?int
    {
        $value = data_get($this->settings, "retention_days.$key");

        return is_numeric($value) ? (int) $value : null;
    }
}
