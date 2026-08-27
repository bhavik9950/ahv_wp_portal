<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'country_code',
        'phone_e164',
        'phone_hash',
        'email',
        'custom_fields',
        'opt_in_status',
        'opted_in_at',
        'opt_in_source',
        'opted_out_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'opted_in_at' => 'datetime',
            'opted_out_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_group_contact')->withTimestamps();
    }

    public function isOptedOut(): bool
    {
        return $this->opt_in_status === 'opted_out';
    }
}
