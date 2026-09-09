<x-app-layout>
    <x-slot name="title">{{ $conversation->contact?->name ?? '+'.$conversation->wa_phone }}</x-slot>

    @php($canManage = auth()->user()->can('manage-bot'))

    <div class="max-w-2xl mx-auto space-y-3">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('whatsapp.assistant.conversations') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Conversations</a>
            @if ($canManage)
                <div class="flex gap-2">
                    @if ($conversation->mode->value === 'bot')
                        <form method="POST" action="{{ route('whatsapp.assistant.conversation.mode', $conversation) }}">@csrf
                            <input type="hidden" name="mode" value="human">
                            <button class="btn btn-sm btn-warning btn-outline"><i class="ti ti-hand-stop"></i> Take over</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('whatsapp.assistant.conversation.mode', $conversation) }}">@csrf
                            <input type="hidden" name="mode" value="bot">
                            <button class="btn btn-sm btn-success btn-outline"><i class="ti ti-robot"></i> Hand back to bot</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary font-semibold">
                {{ mb_strtoupper(mb_substr($conversation->contact?->name ?? '#', 0, 1)) }}
            </span>
            <div>
                <div class="font-medium">{{ $conversation->contact?->name ?? 'Unknown contact' }}</div>
                <div class="text-xs opacity-60 font-mono">+{{ $conversation->wa_phone }}</div>
            </div>
            <span class="badge badge-sm ml-auto {{ ['bot'=>'badge-success','human'=>'badge-warning','paused'=>'badge-ghost'][$conversation->mode->value] ?? 'badge-ghost' }}">{{ $conversation->mode->value }}</span>
        </div>

        <div class="card bg-base-200/50 border border-base-300">
            <div class="card-body gap-2 p-4">
                @forelse ($conversation->messages as $m)
                    <div class="flex {{ $m->role === 'assistant' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-3 py-2 text-sm shadow-sm whitespace-pre-line
                                    {{ $m->role === 'assistant' ? 'bg-primary text-primary-content rounded-br-sm' : 'bg-base-100 rounded-bl-sm' }}">
                            {{ $m->content }}
                            <div class="text-[0.65rem] mt-1 {{ $m->role === 'assistant' ? 'text-primary-content/70' : 'opacity-50' }}">
                                {{ $m->created_at?->format('d M H:i') }}@if ($m->role === 'assistant' && $m->tokens_out) · {{ $m->tokens_in + $m->tokens_out }} tok @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm opacity-60 text-center py-4">No messages recorded yet.</p>
                @endforelse
            </div>
        </div>

        <a href="{{ route('whatsapp.conversations.show', $conversation->wa_phone) }}" class="link link-hover text-xs opacity-60">
            <i class="ti ti-message-circle-2"></i> Open the full WhatsApp chat
        </a>
    </div>
</x-app-layout>
