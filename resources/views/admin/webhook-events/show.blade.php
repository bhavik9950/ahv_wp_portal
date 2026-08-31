<x-app-layout>
    <x-slot name="title">Webhook Event</x-slot>

    <div class="max-w-3xl space-y-4">
        <a href="{{ route('admin.webhook-events.index') }}" class="btn btn-sm btn-ghost">
            <i class="ti ti-arrow-left"></i> Back to events
        </a>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-sm">{{ $event->kind() }}</span>
                    @php($sm = ['processed'=>'badge-success','failed'=>'badge-error','processing'=>'badge-info','ignored'=>'badge-ghost'])
                    <span class="badge badge-sm {{ $sm[$event->status] ?? 'badge-warning' }}">{{ $event->status }}</span>
                    <span class="badge badge-sm {{ $event->signature_valid ? 'badge-success' : 'badge-error' }}">
                        signature {{ $event->signature_valid ? 'valid' : 'invalid' }}
                    </span>
                </div>

                <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-1 text-sm">
                    <dt class="opacity-60">Received</dt><dd>{{ $event->received_at?->format('d M Y, H:i:s') ?? '—' }}</dd>
                    <dt class="opacity-60">Processed</dt><dd>{{ $event->processed_at?->format('d M Y, H:i:s') ?? '—' }}</dd>
                    <dt class="opacity-60">Source</dt><dd>{{ $event->source }}</dd>
                    <dt class="opacity-60">Organization</dt><dd>{{ $event->organization_id ?? '—' }}</dd>
                    <dt class="opacity-60">Fingerprint</dt><dd class="font-mono text-xs break-all">{{ $event->event_fingerprint }}</dd>
                    <dt class="opacity-60">Summary</dt><dd>{{ $event->summary() }}</dd>
                </dl>

                @if ($event->error)
                    <div class="alert alert-error text-sm">
                        <i class="ti ti-x"></i><span class="break-all">{{ $event->error }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-sm">Payload</h2>
                <pre class="text-xs overflow-x-auto bg-base-200 rounded p-3 whitespace-pre-wrap break-words">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-sm">Headers</h2>
                <pre class="text-xs overflow-x-auto bg-base-200 rounded p-3 whitespace-pre-wrap break-words">{{ json_encode($event->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
</x-app-layout>
