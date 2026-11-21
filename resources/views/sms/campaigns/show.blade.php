<x-app-layout>
    <x-slot name="title">{{ $campaign->name }} · SMS Report</x-slot>
    <x-slot name="header">SMS Campaign Report</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold">{{ $campaign->name }}</h2>
                    <span class="badge badge-sm badge-success uppercase font-bold">{{ $campaign->status }}</span>
                </div>
                <p class="text-xs opacity-70">
                    Header: <strong>{{ $campaign->sender?->sender_id ?? 'DIRECT' }}</strong> · 
                    DLT Template: <strong>{{ $campaign->template?->name ?? 'Custom' }}</strong> · 
                    Launched: {{ $campaign->created_at->format('d M Y, H:i:s') }}
                </p>
            </div>
            <a href="{{ route('sms.campaigns.index') }}" class="btn btn-outline btn-sm gap-1">
                <i class="ti ti-arrow-left"></i> All Campaigns
            </a>
        </div>

        {{-- KPI Tiles --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="card bg-base-100 border border-base-300 p-4">
                <div class="opacity-60 text-xs">Total Audience</div>
                <div class="text-2xl font-bold font-mono">{{ number_format($campaign->audience_count) }}</div>
            </div>
            <div class="card bg-base-100 border border-base-300 p-4">
                <div class="opacity-60 text-xs">Delivered</div>
                <div class="text-2xl font-bold font-mono text-success">{{ number_format($campaign->delivered_count) }}</div>
            </div>
            <div class="card bg-base-100 border border-base-300 p-4">
                <div class="opacity-60 text-xs">Failed</div>
                <div class="text-2xl font-bold font-mono text-error">{{ number_format($campaign->failed_count) }}</div>
            </div>
            <div class="card bg-base-100 border border-base-300 p-4">
                <div class="opacity-60 text-xs">Total Billed</div>
                <div class="text-2xl font-bold font-mono text-primary">₹{{ number_format($campaign->total_cost, 2) }}</div>
            </div>
        </div>

        {{-- Message Body Snapshot --}}
        <div class="card bg-base-100 border border-base-300 p-4 text-xs font-mono">
            <div class="opacity-60 text-[0.7rem] uppercase font-bold mb-1">Delivered Message Text</div>
            <div class="p-3 bg-base-200/50 rounded-lg">{{ $campaign->message_body }}</div>
        </div>

        {{-- Recipient Logs Table --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="p-4 border-b border-base-300">
                <h3 class="font-bold text-sm">Recipient Delivery Logs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Mobile Number</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Cost</th>
                            <th>Dispatched At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($messages as $msg)
                            <tr class="hover">
                                <td class="font-mono text-xs">+{{ $msg->to_phone }}</td>
                                <td class="text-xs">{{ $msg->contact?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-xs {{ $msg->status === 'delivered' ? 'badge-success' : ($msg->status === 'failed' ? 'badge-error' : 'badge-info') }}">
                                        {{ ucfirst($msg->status) }}
                                    </span>
                                </td>
                                <td class="font-mono text-xs">₹{{ number_format($msg->cost, 4) }}</td>
                                <td class="text-xs opacity-75">{{ $msg->sent_at?->format('d M, H:i:s') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 opacity-60 text-xs">No recipient records.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($messages->hasPages())
                <div class="p-3 border-t border-base-300">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
