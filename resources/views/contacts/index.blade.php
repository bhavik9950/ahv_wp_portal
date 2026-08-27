<x-app-layout>
    <x-slot name="title">Contacts</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::ContactManage->value))
    @php($canImport = auth()->user()->can(\App\Enums\Permission::ContactImport->value))
    @php($canExport = auth()->user()->can(\App\Enums\Permission::ContactExport->value))

    <div class="space-y-4" x-data="{ selected: [] }">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <form method="GET" class="flex flex-wrap gap-2">
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, phone or email…" class="input input-bordered input-sm">
                <select name="opt_in" class="select select-bordered select-sm">
                    <option value="">Any opt-in</option>
                    @foreach ($optInStatuses as $s)
                        <option value="{{ $s->value }}" @selected(($filters['opt_in'] ?? '') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
                <select name="group" class="select select-bordered select-sm">
                    <option value="">Any group</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}" @selected(($filters['group'] ?? '') === $g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm">Filter</button>
            </form>

            <div class="flex gap-2">
                @if ($canExport)
                    <a href="{{ route('whatsapp.contacts.export', $filters) }}" class="btn btn-sm btn-outline"><i class="ti ti-download"></i> Export</a>
                @endif
                @if ($canImport)
                    <a href="{{ route('whatsapp.contacts.import.create') }}" class="btn btn-sm btn-outline"><i class="ti ti-file-upload"></i> Import CSV</a>
                @endif
                @if ($canManage)
                    <a href="{{ route('whatsapp.contacts.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus"></i> Add contact</a>
                @endif
            </div>
        </div>

        {{-- Bulk group assignment --}}
        @if ($canManage && $groups->isNotEmpty())
            <form method="POST" action="{{ route('whatsapp.groups.assign') }}" class="flex items-center gap-2" x-show="selected.length">
                @csrf
                <template x-for="id in selected" :key="id"><input type="hidden" name="contact_ids[]" :value="id"></template>
                <span class="text-sm opacity-70" x-text="selected.length + ' selected'"></span>
                <select name="group_id" class="select select-bordered select-sm">
                    @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                </select>
                <button name="action" value="add" class="btn btn-sm">Add to group</button>
                <button name="action" value="remove" class="btn btn-sm btn-ghost">Remove</button>
            </form>
        @endif

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table">
                <thead><tr>
                    @if ($canManage)<th></th>@endif
                    <th>Name</th><th>Phone</th><th>Opt-in</th><th>Groups</th><th>Added</th>
                </tr></thead>
                <tbody>
                    @forelse ($contacts as $c)
                        <tr class="hover">
                            @if ($canManage)
                                <td><input type="checkbox" class="checkbox checkbox-sm" x-model="selected" value="{{ $c->id }}"></td>
                            @endif
                            <td><a class="link link-hover" href="{{ route('whatsapp.contacts.show', $c) }}">{{ $c->name ?: '—' }}</a></td>
                            <td class="font-mono">+{{ $c->phone_e164 }}</td>
                            <td>
                                @php($m = [\App\Enums\OptInStatus::OptedIn->value => 'badge-success', \App\Enums\OptInStatus::OptedOut->value => 'badge-error'])
                                <span class="badge badge-sm {{ $m[$c->opt_in_status->value] ?? 'badge-ghost' }}">{{ $c->opt_in_status->label() }}</span>
                            </td>
                            <td class="text-xs">{{ $c->groups->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td class="text-xs opacity-60">{{ $c->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center opacity-60 py-6">No contacts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $contacts->links() }}
    </div>
</x-app-layout>
