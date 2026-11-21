<x-app-layout>
    <x-slot name="title">Reports</x-slot>

    @php($m = $metrics)

    <div class="space-y-6" @if ($range->key === 'today') data-auto-refresh="15" @endif>
        {{-- Range Presets --}}
        <div class="flex items-center gap-2 flex-wrap">
            @foreach ($presets as $p)
                <a href="{{ route('whatsapp.reports.index', ['range' => $p['key']]) }}"
                   class="btn btn-sm {{ $range->key === $p['key'] ? 'btn-primary' : 'btn-ghost' }}">{{ $p['label'] }}</a>
            @endforeach
        </div>

        {{-- Top KPI Headline Tiles --}}
        <div class="grid gap-4 grid-cols-2 lg:grid-cols-5">
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body py-4 flex-row items-center gap-3">
                    <i class="ti ti-send text-2xl text-primary"></i>
                    <div>
                        <div class="text-2xl font-semibold">{{ number_format($m['messages']['total']) }}</div>
                        <div class="text-xs opacity-60">Outbound Messages</div>
                    </div>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body py-4 flex-row items-center gap-3">
                    <i class="ti ti-checks text-2xl text-info"></i>
                    <div>
                        <div class="text-2xl font-semibold">{{ number_format($m['messages']['delivered'] + $m['messages']['read']) }}</div>
                        <div class="text-xs opacity-60">Delivered ({{ $m['rates']['delivery'] }}%)</div>
                    </div>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body py-4 flex-row items-center gap-3">
                    <i class="ti ti-eye-check text-2xl text-success"></i>
                    <div>
                        <div class="text-2xl font-semibold">{{ number_format($m['messages']['read']) }}</div>
                        <div class="text-xs opacity-60">Read ({{ $m['rates']['read'] }}%)</div>
                    </div>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body py-4 flex-row items-center gap-3">
                    <i class="ti ti-message-reply text-2xl text-warning"></i>
                    <div>
                        <div class="text-2xl font-semibold">{{ number_format($m['messages']['inbound'] ?? 0) }}</div>
                        <div class="text-xs opacity-60">Inbound Replies</div>
                    </div>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body py-4 flex-row items-center gap-3">
                    <i class="ti ti-alert-triangle text-2xl text-error"></i>
                    <div>
                        <div class="text-2xl font-semibold">{{ number_format($m['messages']['failed']) }}</div>
                        <div class="text-xs opacity-60">Failed ({{ $m['rates']['failure'] }}%)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Trend Chart --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Volume trend</h2>
                @include('partials.trend-chart', ['trend' => $m['trend']])
            </div>
        </div>

        {{-- Campaign Performance Table --}}
        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <div class="p-3 border-b border-base-300 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="ti ti-rocket text-primary"></i>
                    <span class="font-bold text-sm">Campaign performance</span>
                </div>
                <span class="badge badge-sm badge-ghost">{{ count($campaigns) }} campaign(s)</span>
            </div>
            <table class="table" id="report-campaigns-table" data-datatable data-page-length="10" data-no-sort="6">
                <thead><tr><th>Campaign</th><th>Status</th><th>Recipients</th><th>Delivery</th><th>Read</th><th>Failed</th><th></th></tr></thead>
                <tbody>
                    @foreach ($campaigns as $row)
                        @php($c = $row['campaign'])
                        <tr class="hover">
                            <td><a class="link link-hover font-medium" href="{{ route('whatsapp.campaigns.report', $c) }}">{{ $c->name }}</a></td>
                            <td><span class="badge badge-sm {{ $c->status->value === 'completed' ? 'badge-success' : ($c->status->value === 'processing' ? 'badge-info' : 'badge-ghost') }}">{{ ucfirst($c->status->value) }}</span></td>
                            <td class="tabular-nums" data-order="{{ $row['total'] }}">{{ number_format($row['total']) }}</td>
                            <td class="tabular-nums font-medium text-info" data-order="{{ $row['delivery'] }}">{{ $row['delivery'] }}%</td>
                            <td class="tabular-nums font-medium text-success" data-order="{{ $row['read'] }}">{{ $row['read'] }}%</td>
                            <td class="tabular-nums text-error" data-order="{{ $row['failed'] }}">{{ number_format($row['failed']) }}</td>
                            <td class="text-right">
                                @if ($canExport)
                                    <a href="{{ route('whatsapp.campaigns.report.export', $c) }}" data-turbo="false" class="btn btn-xs btn-ghost" title="Export CSV"><i class="ti ti-download"></i></a>
                                @endif
                                <a href="{{ route('whatsapp.campaigns.report', $c) }}" class="btn btn-xs btn-outline btn-primary ml-1">Report →</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Direct & Normal Messages Section --}}
        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <div class="p-3 border-b border-base-300 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="ti ti-message-share text-accent"></i>
                    <span class="font-bold text-sm">Direct & Normal Messages (Test & Single Sends)</span>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="badge badge-sm badge-outline">Sent: {{ number_format($directSummary['total']) }}</span>
                    <span class="badge badge-sm badge-info badge-outline">Delivered: {{ number_format($directSummary['delivered']) }}</span>
                    <span class="badge badge-sm badge-success badge-outline">Read: {{ number_format($directSummary['read']) }}</span>
                    @if ($directSummary['failed'] > 0)
                        <span class="badge badge-sm badge-error badge-outline">Failed: {{ number_format($directSummary['failed']) }}</span>
                    @endif
                </div>
            </div>
            <table class="table" id="report-direct-messages-table" data-datatable data-page-length="10" data-no-sort="5">
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Type / Template</th>
                        <th>Status</th>
                        <th>Sent At</th>
                        <th>Delivered / Read</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($directMessages as $dm)
                        @php($statusClass = match ($dm->status->value) {
                            'read' => 'badge-success',
                            'delivered' => 'badge-info',
                            'sent' => 'badge-primary',
                            'failed' => 'badge-error',
                            default => 'badge-ghost',
                        })
                        <tr class="hover">
                            <td>
                                <div class="font-mono text-xs">+{{ ltrim($dm->to_phone, '+') }}</div>
                                @if ($dm->contact)<div class="text-[0.7rem] opacity-60">{{ $dm->contact->name }}</div>@endif
                            </td>
                            <td>
                                @if ($dm->template)
                                    <span class="badge badge-xs badge-outline">{{ $dm->template->name }}</span>
                                @else
                                    <span class="text-xs opacity-70">{{ ucfirst($dm->type->value ?? 'Text') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-sm {{ $statusClass }} gap-1 font-semibold">
                                    {{ ucfirst($dm->status->value) }}
                                </span>
                                @if ($dm->error_message)
                                    <span class="text-[0.7rem] text-error block max-w-xs truncate" title="{{ $dm->error_message }}">{{ $dm->error_message }}</span>
                                @endif
                            </td>
                            <td class="text-xs text-nowrap opacity-75">
                                {{ ($dm->sent_at ?? $dm->created_at)?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                            </td>
                            <td class="text-xs text-nowrap">
                                @if ($dm->read_at)
                                    <span class="text-success flex items-center gap-1" title="Read at {{ $dm->read_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}">
                                        <i class="ti ti-eye-check"></i> {{ $dm->read_at->timezone('Asia/Kolkata')->format('h:i A') }}
                                    </span>
                                @elseif ($dm->delivered_at)
                                    <span class="text-info flex items-center gap-1" title="Delivered at {{ $dm->delivered_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}">
                                        <i class="ti ti-checks"></i> {{ $dm->delivered_at->timezone('Asia/Kolkata')->format('h:i A') }}
                                    </span>
                                @else
                                    <span class="opacity-40">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('whatsapp.conversations.show', ltrim($dm->to_phone, '+')) }}" class="btn btn-xs btn-ghost gap-1">
                                    <i class="ti ti-message-circle-2"></i> Chat
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Inbound Messages Section --}}
        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <div class="p-3 border-b border-base-300 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="ti ti-message-reply text-warning"></i>
                    <span class="font-bold text-sm">Recent Inbound Replies (From Customers)</span>
                </div>
                <a href="{{ route('whatsapp.conversations.index') }}" class="btn btn-xs btn-outline btn-warning">View All Chats →</a>
            </div>
            <table class="table" id="report-inbound-messages-table" data-datatable data-page-length="10" data-no-sort="3">
                <thead>
                    <tr>
                        <th>From Phone</th>
                        <th>Message</th>
                        <th>Received At</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inboundMessages as $im)
                        <tr class="hover">
                            <td>
                                <div class="font-mono text-xs">+{{ ltrim($im->to_phone, '+') }}</div>
                                @if ($im->contact)<div class="text-[0.7rem] opacity-60">{{ $im->contact->name }}</div>@endif
                            </td>
                            <td class="max-w-md">
                                <span class="text-xs truncate block">
                                    {{ $im->bodyText() ?? data_get($im->payload, 'text.body') ?? ($im->type->value ?? 'Message') }}
                                </span>
                            </td>
                            <td class="text-xs text-nowrap opacity-75">
                                {{ $im->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                            </td>
                            <td class="text-right">
                                <a href="{{ route('whatsapp.conversations.show', ltrim($im->to_phone, '+')) }}" class="btn btn-xs btn-ghost gap-1 text-primary">
                                    <i class="ti ti-message-circle-2"></i> Open Chat
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($canExport)
            <div class="text-sm">
                <a href="{{ route('whatsapp.contacts.export') }}" data-turbo="false" class="link"><i class="ti ti-download"></i> Export all contacts (CSV)</a>
            </div>
        @endif
    </div>
</x-app-layout>
