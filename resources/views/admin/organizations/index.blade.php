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
                <button type="button" onclick="document.getElementById('create-org-modal').showModal()" class="btn btn-primary btn-sm gap-1">
                    <i class="ti ti-plus"></i> New Organization
                </button>
                <form method="GET" action="{{ route('admin.organizations.index') }}" class="join">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search company..."
                           class="input input-bordered input-sm join-item w-44 sm:w-56">
                    <button type="submit" class="btn btn-sm btn-ghost join-item border border-base-300">
                        <i class="ti ti-search"></i>
                    </button>
                </form>
            </div>
        </div>

        @if (session('created_credentials'))
            @php($cred = session('created_credentials'))
            <div class="alert alert-success shadow-sm border border-success/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                <div class="space-y-1">
                    <div class="font-bold text-sm flex items-center gap-1.5">
                        <i class="ti ti-circle-check text-base"></i> Client Organization Provisioned Successfully!
                    </div>
                    <div class="opacity-90">
                        Share these login credentials with the client:
                    </div>
                    <div class="font-mono bg-base-100/80 p-2.5 rounded-lg border border-base-300 space-y-1 mt-1 text-[11px] select-all">
                        <div><strong>Company:</strong> {{ $cred['org_name'] }}</div>
                        <div><strong>Login URL:</strong> <a href="{{ url('/login') }}" target="_blank" class="link link-primary">{{ url('/login') }}</a></div>
                        <div><strong>Email:</strong> {{ $cred['email'] }}</div>
                        <div><strong>Password:</strong> <span class="badge badge-neutral badge-xs font-mono font-bold">{{ $cred['password'] }}</span></div>
                    </div>
                </div>
            </div>
        @endif

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

    {{-- Create Organization Modal --}}
    <dialog id="create-org-modal" class="modal">
        <div class="modal-box max-w-lg">
            <div class="flex items-center justify-between pb-3 border-b border-base-300">
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i class="ti ti-building-plus text-primary"></i> Create Client Organization &amp; Admin
                </h3>
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost">✕</button>
                </form>
            </div>

            <form method="POST" action="{{ route('admin.organizations.store') }}" class="space-y-4 pt-4">
                @csrf

                <div class="space-y-1">
                    <label class="label text-xs font-semibold py-0">Company / Organization Name <span class="text-error">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. Acme Retailers" class="input input-bordered input-sm w-full">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="label text-xs font-semibold py-0">Client Admin Name <span class="text-error">*</span></label>
                        <input type="text" name="admin_name" required placeholder="e.g. Rahul Sharma" class="input input-bordered input-sm w-full">
                    </div>
                    <div class="space-y-1">
                        <label class="label text-xs font-semibold py-0">Admin Email <span class="text-error">*</span></label>
                        <input type="email" name="admin_email" required placeholder="client@example.com" class="input input-bordered input-sm w-full">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="label text-xs font-semibold py-0">Login Password (leave empty to auto-generate)</label>
                    <input type="text" name="password" placeholder="Auto-generated secure password" class="input input-bordered input-sm w-full font-mono">
                </div>

                <div class="space-y-1">
                    <label class="label text-xs font-semibold py-0">Subscription Plan <span class="text-error">*</span></label>
                    <select name="plan" class="select select-bordered select-sm w-full">
                        <option value="monthly">Monthly Plan (30 days)</option>
                        <option value="half_yearly">6 Months Plan (180 days)</option>
                        <option value="yearly">1 Year Plan (365 days)</option>
                        <option value="lifetime">Lifetime License (Never Expires)</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="label text-xs font-semibold py-0">Meta WABA ID (Optional - can be configured later)</label>
                    <input type="text" name="waba_id" placeholder="e.g. 102938475610293" class="input input-bordered input-sm w-full font-mono">
                    <span class="text-[11px] opacity-60 block">Client or super-admin can enter full API tokens later under WhatsApp &gt; Settings.</span>
                </div>

                <div class="modal-action pt-2">
                    <button type="button" onclick="document.getElementById('create-org-modal').close()" class="btn btn-sm btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary gap-1">
                        <i class="ti ti-check"></i> Create Organization &amp; User
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</x-app-layout>

