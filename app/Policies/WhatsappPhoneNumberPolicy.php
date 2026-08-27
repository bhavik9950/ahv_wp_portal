<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WhatsappPhoneNumber;
use App\Support\TenantContext;

class WhatsappPhoneNumberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::WabaView->value);
    }

    public function view(User $user, WhatsappPhoneNumber $number): bool
    {
        return $user->can(Permission::WabaView->value) && $this->sameOrg($number);
    }

    public function update(User $user, WhatsappPhoneNumber $number): bool
    {
        return $user->can(Permission::WabaManage->value) && $this->sameOrg($number);
    }

    private function sameOrg(WhatsappPhoneNumber $number): bool
    {
        $current = app(TenantContext::class)->id();

        return $current !== null && (int) $number->organization_id === (int) $current;
    }
}
