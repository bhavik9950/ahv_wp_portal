<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\System\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

