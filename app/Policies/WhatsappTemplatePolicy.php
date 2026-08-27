<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Support\TenantContext;

class WhatsappTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TemplateView->value);
    }

    public function view(User $user, WhatsappTemplate $template): bool
    {
        return $user->can(Permission::TemplateView->value) && $this->sameOrg($template);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TemplateManage->value);
    }

    public function submit(User $user, WhatsappTemplate $template): bool
    {
        return $user->can(Permission::TemplateSubmit->value) && $this->sameOrg($template);
    }

    public function delete(User $user, WhatsappTemplate $template): bool
    {
        return $user->can(Permission::TemplateManage->value) && $this->sameOrg($template);
    }

    private function sameOrg(WhatsappTemplate $template): bool
    {
        $current = app(TenantContext::class)->id();

        return $current !== null && (int) $template->organization_id === (int) $current;
    }
}
