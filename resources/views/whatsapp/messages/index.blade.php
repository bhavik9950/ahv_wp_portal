<x-app-layout>
    <x-slot name="title">Messages</x-slot>

    <div class="space-y-4">
        <form method="GET" class="flex flex-wrap gap-2">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search number…" class="input input-bordered input-sm">
            <select name="status" class="select select-bordered select-sm">
                <option value="">Any status</option>
                @foreach (['pending','queued','sent','delivered','read','failed','skipped'] as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="direction" class="select select-bordered select-sm">
                <option value="">Any direction</option>
                <option value="outbound" @selected(($filters['direction'] ?? '') === 'outbound')>Outbound</option>
                <option value="inbound" @selected(($filters['direction'] ?? '') === 'inbound')>Inbound</option>
            </select>
            <button class="btn btn-sm">Filter</button>
        </form>

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table">
                <thead><tr><th>To / From</th><th>Type</th><th>Template</th><th>Status</th><th>Sent</th></tr></thead>
                <tbody>
                    @forelse ($messages as $m)
                        <tr class="hover cursor-pointer" data-href="{{ route('whatsapp.messages.show', $m) }}">
                            <td class="font-mono">
                                <a class="link link-hover" href="{{ route('whatsapp.messages.show', $m) }}">{{ $m->to_phone }}</a>
                                @if ($m->direction === 'inbound')<span class="badge badge-xs badge-ghost ml-1">in</span>@endif
                            </td>
                            <td>{{ $m->type->value }}</td>
                            <td>{{ $m->template?->name ?? '—' }}</td>
                            <td>
                                @php($map = ['read'=>'badge-success','delivered'=>'badge-info','sent'=>'badge-ghost','failed'=>'badge-error'])
                                <span class="badge badge-sm {{ $map[$m->status->value] ?? 'badge-ghost' }}">{{ $m->status->value }}</span>
                            </td>
                            <td class="text-xs opacity-60">{{ $m->sent_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center opacity-60 py-6">No messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $messages->links() }}
    </div>
</x-app-layout>
