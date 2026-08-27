<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Media;
use App\Models\User;
use App\Support\TenantContext;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CampaignView->value) || $user->can(Permission::TemplateView->value);
    }

    public function view(User $user, Media $media): bool
    {
        return $this->viewAny($user) && $this->sameOrg($media);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CampaignManage->value) || $user->can(Permission::TemplateManage->value);
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->create($user) && $this->sameOrg($media);
    }

    private function sameOrg(Media $media): bool
    {
        $current = app(TenantContext::class)->id();

        return $current !== null && (int) $media->organization_id === (int) $current;
    }
}
