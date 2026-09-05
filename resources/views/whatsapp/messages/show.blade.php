<x-app-layout>
    <x-slot name="title">Message</x-slot>

    <div class="max-w-2xl space-y-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('whatsapp.messages.index') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Messages</a>
            <a href="{{ route('whatsapp.conversations.show', $message->to_phone) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-message-circle-2"></i> Open chat
            </a>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <div><span class="opacity-60">Direction:</span> {{ ucfirst($message->direction) }}</div>
                    <div><span class="opacity-60">Type:</span> {{ $message->type->value }}</div>
                    <div><span class="opacity-60">{{ $message->direction === 'inbound' ? 'From' : 'To' }}:</span> <span class="font-mono">{{ $message->to_phone }}</span></div>
                    <div><span class="opacity-60">Contact:</span> {{ $message->contact?->name ?? '—' }}</div>
                    <div><span class="opacity-60">Template:</span> {{ $message->template?->name ?? '—' }}</div>
                    <div><span class="opacity-60">WAMID:</span> <span class="font-mono text-xs">{{ $message->wamid ?? '—' }}</span></div>
                    <div><span class="opacity-60">Phone number:</span> {{ $message->phoneNumber?->display_phone_number ?? '—' }}</div>
                    <div><span class="opacity-60">Campaign:</span> {{ $message->campaign_id ? 'yes' : '—' }}</div>
                </div>

                @if ($message->error_message)
                    <div class="alert alert-error text-sm mt-3">
                        <i class="ti ti-x"></i>
                        <span>{{ $message->error_message }} @if ($message->error_code)(code {{ $message->error_code }})@endif</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Status timeline</h2>
                <ul class="timeline timeline-vertical timeline-compact">
                    @forelse ($message->statusEvents as $e)
                        <li>
                            @if (! $loop->first)<hr>@endif
                            <div class="timeline-start text-xs opacity-60">{{ $e->occurred_at?->format('d M H:i:s') }}</div>
                            <div class="timeline-middle">
                                <i class="ti {{ $e->status === 'failed' ? 'ti-circle-x text-error' : 'ti-circle-check text-success' }}"></i>
                            </div>
                            <div class="timeline-end">
                                <span class="font-medium">{{ ucfirst($e->status) }}</span>
                                @if ($e->error_message)<div class="text-xs text-error">{{ $e->error_message }}</div>@endif
                            </div>
                            @if (! $loop->last)<hr>@endif
                        </li>
                    @empty
                        <li><div class="timeline-end opacity-60 text-sm">No status events yet.</div></li>
                    @endforelse
                </ul>
            </div>
        </div>

        <details class="collapse collapse-arrow border border-base-300 bg-base-100">
            <summary class="collapse-title text-sm font-medium">Payload sent to Meta</summary>
            <div class="collapse-content">
                <pre class="text-xs overflow-x-auto">{{ json_encode($message->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </details>
    </div>
</x-app-layout>
