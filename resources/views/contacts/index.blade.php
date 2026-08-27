<x-app-layout>
    <x-slot name="title">Contacts</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::ContactManage->value))
    @php($canImport = auth()->user()->can(\App\Enums\Permission::ContactImport->value))
    @php($canExport = auth()->user()->can(\App\Enums\Permission::ContactExport->value))

    <div class="space-y-4" x-data="{ selected: [] }">
        <div class="flex items-end justify-between flex-wrap gap-3">
            <div class="flex flex-wrap items-end gap-2">
                <x-dt-filter label="Opt-in" target="#contacts-table" col-name="Opt-in">
                    <option value="">Any opt-in</option>
                    @foreach ($optInStatuses as $s)
                        <option value="{{ $s->label() }}">{{ $s->label() }}</option>
                    @endforeach
                </x-dt-filter>
                <x-dt-filter label="Group" target="#contacts-table" col-name="Groups" match="contains">
                    <option value="">Any group</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->name }}">{{ $g->name }}</option>
                    @endforeach
                </x-dt-filter>
            </div>

            <div class="flex gap-2">
                @if ($canExport)
                    <a href="{{ route('whatsapp.contacts.export') }}" class="btn btn-sm btn-outline"><i class="ti ti-download"></i> Export</a>
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

        @if ($capped ?? false)
            <p class="text-xs opacity-60"><i class="ti ti-info-circle"></i> Showing the most recent {{ number_format($limit) }} contacts. Use an export for the full list.</p>
        @endif

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table" id="contacts-table" data-datatable data-order='[[{{ $canManage ? 5 : 4 }},"desc"]]' data-no-sort="{{ $canManage ? '0' : '' }}">
                <thead><tr>
                    @if ($canManage)<th></th>@endif
                    <th>Name</th><th>Phone</th><th>Opt-in</th><th>Groups</th><th>Added</th>
                </tr></thead>
                <tbody>
                    @foreach ($contacts as $c)
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
                            <td class="text-xs opacity-60" data-order="{{ $c->created_at?->timestamp ?? 0 }}">{{ $c->created_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
