<x-app-layout>
    <x-slot name="title">Webhook Event</x-slot>

    @php
        [$ki, $kc, $kbg] = match (true) {
            str_starts_with($event->kind(), 'inbound')  => ['ti-arrow-down-left', 'text-success', 'bg-success/10'],
            str_starts_with($event->kind(), 'status')   => ['ti-activity', 'text-info', 'bg-info/10'],
            str_contains($event->kind(), 'template')     => ['ti-template', 'text-warning', 'bg-warning/10'],
            default                                      => ['ti-webhook', 'text-base-content/50', 'bg-base-300/60'],
        };
        $sm = ['processed'=>'badge-success','failed'=>'badge-error','processing'=>'badge-info','ignored'=>'badge-ghost'];
    @endphp

    <div class="max-w-3xl space-y-4" x-data="{ payload: {{ json_encode(json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) }} }">
        <a href="{{ route('admin.webhook-events.index') }}" class="btn btn-sm btn-ghost">
            <i class="ti ti-arrow-left"></i> Back
        </a>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body gap-4">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg {{ $kbg }}">
                        <i class="ti {{ $ki }} {{ $kc }} text-xl"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="font-mono text-sm">{{ $event->kind() }}</div>
                        <div class="text-sm opacity-70 truncate">{{ $event->summary() }}</div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="badge badge-sm {{ $sm[$event->status] ?? 'badge-warning' }}">{{ $event->status }}</span>
                        <span class="text-xs {{ $event->signature_valid ? 'text-success' : 'text-error' }}">
                            <i class="ti {{ $event->signature_valid ? 'ti-shield-check' : 'ti-shield-x' }}"></i>
                            signature {{ $event->signature_valid ? 'valid' : 'invalid' }}
                        </span>
                    </div>
                </div>

                @if ($event->error)
                    <div class="alert alert-error text-sm">
                        <i class="ti ti-x"></i><span class="break-all font-mono text-xs">{{ $event->error }}</span>
                    </div>
                @endif

                <div class="grid sm:grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                    <div class="flex justify-between gap-2"><span class="opacity-50">Received</span><span>{{ $event->received_at?->format('d M Y, H:i:s') ?? '—' }}</span></div>
                    <div class="flex justify-between gap-2"><span class="opacity-50">Processed</span><span>{{ $event->processed_at?->format('d M Y, H:i:s') ?? '—' }}</span></div>
                    <div class="flex justify-between gap-2"><span class="opacity-50">Source</span><span>{{ $event->source }}</span></div>
                    <div class="flex justify-between gap-2"><span class="opacity-50">Organization</span><span>{{ $event->organization_id ?? '—' }}</span></div>
                    <div class="flex justify-between gap-2 sm:col-span-2"><span class="opacity-50">Fingerprint</span><span class="font-mono text-xs truncate">{{ $event->event_fingerprint }}</span></div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body gap-2">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-sm">Payload</h2>
                    <button type="button" class="btn btn-xs btn-ghost gap-1"
                            x-on:click="navigator.clipboard.writeText(payload); $el.querySelector('span').textContent = 'Copied'">
                        <i class="ti ti-copy"></i><span>Copy</span>
                    </button>
                </div>
                <pre class="text-xs leading-relaxed overflow-x-auto bg-base-200 rounded-lg p-3 max-h-[28rem]" x-text="payload"></pre>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body gap-2">
                <h2 class="card-title text-sm">Headers</h2>
                <pre class="text-xs overflow-x-auto bg-base-200 rounded-lg p-3">{{ json_encode($event->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>
