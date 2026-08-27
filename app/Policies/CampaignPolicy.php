<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Campaign;
use App\Models\User;
use App\Support\TenantContext;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CampaignView->value);
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->can(Permission::CampaignView->value) && $this->sameOrg($campaign);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CampaignManage->value);
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->can(Permission::CampaignManage->value) && $this->sameOrg($campaign) && $campaign->isEditable();
    }

    public function launch(User $user, Campaign $campaign): bool
    {
        return $user->can(Permission::CampaignLaunch->value) && $this->sameOrg($campaign);
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->can(Permission::CampaignManage->value) && $this->sameOrg($campaign);
    }

    private function sameOrg(Campaign $campaign): bool
    {
        $current = app(TenantContext::class)->id();

        return $current !== null && (int) $campaign->organization_id === (int) $current;
    }
}
