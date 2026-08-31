<x-app-layout>
    <x-slot name="title">Webhook Events</x-slot>

    @php
        $live = (bool) request('live');
        $fresh = $lastAt && $lastAt->gt(now()->subSeconds(60));

        // icon + colour per event kind
        $badge = function (string $kind): array {
            return match (true) {
                str_starts_with($kind, 'inbound')        => ['ti-arrow-down-left', 'text-success', 'bg-success/10'],
                $kind === 'status: read'                  => ['ti-checks', 'text-success', 'bg-success/10'],
                $kind === 'status: delivered'             => ['ti-check', 'text-info', 'bg-info/10'],
                $kind === 'status: sent'                  => ['ti-send-2', 'text-base-content/60', 'bg-base-300/60'],
                $kind === 'status: failed'                => ['ti-alert-triangle', 'text-error', 'bg-error/10'],
                str_starts_with($kind, 'status')          => ['ti-activity', 'text-base-content/60', 'bg-base-300/60'],
                str_contains($kind, 'template')           => ['ti-template', 'text-warning', 'bg-warning/10'],
                default                                    => ['ti-webhook', 'text-base-content/50', 'bg-base-300/60'],
            };
        };
    @endphp

    <div class="space-y-4" @if ($live) data-auto-refresh="15" @endif>

        {{-- header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm">
                @if ($fresh)
                    <span class="inline-flex items-center gap-1.5 text-success">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-success opacity-75 animate-ping"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-success"></span>
                        </span>
                        Receiving events
                    </span>
                @else
                    <span class="text-base-content/50">
                        <i class="ti ti-clock"></i>
                        Last event {{ $lastAt?->diffForHumans() ?? 'never' }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.webhook-events.index') }}" class="btn btn-xs btn-ghost gap-1">
                    <i class="ti ti-refresh"></i> Refresh
                </a>
                @if ($live)
                    <a href="{{ route('admin.webhook-events.index') }}" class="btn btn-xs btn-primary gap-1">
                        <span class="loading loading-ring loading-xs"></span> Live · stop
                    </a>
                @else
                    <a href="{{ route('admin.webhook-events.index', ['live' => 1]) }}" class="btn btn-xs btn-ghost gap-1">
                        <i class="ti ti-player-play"></i> Go live
                    </a>
                @endif
            </div>
        </div>

        {{-- stat strip --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach ([
                ['Events', $stats['total'], 'ti-webhook', 'opacity-70'],
                ['Inbound', $stats['inbound'], 'ti-arrow-down-left', 'text-success'],
                ['Status updates', $stats['statuses'], 'ti-activity', 'text-info'],
                ['Failed', $stats['failed'], 'ti-alert-triangle', $stats['failed'] ? 'text-error' : 'opacity-40'],
                ['Bad signature', $stats['bad_signature'], 'ti-shield-x', $stats['bad_signature'] ? 'text-error' : 'opacity-40'],
            ] as [$label, $value, $icon, $color])
                <div class="card bg-base-100 border border-base-300">
                    <div class="card-body p-3 flex-row items-center gap-3">
                        <i class="ti {{ $icon }} text-xl {{ $color }}"></i>
                        <div>
                            <div class="text-lg font-semibold leading-none">{{ number_format($value) }}</div>
                            <div class="text-[0.7rem] uppercase tracking-wide opacity-50 mt-0.5">{{ $label }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($pending > 0)
            <div class="alert alert-warning text-sm">
                <i class="ti ti-alert-triangle"></i>
                <span>
                    <strong>{{ $pending }}</strong> event(s) received but not processed — the queue worker isn't running:
                    <code class="bg-base-300 px-1 rounded">php artisan queue:work --queue=whatsapp-webhook,whatsapp-high,default</code>
                </span>
            </div>
        @endif

        @if ($events->isEmpty())
            <div class="alert text-sm">
                <i class="ti ti-info-circle"></i>
                <span>No events yet. Once Meta's callback points at
                    <code class="bg-base-300 px-1 rounded">/api/webhooks/whatsapp</code>
                    and the <code>messages</code> field is subscribed, deliveries appear here.</span>
            </div>
        @endif

        {{-- table --}}
        <div class="flex flex-wrap items-end gap-2">
            <x-dt-filter label="Status" target="#webhook-events-table" :col="2" class="min-w-[9rem]">
                <option value="">Any status</option>
                @foreach (['received','processing','processed','failed','ignored'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </x-dt-filter>
            <x-dt-filter label="Kind" target="#webhook-events-table" col-name="Kind" match="contains" class="min-w-[9rem]">
                <option value="">Any kind</option>
                <option value="inbound">Inbound message</option>
                <option value="status">Status update</option>
                <option value="template">Template update</option>
            </x-dt-filter>
        </div>

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table table-sm" id="webhook-events-table" data-datatable data-order='[[0,"desc"]]' data-no-sort="4">
                <thead><tr>
                    <th>Received</th><th>Kind</th><th>Status</th><th class="text-center">Sig</th><th>Details</th>
                </tr></thead>
                <tbody>
                    @foreach ($events as $e)
                        @php([$ki, $kc, $kbg] = $badge($e->kind()))
                        <tr class="hover cursor-pointer" data-href="{{ route('admin.webhook-events.show', $e) }}">
                            <td class="whitespace-nowrap text-xs opacity-70" data-order="{{ $e->received_at?->timestamp ?? 0 }}"
                                title="{{ $e->received_at?->format('d M Y H:i:s') }}">
                                {{ $e->received_at?->diffForHumans() ?? '—' }}
                            </td>
                            <td>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded {{ $kbg }}">
                                        <i class="ti {{ $ki }} {{ $kc }} text-sm"></i>
                                    </span>
                                    <span class="font-mono text-xs">{{ $e->kind() }}</span>
                                </span>
                            </td>
                            <td>
                                @php($sm = ['processed'=>'badge-success','failed'=>'badge-error','processing'=>'badge-info','ignored'=>'badge-ghost'])
                                <span class="badge badge-sm {{ $sm[$e->status] ?? 'badge-warning' }} badge-outline">{{ $e->status }}</span>
                            </td>
                            <td class="text-center">
                                @if ($e->signature_valid)
                                    <i class="ti ti-shield-check text-success" title="Signature valid"></i>
                                @else
                                    <i class="ti ti-shield-x text-error" title="Signature invalid"></i>
                                @endif
                            </td>
                            <td class="text-xs max-w-md truncate">
                                {{ $e->summary() }}
                                @if ($e->error)
                                    <span class="text-error">· {{ \Illuminate\Support\Str::limit($e->error, 60) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-xs opacity-40">
            Showing the latest {{ number_format($events->count()) }} deliveries · click a row for the raw payload
            @if ($live) · refreshing every 15s @endif
        </p>
    </div>
</x-app-layout>
