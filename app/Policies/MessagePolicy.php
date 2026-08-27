<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Message;
use App\Models\User;
use App\Support\TenantContext;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::MessageView->value);
    }

    public function view(User $user, Message $message): bool
    {
        return $user->can(Permission::MessageView->value)
            && (int) $message->organization_id === (int) app(TenantContext::class)->id();
    }

    /** Individual / test sends. */
    public function create(User $user): bool
    {
        return $user->can(Permission::MessageSend->value);
    }
}
