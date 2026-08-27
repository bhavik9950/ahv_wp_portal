<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    @php($m = $metrics)

    <div class="space-y-6" @if ($range->key === 'today') data-auto-refresh="60" @endif>
        {{-- Date range --}}
        <div class="flex items-center gap-2 flex-wrap">
            @foreach ($presets as $p)
                <a href="{{ route('dashboard', ['range' => $p['key']]) }}"
                   class="btn btn-sm {{ $range->key === $p['key'] ? 'btn-primary' : 'btn-ghost' }}">{{ $p['label'] }}</a>
            @endforeach
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-1">
                <input type="hidden" name="range" value="custom">
                <input type="date" name="from" value="{{ $m['range']['from'] }}" class="input input-bordered input-sm">
                <span class="opacity-50">–</span>
                <input type="date" name="to" value="{{ $m['range']['to'] }}" class="input input-bordered input-sm">
                <button class="btn btn-sm">Apply</button>
            </form>
        </div>

        {{-- Headline tiles --}}
        <div class="grid gap-4 grid-cols-2 lg:grid-cols-4">
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
        </div>

        {{-- Trend --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Message trend</h2>
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
