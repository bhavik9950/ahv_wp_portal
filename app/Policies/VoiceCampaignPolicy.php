<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\VoiceCampaign;
use App\Support\TenantContext;

class VoiceCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::CampaignView->value);
    }

    public function view(User $user, VoiceCampaign $campaign): bool
    {
        return $user->can(Permission::CampaignView->value) && $this->sameOrg($campaign);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CampaignManage->value);
    }

    public function launch(User $user, VoiceCampaign $campaign): bool
    {
        return $user->can(Permission::CampaignLaunch->value) && $this->sameOrg($campaign);
    }

    public function delete(User $user, VoiceCampaign $campaign): bool
    {
        return $user->can(Permission::CampaignManage->value) && $this->sameOrg($campaign);
    }

    private function sameOrg(VoiceCampaign $campaign): bool
    {
        $current = app(TenantContext::class)->id();

        return $current !== null && (int) $campaign->organization_id === (int) $current;
    }
}
