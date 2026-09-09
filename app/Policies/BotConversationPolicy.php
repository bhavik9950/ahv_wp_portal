<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\BotConversation;
use App\Models\User;
use App\Support\TenantContext;

class BotConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MessageView->value);
    }

    public function view(User $user, BotConversation $conversation): bool
    {
        $current = app(TenantContext::class)->id();

        return $user->can(Permission::MessageView->value)
            && $current !== null
            && (int) $conversation->organization_id === (int) $current;
    }
}
