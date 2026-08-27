<x-app-layout>
    <x-slot name="title">{{ $campaign->name }} · Report</x-slot>
    @if ($live)
        <x-slot name="head"><meta http-equiv="refresh" content="10"></x-slot>
    @endif

    @php($canLaunch = auth()->user()->can(\App\Enums\Permission::CampaignLaunch->value))
    @php($canExport = auth()->user()->can(\App\Enums\Permission::ReportExport->value))

    <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-semibold">{{ $campaign->name }}</h1>
                <p class="text-xs opacity-60">
                    {{ $campaign->template?->name }} · from {{ $campaign->phoneNumber?->display_phone_number ?? '—' }}
                    @if ($campaign->scheduled_at) · scheduled {{ $campaign->scheduled_at->timezone($campaign->timezone)->format('d M Y H:i') }} ({{ $campaign->timezone }}) @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                @php($m = ['completed'=>'badge-success','processing'=>'badge-info','paused'=>'badge-warning','failed'=>'badge-error','cancelled'=>'badge-ghost','scheduled'=>'badge-ghost'])
                <span class="badge {{ $m[$campaign->status->value] ?? 'badge-ghost' }}">{{ ucfirst($campaign->status->value) }}</span>

                @if ($canLaunch)
                    @if ($campaign->status === \App\Enums\CampaignStatus::Processing)
                        <form method="POST" action="{{ route('whatsapp.campaigns.pause', $campaign) }}">@csrf<button class="btn btn-xs btn-warning">Pause</button></form>
                    @elseif ($campaign->status === \App\Enums\CampaignStatus::Paused)
                        <form method="POST" action="{{ route('whatsapp.campaigns.resume', $campaign) }}">@csrf<button class="btn btn-xs btn-success">Resume</button></form>
                    @endif
                    @if (in_array($campaign->status->value, ['processing','paused','scheduled']))
                        <form method="POST" action="{{ route('whatsapp.campaigns.cancel', $campaign) }}" data-confirm="Cancel this campaign? Unsent recipients are skipped.">
                            @csrf<button class="btn btn-xs btn-error btn-outline">Cancel</button>
                        </form>
                    @endif
                @endif
                @if ($canExport)
                    <a href="{{ route('whatsapp.campaigns.report.export', $campaign) }}" class="btn btn-xs btn-outline"><i class="ti ti-download"></i> CSV</a>
                @endif
            </div>
        </div>

        {{-- Totals --}}
        <div class="grid gap-3 grid-cols-2 sm:grid-cols-4 lg:grid-cols-7">
            @foreach ([
                'total' => ['Recipients', 'text-base-content'],
                'sent' => ['Sent', 'text-info'],
                'delivered' => ['Delivered', 'text-info'],
                'read' => ['Read', 'text-success'],
                'failed' => ['Failed', 'text-error'],
                'skipped' => ['Skipped', 'text-warning'],
                'opted_out' => ['Opted out', 'text-warning'],
            ] as $key => [$label, $color])
                <div class="card bg-base-100 border border-base-300">
                    <div class="card-body p-3">
                        <div class="text-xl font-semibold {{ $color }}">{{ number_format($totals[$key] ?? 0) }}</div>
                        <div class="text-xs opacity-60">{{ $label }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex gap-6 text-sm">
            <span>Delivery rate: <strong>{{ $rates['delivery'] }}%</strong></span>
            <span>Read rate: <strong>{{ $rates['read'] }}%</strong></span>
            <span>Failure rate: <strong>{{ $rates['failure'] }}%</strong></span>
        </div>

        {{-- Recipient detail --}}
        <div class="flex flex-wrap items-end gap-2">
            <x-dt-filter label="Recipient status" target="#recipients-table" :col="2">
                <option value="">All statuses</option>
                @foreach (\App\Enums\CampaignRecipientStatus::cases() as $s)
                    <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                @endforeach
            </x-dt-filter>
        </div>

        @if ($recipientsCapped ?? false)
            <p class="text-xs opacity-60"><i class="ti ti-info-circle"></i> Showing {{ number_format($recipientLimit) }} recipients. Export the CSV for the full list.</p>
        @endif

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table table-sm" id="recipients-table" data-datatable data-order='[[2,"asc"]]'>
                <thead><tr><th>Phone</th><th>Contact</th><th>Status</th><th>Error</th><th>Attempts</th></tr></thead>
                <tbody>
                    @foreach ($recipients as $r)
                        <tr>
                            <td class="font-mono">+{{ $r->phone_e164 }}</td>
                            <td>{{ $r->contact?->name ?? '—' }}</td>
                            <td>
                                @php($rm = ['read'=>'badge-success','delivered'=>'badge-info','sent'=>'badge-ghost','failed'=>'badge-error','opted_out'=>'badge-warning','skipped'=>'badge-warning'])
                                <span class="badge badge-sm {{ $rm[$r->status->value] ?? 'badge-ghost' }}">{{ $r->status->value }}</span>
                            </td>
                            <td class="text-xs opacity-70">{{ $r->error_message ?? $r->skip_reason ?? '' }}</td>
                            <td data-order="{{ $r->attempts }}">{{ $r->attempts }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
