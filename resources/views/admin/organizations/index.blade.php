<x-app-layout>
    <x-slot name="title">Client Organizations</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="ti ti-building text-primary"></i> Client Organizations
                </h2>
                <p class="text-xs opacity-60">Manage tenant companies, subscription plans, validity periods, and enabled modules.</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('admin.organizations.index') }}" class="join">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search company..."
                           class="input input-bordered input-sm join-item w-48 sm:w-64">
                    <button type="submit" class="btn btn-sm btn-ghost join-item border border-base-300">
                        <i class="ti ti-search"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Status</th>
                        <th>Current Plan</th>
                        <th>Subscription Expiry</th>
                        <th>Members</th>
                        <th>WABA Accounts</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizations as $org)
                        <tr class="hover">
                            <td>
                                <div class="font-bold">{{ $org->name }}</div>
                                <div class="text-[0.7rem] opacity-50 font-mono">{{ $org->slug }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $org->isActive() ? 'badge-success' : 'badge-error' }} badge-xs font-semibold">
                                    {{ ucfirst($org->status) }}
                                </span>
                            </td>
                            <td>
                                @if ($org->is_lifetime)
                                    <span class="badge badge-primary badge-sm font-semibold gap-1">
                                        <i class="ti ti-infinity"></i> Lifetime Access
                                    </span>
                                @else
                                    <span class="badge badge-ghost badge-sm font-semibold">
                                        {{ ucfirst(str_replace('_', ' ', $org->plan)) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($org->is_lifetime)
                                    <span class="text-xs text-success font-medium">Never Expires</span>
                                @elseif ($org->hasActiveSubscription())
                                    <div class="text-xs font-medium text-success">
                                        {{ $org->subscription_expires_at?->format('d M Y') }}
                                        <span class="opacity-70">({{ $org->subscriptionDaysRemaining() }}d left)</span>
                                    </div>
                                @else
                                    <div class="text-xs font-bold text-error">
                                        Expired ({{ $org->subscription_expires_at?->format('d M Y') ?? 'Never activated' }})
                                    </div>
                                @endif
                            </td>
                            <td class="font-mono text-xs">{{ $org->users_count }}</td>
                            <td class="font-mono text-xs">{{ $org->whatsapp_business_accounts_count }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.organizations.edit', $org) }}" class="btn btn-outline btn-xs gap-1">
                                    <i class="ti ti-edit"></i> Manage Plan &amp; Modules
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 opacity-60">
                                No organizations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $organizations->links() }}
        </div>
    </div>
</x-app-layout>
