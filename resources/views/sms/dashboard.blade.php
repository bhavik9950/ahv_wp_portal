@php
    $user = auth()->user();
    $org = app(\App\Support\CurrentOrganization::class)->resolve();
    $isSmsSubscribed = $user?->isSuperAdmin() || ($org && $org->hasChannel('sms'));
@endphp

<x-app-layout>
    <x-slot name="title">SMS Dashboard</x-slot>
    <x-slot name="header">SMS Gateway Dashboard</x-slot>

    <div class="space-y-6">
        @if (! $isSmsSubscribed)
            <div class="alert alert-warning border border-warning/40 shadow-sm flex items-start gap-3">
                <i class="ti ti-lock text-2xl shrink-0 mt-0.5 text-warning"></i>
                <div class="flex-1 text-xs">
                    <div class="font-bold text-sm">SMS Gateway Preview Mode</div>
                    <span class="opacity-90">Your organization is currently not subscribed to the SMS service. You can review the interface, DLT headers, templates, and analytics, but sending SMS campaigns and submitting templates is locked. Contact AH&V Software administrator to subscribe.</span>
                </div>
            </div>
        @endif

        {{-- Suite Banner & Wallet Snapshot --}}
        <div class="card bg-gradient-to-r from-base-100 via-base-100 to-warning/5 border border-base-300 shadow-sm p-4 sm:p-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-warning/10 text-warning border border-warning/20 flex items-center justify-center shrink-0">
                        <i class="ti ti-message-dots text-2xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold">SMS Cloud Gateway</h2>
                            @if ($isSmsSubscribed)
                                <span class="badge badge-success badge-sm font-semibold text-white">Active</span>
                            @else
                                <span class="badge badge-warning badge-sm font-semibold">Not Subscribed · Preview</span>
                            @endif
                        </div>
                        <p class="text-xs opacity-70">Enterprise SMS Gateway · Fast Transactional &amp; Promotional Delivery</p>
                    </div>
                </div>

                {{-- Wallet & Action Buttons --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="px-3.5 py-1.5 rounded-xl bg-base-200 border border-base-300 text-right">
                        <div class="text-[0.65rem] uppercase tracking-wider opacity-60 font-semibold">Prepaid Balance</div>
                        <div class="text-base font-bold text-success font-mono">₹{{ number_format($stats['wallet_balance'], 2) }}</div>
                    </div>
                    @if ($isSmsSubscribed)
                        <a href="{{ route('sms.quick.create') }}" class="btn btn-sm btn-outline gap-1 text-xs">
                            <i class="ti ti-send"></i> Quick SMS
                        </a>
                        <a href="{{ route('sms.campaigns.create') }}" class="btn btn-sm btn-primary gap-1 text-xs">
                            <i class="ti ti-rocket"></i> New Campaign
                        </a>
                    @else
                        <button type="button" onclick="alert('You are not subscribed to the SMS service. Please contact AH&V Software administrator to subscribe.');" class="btn btn-sm btn-outline opacity-60 gap-1 text-xs" title="Subscription required">
                            <i class="ti ti-lock"></i> Quick SMS
                        </button>
                        <button type="button" onclick="alert('You are not subscribed to the SMS service. Please contact AH&V Software administrator to subscribe.');" class="btn btn-sm btn-ghost border border-base-300 opacity-60 gap-1 text-xs" title="Subscription required">
                            <i class="ti ti-lock"></i> New Campaign
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Metric Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-4 flex-row items-center gap-3">
                    <i class="ti ti-messages text-2xl text-primary"></i>
                    <div>
                        <div class="text-2xl font-bold font-mono">{{ number_format($stats['total_messages']) }}</div>
                        <div class="text-xs opacity-60">Total SMS Dispatched</div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-4 flex-row items-center gap-3">
                    <i class="ti ti-checks text-2xl text-success"></i>
                    <div>
                        <div class="text-2xl font-bold font-mono text-success">{{ number_format($stats['delivered']) }}</div>
                        <div class="text-xs opacity-60">Delivered Successfully</div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-4 flex-row items-center gap-3">
                    <i class="ti ti-alert-triangle text-2xl text-error"></i>
                    <div>
                        <div class="text-2xl font-bold font-mono text-error">{{ number_format($stats['failed']) }}</div>
                        <div class="text-xs opacity-60">Failed / Rejected</div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body p-4 flex-row items-center gap-3">
                    <i class="ti ti-certificate text-2xl text-warning"></i>
                    <div>
                        <div class="text-2xl font-bold font-mono">{{ number_format($stats['active_senders']) }}</div>
                        <div class="text-xs opacity-60">Approved Sender Headers</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Content Grid: Recent Campaigns & Fast Shortcuts --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Recent Campaigns (2 cols) --}}
            <div class="lg:col-span-2 card bg-base-100 border border-base-300">
                <div class="p-4 border-b border-base-300 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-history text-primary"></i>
                        <h3 class="font-bold text-sm">Recent SMS Campaigns</h3>
                    </div>
                    <a href="{{ route('sms.campaigns.index') }}" class="btn btn-ghost btn-xs text-primary">View All →</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Campaign Name</th>
                                <th>Header</th>
                                <th>Audience</th>
                                <th>Delivered</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentCampaigns as $camp)
                                <tr class="hover">
                                    <td class="font-semibold">{{ $camp->name }}</td>
                                    <td><span class="badge badge-sm badge-outline font-mono">{{ $camp->sender?->sender_id ?? 'DIRECT' }}</span></td>
                                    <td>{{ number_format($camp->audience_count) }}</td>
                                    <td class="text-success font-semibold">{{ number_format($camp->delivered_count) }}</td>
                                    <td>
                                        <span class="badge badge-xs {{ $camp->status === 'completed' ? 'badge-success' : 'badge-ghost' }}">
                                            {{ ucfirst($camp->status) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('sms.campaigns.show', $camp) }}" class="btn btn-ghost btn-xs">Report</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-6 opacity-60 text-xs">
                                        No SMS campaigns yet. Create your first campaign to begin broadcasting.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DLT & Cloud Setup Quick Status (1 col) --}}
            <div class="card bg-base-100 border border-base-300 p-5 space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-base-200">
                    <i class="ti ti-shield-check text-success text-lg"></i>
                    <h3 class="font-bold text-sm">DLT Compliance Status</h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="p-3 rounded-lg bg-base-200/60 border border-base-300 flex items-center justify-between">
                        <div>
                            <div class="font-bold">Active Headers (Sender IDs)</div>
                            <div class="opacity-60 text-[0.7rem]">{{ $stats['active_senders'] }} registered</div>
                        </div>
                        <a href="{{ route('sms.headers.index') }}" class="btn btn-xs btn-outline">Manage</a>
                    </div>

                    <div class="p-3 rounded-lg bg-base-200/60 border border-base-300 flex items-center justify-between">
                        <div>
                            <div class="font-bold">DLT Content Templates</div>
                            <div class="opacity-60 text-[0.7rem]">{{ $stats['templates_count'] }} approved</div>
                        </div>
                        <a href="{{ route('sms.templates.index') }}" class="btn btn-xs btn-outline">Manage</a>
                    </div>

                    <div class="p-3 rounded-lg bg-base-200/60 border border-base-300">
                        <div class="flex items-center justify-between">
                            <span class="font-bold">Billing Rate</span>
                            <span class="font-mono text-primary font-bold">₹{{ number_format($stats['rate_per_sms'], 4) }} / SMS</span>
                        </div>
                        <p class="text-[0.7rem] opacity-60 mt-1">Deducted from wallet on dispatch.</p>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="{{ route('sms.quick.create') }}" class="btn btn-block btn-primary btn-sm gap-2">
                        <i class="ti ti-send-2"></i> Send Instant Test SMS
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
