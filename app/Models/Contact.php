<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OptInStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $organization_id
 * @property string $phone_e164
 * @property OptInStatus $opt_in_status
 * @property array<string, mixed>|null $custom_fields
 */
class Contact extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasUlids, SoftDeletes;

    // Contact-form fields only. Phone/hash/consent are set via ContactService /
    // OptInService, never mass-assigned from a request.
    protected $fillable = [
        'name',
        'email',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'opt_in_status' => OptInStatus::class,
            'opted_in_at' => 'datetime',
            'opted_out_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_group_contact')->withTimestamps();
    }

    /** @return HasMany<OptInRecord, $this> */
    public function optInRecords(): HasMany
    {
        return $this->hasMany(OptInRecord::class)->latest('created_at');
    }

    public function isOptedOut(): bool
    {
        return $this->opt_in_status === OptInStatus::OptedOut;
    }

    public function canReceiveMarketing(): bool
    {
        return $this->opt_in_status->canReceiveMarketing();
    }

    /** @param Builder<Contact> $query */
    public function scopeOptedIn(Builder $query): void
    {
        $query->where('opt_in_status', OptInStatus::OptedIn->value);
    }
}
