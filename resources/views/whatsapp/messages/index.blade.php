<x-app-layout>
    <x-slot name="title">Messages</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-end gap-2">
            <x-dt-filter label="Direction" target="#messages-table" :col="1">
                <option value="">Any direction</option>
                <option value="outbound">Outbound</option>
                <option value="inbound">Inbound</option>
            </x-dt-filter>
            <x-dt-filter label="Status" target="#messages-table" :col="4">
                <option value="">Any status</option>
                @foreach (['pending','queued','sent','delivered','read','failed','skipped'] as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </x-dt-filter>
        </div>

        @if ($capped ?? false)
            <p class="text-xs opacity-60"><i class="ti ti-info-circle"></i> Showing the most recent {{ number_format($limit) }} messages. Use Reports or an export for the full history.</p>
        @endif

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table" id="messages-table" data-datatable data-order='[[5,"desc"]]'>
                <thead><tr><th>To / From</th><th>Direction</th><th>Type</th><th>Template</th><th>Status</th><th>Sent</th></tr></thead>
                <tbody>
                    @foreach ($messages as $m)
                        <tr class="hover cursor-pointer" data-href="{{ route('whatsapp.messages.show', $m) }}">
                            <td class="font-mono">
                                <a class="link link-hover" href="{{ route('whatsapp.messages.show', $m) }}">{{ $m->to_phone }}</a>
                                <a href="{{ route('whatsapp.conversations.show', $m->to_phone) }}" class="ml-1" title="Open chat" onclick="event.stopPropagation()">
                                    <i class="ti ti-message-circle-2 text-xs opacity-60"></i>
                                </a>
                            </td>
                            <td>{{ ucfirst($m->direction) }}</td>
                            <td>{{ $m->type->value }}</td>
                            <td>{{ $m->template?->name ?? '—' }}</td>
                            <td>
                                @php($map = ['read'=>'badge-success','delivered'=>'badge-info','sent'=>'badge-ghost','failed'=>'badge-error'])
                                <span class="badge badge-sm {{ $map[$m->status->value] ?? 'badge-ghost' }}">{{ $m->status->value }}</span>
                            </td>
                            <td class="text-xs opacity-60" data-order="{{ $m->sent_at?->timestamp ?? 0 }}">{{ $m->sent_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
