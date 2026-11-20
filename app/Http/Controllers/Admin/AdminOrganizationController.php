<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Models\WhatsappBusinessAccount;
use App\Services\Organizations\OrganizationProvisioner;
use App\Services\System\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminOrganizationController extends Controller
{
    public function __construct(private readonly SystemSettings $settings) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');

        $query = Organization::query()->withoutGlobalScopes()
            ->withCount(['users', 'whatsappBusinessAccounts'])
            ->latest('id');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $organizations = $query->paginate(20)->withQueryString();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
            'search' => $search,
            'plans' => $this->settings->billingPlans(),
        ]);
    }

    public function store(Request $request, OrganizationProvisioner $provisioner): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:150'],
            'password' => ['nullable', 'string', 'min:6'],
            'plan' => ['required', 'string', 'in:monthly,half_yearly,yearly,lifetime'],
            'waba_id' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        $password = ! empty($validated['password']) ? $validated['password'] : Str::password(10);
        $timezone = $validated['timezone'] ?: 'Asia/Kolkata';

        // 1. Provision organization
        $organization = $provisioner->create([
            'name' => $validated['name'],
            'timezone' => $timezone,
        ]);

        // 2. Set plan validity
        $organization->extendSubscription($validated['plan']);

        // 3. Find or create user
        $user = User::query()->firstWhere('email', $validated['admin_email']);
        $isNewUser = $user === null;
        if ($isNewUser) {
            $user = (new User)->forceFill([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);
            $user->save();
        }

        // 4. Assign OrgAdmin role in this organization
        $provisioner->addMember($organization, $user, OrganizationRole::OrgAdmin);

        // 5. Optional WABA ID
        if (! empty($validated['waba_id'])) {
            (new WhatsappBusinessAccount)->forceFill([
                'organization_id' => $organization->getKey(),
                'name' => "{$organization->name} WhatsApp",
                'waba_id' => $validated['waba_id'],
                'is_active' => false,
                'default_country_code' => '91',
            ])->save();
        }

        $msg = "Organization '{$organization->name}' created! Admin: {$user->email}";

        return redirect()->route('admin.organizations.index')->with('created_credentials', [
            'org_name' => $organization->name,
            'email' => $user->email,
            'password' => $password,
            'is_new_user' => $isNewUser,
        ])->with('flash_notify', [
            'type' => 'success',
            'message' => $msg,
        ]);
    }

    public function edit(Organization $organization): View
    {
        $availableModules = [
            'campaigns' => 'Broadcast Campaigns',
            'voice_campaigns' => 'Voice Broadcast Calling',
            'assistant' => 'AI Assistant Bot',
            'templates' => 'Meta Templates Management',
            'contacts' => 'Contacts & CSV Import',
            'chats' => '1-on-1 Live Chat Inbox',
            'media' => 'Media Library',
            'reports' => 'Reports & Analytics',
        ];

        return view('admin.organizations.edit', [
            'organization' => $organization,
            'availableModules' => $availableModules,
            'plans' => $this->settings->billingPlans(),
        ]);
    }

    public function update(Organization $organization, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:active,suspended'],
            'plan' => ['required', 'string'],
            'is_lifetime' => ['nullable', 'boolean'],
            'subscription_expires_at' => ['nullable', 'date'],
            'allowed_modules' => ['nullable', 'array'],
            'allowed_modules.*' => ['string'],
        ]);

        $isLifetime = $request->boolean('is_lifetime') || $validated['plan'] === 'lifetime';

        $expiresAt = null;
        if (! $isLifetime && filled($validated['subscription_expires_at'])) {
            $expiresAt = Carbon::parse($validated['subscription_expires_at']);
        } elseif (! $isLifetime) {
            // If expiry is cleared, calculate default from plan
            $days = match ($validated['plan']) {
                'yearly' => 365,
                'half_yearly' => 180,
                default => 30,
            };
            $expiresAt = now()->addDays($days);
        }

        $organization->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'plan' => $isLifetime ? 'lifetime' : $validated['plan'],
            'is_lifetime' => $isLifetime,
            'subscription_expires_at' => $isLifetime ? null : $expiresAt,
            'allowed_modules' => $validated['allowed_modules'] ?? null,
        ]);

        return redirect()->route('admin.organizations.index')->with('flash_notify', [
            'type' => 'success',
            'message' => "Organization '{$organization->name}' updated successfully.",
        ]);
    }
}

