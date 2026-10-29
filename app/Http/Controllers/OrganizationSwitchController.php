<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $targetOrgId = (int) $validated['organization_id'];

        // Non-super-admins can only switch to an organization they belong to.
        if (! $user->isSuperAdmin() && ! $user->belongsToOrganization($targetOrgId)) {
            abort(403, 'You do not have access to this organization.');
        }

        $org = Organization::query()->withoutGlobalScopes()->findOrFail($targetOrgId);

        $request->session()->put('current_organization_id', $org->getKey());

        return back()->with('flash_notify', [
            'type' => 'info',
            'message' => "Switched to {$org->name}",
        ]);
    }
}
