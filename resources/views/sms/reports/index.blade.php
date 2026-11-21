<x-app-layout>
    <x-slot name="title">SMS Reports</x-slot>
    <x-slot name="header">SMS Delivery Reports &amp; Analytics</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Delivery Reports (DLR)</h2>
                <p class="text-xs opacity-70">Carrier delivery confirmation and cost reconciliation across all SMS traffic.</p>
            </div>

            {{-- Date Range Filter --}}
            <div class="flex items-center gap-2 flex-wrap">
                @foreach ($presets as $p)
                    <a href="{{ route('sms.reports.index', ['range' => $p['key']]) }}"
                       class="btn btn-xs {{ $range->key === $p['key'] ? 'btn-primary' : 'btn-ghost border border-base-300' }}">
                        {{ $p['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- KPI Tiles --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card bg-base-100 border border-base-300 p-4 flex-row items-center gap-3">
                <i class="ti ti-messages text-2xl text-primary"></i>
                <div>
                    <div class="text-2xl font-bold font-mono">{{ number_format($summary['total']) }}</div>
                    <div class="text-xs opacity-60">Total Dispatched</div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 p-4 flex-row items-center gap-3">
                <i class="ti ti-checks text-2xl text-success"></i>
                <div>
                    <div class="text-2xl font-bold font-mono text-success">{{ number_format($summary['delivered']) }}</div>
                    <div class="text-xs opacity-60">Delivered</div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 p-4 flex-row items-center gap-3">
                <i class="ti ti-alert-triangle text-2xl text-error"></i>
                <div>
                    <div class="text-2xl font-bold font-mono text-error">{{ number_format($summary['failed']) }}</div>
                    <div class="text-xs opacity-60">Failed</div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 p-4 flex-row items-center gap-3">
                <i class="ti ti-wallet text-2xl text-warning"></i>
                <div>
                    <div class="text-2xl font-bold font-mono text-primary">₹{{ number_format($summary['cost'], 2) }}</div>
                    <div class="text-xs opacity-60">Total Billed</div>
                </div>
            </div>
        </div>

        {{-- Detailed Message Logs Table --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="p-4 border-b border-base-300 flex items-center justify-between">
                <h3 class="font-bold text-sm">Recent Dispatch Activity</h3>
                <span class="text-xs opacity-60">Last 50 messages in range</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Mobile</th>
                            <th>Header</th>
                            <th>DLT Template</th>
                            <th>Status</th>
                            <th>Cost</th>
                            <th>Dispatched At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLogs as $log)
                            <tr class="hover">
                                <td class="font-mono text-xs font-semibold">+{{ $log->to_phone }}</td>
                                <td><span class="badge badge-sm badge-outline font-mono">{{ $log->sender_id }}</span></td>
                                <td class="text-xs font-mono opacity-80">{{ $log->dlt_template_id ?? 'DIRECT' }}</td>
                                <td>
                                    <span class="badge badge-xs {{ $log->status === 'delivered' ? 'badge-success' : ($log->status === 'failed' ? 'badge-error' : 'badge-info') }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td class="font-mono text-xs">₹{{ number_format($log->cost, 4) }}</td>
                                <td class="text-xs opacity-75 whitespace-nowrap">{{ ($log->sent_at ?? $log->created_at)?->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 opacity-60 text-xs">
                                    No SMS dispatch records found in this date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
