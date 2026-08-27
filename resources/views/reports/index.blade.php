<x-app-layout>
    <x-slot name="title">Reports</x-slot>

    @php($m = $metrics)

    <div class="space-y-6">
        <div class="flex items-center gap-2 flex-wrap">
            @foreach ($presets as $p)
                <a href="{{ route('whatsapp.reports.index', ['range' => $p['key']]) }}"
                   class="btn btn-sm {{ $range->key === $p['key'] ? 'btn-primary' : 'btn-ghost' }}">{{ $p['label'] }}</a>
            @endforeach
        </div>

        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Messages', number_format($m['messages']['total'])],
                ['Delivered', number_format($m['messages']['delivered'] + $m['messages']['read'])],
                ['Failed', number_format($m['messages']['failed'])],
                ['Delivery rate', $m['rates']['delivery'].'%'],
            ] as [$label, $value])
                <div class="card bg-base-100 border border-base-300">
                    <div class="card-body py-4">
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                        <div class="text-xs opacity-60">{{ $label }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Volume trend</h2>
                @include('partials.trend-chart', ['trend' => $m['trend']])
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <div class="p-3 border-b border-base-300 font-medium text-sm">Campaign performance</div>
            <table class="table" id="report-campaigns-table" data-datatable data-page-length="10" data-no-sort="6">
                <thead><tr><th>Campaign</th><th>Status</th><th>Recipients</th><th>Delivery</th><th>Read</th><th>Failed</th><th></th></tr></thead>
                <tbody>
                    @foreach ($campaigns as $row)
                        @php($c = $row['campaign'])
                        <tr class="hover">
                            <td><a class="link link-hover" href="{{ route('whatsapp.campaigns.report', $c) }}">{{ $c->name }}</a></td>
                            <td><span class="badge badge-sm badge-ghost">{{ ucfirst($c->status->value) }}</span></td>
                            <td class="tabular-nums" data-order="{{ $row['total'] }}">{{ number_format($row['total']) }}</td>
                            <td class="tabular-nums" data-order="{{ $row['delivery'] }}">{{ $row['delivery'] }}%</td>
                            <td class="tabular-nums" data-order="{{ $row['read'] }}">{{ $row['read'] }}%</td>
                            <td class="tabular-nums" data-order="{{ $row['failed'] }}">{{ number_format($row['failed']) }}</td>
                            <td class="text-right">
                                @if ($canExport)
                                    <a href="{{ route('whatsapp.campaigns.report.export', $c) }}" class="btn btn-xs btn-ghost"><i class="ti ti-download"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($canExport)
            <div class="text-sm">
                <a href="{{ route('whatsapp.contacts.export') }}" class="link"><i class="ti ti-download"></i> Export all contacts (CSV)</a>
            </div>
        @endif
    </div>
</x-app-layout>
