<x-app-layout>
    <x-slot name="title">Assistant Conversations</x-slot>

    <div class="space-y-4">
        <div class="alert alert-info bg-info/10 text-xs border border-info/20">
            <i class="ti ti-sparkles text-info"></i>
            <span><strong>Private Preview / Coming Soon:</strong> Autonomous AI WhatsApp Chatbot is currently in early preview. Contact AHV support if you wish to activate custom AI LLM agents on your WABA account.</span>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm opacity-70">Every WhatsApp thread the assistant has seen. "Human" rows need a person to reply.</p>
            <a href="{{ route('whatsapp.assistant.settings') }}" class="btn btn-sm btn-ghost"><i class="ti ti-adjustments-alt"></i> Settings</a>
        </div>

        <div class="flex flex-wrap items-end gap-2">
            <x-dt-filter label="Mode" target="#assistant-conversations-table" :col="1">
                <option value="">Any mode</option>
                <option value="bot">Bot</option>
                <option value="human">Human</option>
                <option value="paused">Paused</option>
            </x-dt-filter>
        </div>

        @if ($conversations->isEmpty())
            <div class="alert text-sm"><i class="ti ti-info-circle"></i><span>No conversations yet.</span></div>
        @else
            <div class="card bg-base-100 border border-base-300 overflow-x-auto">
                <table class="table" id="assistant-conversations-table" data-datatable data-order='[[3,"desc"]]'>
                    <thead><tr><th>Contact</th><th>Mode</th><th>Replies today</th><th>Last message</th></tr></thead>
                    <tbody>
                        @foreach ($conversations as $c)
                            <tr class="hover cursor-pointer" data-href="{{ route('whatsapp.assistant.conversation', $c) }}">
                                <td>
                                    <a class="link link-hover" href="{{ route('whatsapp.assistant.conversation', $c) }}">
                                        {{ $c->contact?->name ?? '+'.$c->wa_phone }}
                                    </a>
                                    @if ($c->handoff_reason)<div class="text-xs text-warning">{{ $c->handoff_reason }}</div>@endif
                                </td>
                                <td>
                                    @php($mm = ['bot'=>'badge-success','human'=>'badge-warning','paused'=>'badge-ghost'])
                                    <span class="badge badge-sm {{ $mm[$c->mode->value] ?? 'badge-ghost' }}">{{ $c->mode->value }}</span>
                                </td>
                                <td>{{ $c->repliesUsedToday() }}</td>
                                <td class="text-xs opacity-60" data-order="{{ $c->last_inbound_at?->timestamp ?? 0 }}">{{ $c->last_inbound_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
