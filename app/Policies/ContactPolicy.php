<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Contact;
use App\Models\User;
use App\Support\TenantContext;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ContactView->value);
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->can(Permission::ContactView->value) && $this->sameOrg($contact);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ContactManage->value);
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->can(Permission::ContactManage->value) && $this->sameOrg($contact);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $this->update($user, $contact);
    }

    public function import(User $user): bool
    {
        return $user->can(Permission::ContactImport->value);
    }

    public function export(User $user): bool
    {
        return $user->can(Permission::ContactExport->value);
    }

    private function sameOrg(Contact $contact): bool
    {
        $current = app(TenantContext::class)->id();

        return $current !== null && (int) $contact->organization_id === (int) $current;
    }
}
