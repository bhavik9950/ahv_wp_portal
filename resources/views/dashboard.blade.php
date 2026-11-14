<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    @php($m = $metrics)

    <div class="space-y-6" @if ($range->key === 'today' && ! $selectedCampaignId) data-auto-refresh="60" @endif>
        {{-- Unified Date Range & Campaign Filter Bar --}}
        <form method="GET" action="{{ route('dashboard') }}" id="dashboard_filter_form" class="bg-base-100 p-3 rounded-2xl border border-base-300 shadow-xs flex items-center justify-between gap-3 flex-wrap">
            <input type="hidden" name="range" id="filter_range_key" value="{{ $range->key }}">

            <div class="flex items-center gap-2 flex-wrap">
                @foreach ($presets as $p)
                    <button type="button"
                            onclick="setDashboardPreset('{{ $p['key'] }}')"
                            class="btn btn-sm {{ $range->key === $p['key'] ? 'btn-primary' : 'btn-ghost' }}">
                        {{ $p['label'] }}
                    </button>
                @endforeach

                <div class="flex items-center gap-1.5 pl-1 border-l border-base-300">
                    <input type="date" name="from" id="filter_from" value="{{ $m['range']['from'] }}"
                           class="input input-bordered input-sm"
                           onchange="document.getElementById('filter_range_key').value='custom'">
                    <span class="opacity-50 text-xs">–</span>
                    <input type="date" name="to" id="filter_to" value="{{ $m['range']['to'] }}"
                           class="input input-bordered input-sm"
                           onchange="document.getElementById('filter_range_key').value='custom'">
                    <button type="submit"
                            onclick="document.getElementById('filter_range_key').value='custom'"
                            class="btn btn-sm btn-outline">
                        Apply
                    </button>
                </div>
            </div>

            {{-- Campaign dropdown filter --}}
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <i class="ti ti-target opacity-50 text-base"></i>
                    <select name="campaign_id" id="filter_campaign_id"
                            onchange="submitDashboardFilter()"
                            class="select select-bordered select-sm max-w-xs text-xs font-medium">
                        <option value="">All Campaigns (Account-wide)</option>
                        @foreach ($campaigns as $camp)
                            <option value="{{ $camp->id }}" @selected($selectedCampaignId === $camp->id)>
                                {{ $camp->name }} ({{ ucfirst($camp->status->value) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($selectedCampaignId || $range->key !== 'last_7_days')
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-ghost text-error gap-1 text-xs" title="Reset all filters to default">
                        <i class="ti ti-rotate-clockwise"></i> Reset
                    </a>
                @endif
            </div>
        </form>

        <script>
            function submitDashboardFilter() {
                var form = document.getElementById('dashboard_filter_form');
                if (!form) return;
                form.classList.add('opacity-70', 'pointer-events-none');
                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    form.submit();
                }
            }

            function setDashboardPreset(presetKey) {
                var form = document.getElementById('dashboard_filter_form');
                if (!form) return;
                document.getElementById('filter_range_key').value = presetKey;
                submitDashboardFilter();
            }
        </script>

        {{-- Campaign Focus Banner --}}
        @if ($selectedCampaign)
            <div class="card bg-gradient-to-r from-primary/10 via-primary/5 to-base-100 border-2 border-primary/30 p-4 shadow-xs rounded-2xl">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="badge badge-primary font-bold gap-1 text-xs">
                                <i class="ti ti-filter"></i> Filtered by campaign:
                            </span>
                            <span class="font-extrabold text-base">{{ $selectedCampaign->name }}</span>
                            <span class="badge badge-sm {{ $selectedCampaign->status->value === 'completed' ? 'badge-success' : ($selectedCampaign->status->value === 'processing' ? 'badge-info' : 'badge-ghost') }}">
                                {{ ucfirst($selectedCampaign->status->value) }}
                            </span>
                        </div>
                        <div class="text-xs opacity-70 flex items-center gap-3 flex-wrap">
                            <span><i class="ti ti-template"></i> Template: <strong>{{ $selectedCampaign->template?->name ?? 'Custom' }}</strong></span>
                            <span>&bull;</span>
                            <span><i class="ti ti-calendar"></i> Filter Range: <strong>{{ $m['range']['from'] }} → {{ $m['range']['to'] }} ({{ $range->label() }})</strong></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('whatsapp.campaigns.report', $selectedCampaign) }}" class="btn btn-sm btn-primary gap-1">
                            <i class="ti ti-chart-dots"></i> View Detailed Report →
                        </a>
                        <a href="{{ route('dashboard', ['range' => $range->key]) }}" class="btn btn-sm btn-outline gap-1 text-xs" title="Show all campaigns for this date range">
                            <i class="ti ti-x"></i> Show All Campaigns
                        </a>
                    </div>
                </div>

                {{-- Campaign Lifetime Audience Snapshot --}}
                @if ($campaignTotals)
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 pt-3 mt-3 border-t border-primary/15 text-xs">
                        <div class="p-2.5 rounded-xl bg-base-100/90 border border-base-200">
                            <div class="opacity-60 text-[0.7rem]">Total Campaign Audience</div>
                            <div class="font-bold text-base">{{ number_format($campaignTotals['total'] ?? 0) }}</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-base-100/90 border border-base-200">
                            <div class="opacity-60 text-[0.7rem]">Campaign Sent</div>
                            <div class="font-bold text-base text-primary">{{ number_format($campaignTotals['sent'] ?? 0) }}</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-base-100/90 border border-base-200">
                            <div class="opacity-60 text-[0.7rem]">Campaign Delivered</div>
                            <div class="font-bold text-base text-info">{{ number_format(($campaignTotals['delivered'] ?? 0) + ($campaignTotals['read'] ?? 0)) }}</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-base-100/90 border border-base-200">
                            <div class="opacity-60 text-[0.7rem]">Campaign Read</div>
                            <div class="font-bold text-base text-success">{{ number_format($campaignTotals['read'] ?? 0) }}</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-base-100/90 border border-base-200">
                            <div class="opacity-60 text-[0.7rem]">Campaign Failed</div>
                            <div class="font-bold text-base text-error">{{ number_format($campaignTotals['failed'] ?? 0) }}</div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Headline tiles --}}
        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
            @if ($selectedCampaign && $campaignTotals)
                {{-- Campaign-specific tiles --}}
                @foreach ([
                    ['Messages in Range', number_format($m['messages']['total']), 'ti-messages', 'text-primary', null],
                    ['Delivery rate (range)', $m['rates']['delivery'].'%', 'ti-checks', 'text-success', null],
                    ['Read rate (range)', $m['rates']['read'].'%', 'ti-eye', 'text-info', null],
                    ['Failure rate (range)', $m['rates']['failure'].'%', 'ti-alert-triangle', $m['rates']['failure'] > 5 ? 'text-error' : 'opacity-60', null],
                    ['Campaign Audience', number_format($campaignTotals['total'] ?? 0), 'ti-users', 'text-secondary', route('whatsapp.campaigns.report', $selectedCampaign)],
                    ['Campaign Delivered', number_format(($campaignTotals['delivered'] ?? 0) + ($campaignTotals['read'] ?? 0)), 'ti-check', 'text-info', route('whatsapp.campaigns.report', $selectedCampaign)],
                    ['Campaign Read', number_format($campaignTotals['read'] ?? 0), 'ti-eye-check', 'text-success', route('whatsapp.campaigns.report', $selectedCampaign)],
                    ['Campaign Failed', number_format($campaignTotals['failed'] ?? 0), 'ti-alert-circle', ($campaignTotals['failed'] ?? 0) > 0 ? 'text-error' : 'opacity-60', route('whatsapp.campaigns.report', $selectedCampaign)],
                ] as [$label, $value, $icon, $color, $link])
                    <a @if ($link) href="{{ $link }}" @endif
                       class="card bg-base-100 border border-base-300 {{ $link ? 'hover:border-primary transition' : 'pointer-events-none' }}">
                        <div class="card-body py-4 flex-row items-center gap-4">
                            <i class="ti {{ $icon }} text-2xl {{ $color }}"></i>
                            <div>
                                <div class="text-2xl font-semibold">{{ $value }}</div>
                                <div class="text-xs opacity-60">{{ $label }}</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            @else
                {{-- Account-wide tiles --}}
                @foreach ([
                    ['Messages', number_format($m['messages']['total']), 'ti-messages', 'text-primary', null],
                    ['Delivery rate', $m['rates']['delivery'].'%', 'ti-checks', 'text-success', null],
                    ['Read rate', $m['rates']['read'].'%', 'ti-eye', 'text-info', null],
                    ['Failure rate', $m['rates']['failure'].'%', 'ti-alert-triangle', $m['rates']['failure'] > 5 ? 'text-error' : 'opacity-60', null],
                    ['Active campaigns', number_format($m['campaigns']['active']), 'ti-rocket', 'text-secondary', route('whatsapp.campaigns.index')],
                    ['Contacts', number_format($m['contacts']['total']), 'ti-address-book', 'opacity-70', route('whatsapp.contacts.index')],
                    ['Opted-in', number_format($m['contacts']['opted_in']), 'ti-user-check', 'text-success', null],
                    ['Scheduled', number_format($m['campaigns']['scheduled'] + $m['scheduled_messages']), 'ti-clock', 'opacity-70', null],
                ] as [$label, $value, $icon, $color, $link])
                    <a @if ($link) href="{{ $link }}" @endif
                       class="card bg-base-100 border border-base-300 {{ $link ? 'hover:border-primary transition' : 'pointer-events-none' }}">
                        <div class="card-body py-4 flex-row items-center gap-4">
                            <i class="ti {{ $icon }} text-2xl {{ $color }}"></i>
                            <div>
                                <div class="text-2xl font-semibold">{{ $value }}</div>
                                <div class="text-xs opacity-60">{{ $label }}</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            @endif
        </div>

        {{-- Trend --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-base">Message trend</h2>
                    @if ($selectedCampaign)
                        <span class="text-xs badge badge-ghost">Campaign: {{ $selectedCampaign->name }}</span>
                    @endif
                </div>
                @include('partials.trend-chart', ['trend' => $m['trend']])
            </div>
        </div>

        {{-- Delivery funnel + status breakdown --}}
        <div class="grid lg:grid-cols-2 gap-4">
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body">
                    <h2 class="card-title text-base">Delivery funnel ({{ $m['range']['from'] }} → {{ $m['range']['to'] }})</h2>
                    @php($sent = $m['messages']['sent'] + $m['messages']['delivered'] + $m['messages']['read'])
                    @php($delivered = $m['messages']['delivered'] + $m['messages']['read'])
                    @php($read = $m['messages']['read'])
                    @php($base = max(1, $sent))
                    @foreach ([['Sent', $sent, 'bg-primary'], ['Delivered', $delivered, 'bg-info'], ['Read', $read, 'bg-success']] as [$label, $v, $bar])
                        <div class="mt-2">
                            <div class="flex justify-between text-xs mb-1"><span>{{ $label }}</span><span class="tabular-nums">{{ number_format($v) }}</span></div>
                            <div class="w-full bg-base-200 rounded h-3">
                                <div class="{{ $bar }} h-3 rounded" style="width: {{ round($v / $base * 100, 1) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300">
                <div class="card-body">
                    <h2 class="card-title text-base">By status</h2>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <tbody>
                                @foreach (['sent' => 'Sent', 'delivered' => 'Delivered', 'read' => 'Read', 'failed' => 'Failed', 'pending' => 'Pending', 'queued' => 'Queued', 'skipped' => 'Skipped'] as $k => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td class="text-right tabular-nums">{{ number_format($m['messages'][$k] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs opacity-50 mt-2">
                        Templates: {{ $m['templates']['approved'] }} approved · {{ $m['templates']['pending'] }} pending · {{ $m['templates']['rejected'] }} rejected
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
