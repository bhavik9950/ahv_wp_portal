<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationUser extends Pivot
{
    use HasUlids;

    protected $table = 'organization_user';

    public $incrementing = false;

    protected $keyType = 'string';
}
