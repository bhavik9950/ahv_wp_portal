@php
    $user = auth()->user();
    $org = app(\App\Support\CurrentOrganization::class)->resolve();
    $isSmsSubscribed = $user?->isSuperAdmin() || ($org && $org->hasChannel('sms'));
@endphp

<x-app-layout>
    <x-slot name="title">SMS Campaigns</x-slot>
    <x-slot name="header">SMS Campaigns</x-slot>

    <div class="space-y-6">
        @if (! $isSmsSubscribed)
            <div class="alert alert-warning border border-warning/40 shadow-sm flex items-start gap-3">
                <i class="ti ti-lock text-xl shrink-0 mt-0.5 text-warning"></i>
                <div class="flex-1 text-xs">
                    <span class="font-bold">Subscription Required:</span> You are viewing SMS Campaigns in preview mode. Launching campaigns requires an active SMS service subscription.
                </div>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Broadcast Campaigns</h2>
                <p class="text-xs opacity-70">Bulk SMS broadcasts dispatched to contact groups and custom audience lists.</p>
            </div>
            @if ($isSmsSubscribed)
                <a href="{{ route('sms.campaigns.create') }}" class="btn btn-primary btn-sm gap-2">
                    <i class="ti ti-plus"></i> Launch SMS Campaign
                </a>
            @else
                <button type="button" onclick="alert('You are not subscribed to the SMS service. Please contact AH&V Software administrator to subscribe.');" class="btn btn-ghost border border-base-300 opacity-60 btn-sm gap-2" title="Subscription required">
                    <i class="ti ti-lock"></i> Launch SMS Campaign
                </button>
            @endif
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Header</th>
                            <th>Audience</th>
                            <th>Delivered</th>
                            <th>Cost</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($campaigns as $camp)
                            <tr class="hover">
                                <td class="font-bold">{{ $camp->name }}</td>
                                <td><span class="badge badge-sm badge-outline font-mono">{{ $camp->sender?->sender_id ?? 'DIRECT' }}</span></td>
                                <td>{{ number_format($camp->audience_count) }}</td>
                                <td class="text-success font-semibold">{{ number_format($camp->delivered_count) }}</td>
                                <td class="font-mono text-xs">₹{{ number_format($camp->total_cost, 2) }}</td>
                                <td>
                                    <span class="badge badge-xs {{ $camp->status === 'completed' ? 'badge-success' : 'badge-ghost' }}">
                                        {{ ucfirst($camp->status) }}
                                    </span>
                                </td>
                                <td class="text-xs opacity-75">{{ $camp->created_at->format('d M, H:i') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('sms.campaigns.show', $camp) }}" class="btn btn-ghost btn-xs gap-1">
                                        <i class="ti ti-chart-bar"></i> Report
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 opacity-60 text-xs">
                                    No SMS campaigns found. Click "Launch SMS Campaign" to start your first bulk delivery.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($campaigns->hasPages())
                <div class="p-3 border-t border-base-300">
                    {{ $campaigns->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
