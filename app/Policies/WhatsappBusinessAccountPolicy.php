<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WhatsappBusinessAccount;
use App\Support\TenantContext;

class WhatsappBusinessAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::WabaView->value);
    }

    public function view(User $user, WhatsappBusinessAccount $account): bool
    {
        return $user->can(Permission::WabaView->value)
            && $this->sameOrg($user, $account);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::WabaManage->value);
    }

    public function update(User $user, WhatsappBusinessAccount $account): bool
    {
        return $user->can(Permission::WabaManage->value)
            && $this->sameOrg($user, $account);
    }

    public function delete(User $user, WhatsappBusinessAccount $account): bool
    {
        return $this->update($user, $account);
    }

    /** Belt-and-braces: the global scope already prevents cross-tenant loads. */
    private function sameOrg(User $user, WhatsappBusinessAccount $account): bool
    {
        $current = app(TenantContext::class)->id();

        return $current !== null && (int) $account->organization_id === (int) $current;
    }
}
