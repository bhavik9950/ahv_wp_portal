<x-app-layout>
    <x-slot name="title">Contacts</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::ContactManage->value))
    @php($canImport = auth()->user()->can(\App\Enums\Permission::ContactImport->value))
    @php($canExport = auth()->user()->can(\App\Enums\Permission::ContactExport->value))

    <div class="space-y-4">
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
                    <a href="{{ route('whatsapp.contacts.export') }}" data-turbo="false" class="btn btn-sm btn-outline"><i class="ti ti-download"></i> Export</a>
                @endif
                @if ($canImport)
                    <a href="{{ route('whatsapp.contacts.import.create') }}" class="btn btn-sm btn-outline"><i class="ti ti-file-upload"></i> Import CSV</a>
                @endif
                @if ($canManage)
                    <a href="{{ route('whatsapp.contacts.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus"></i> Add contact</a>
                @endif
            </div>
        </div>

        {{-- Bulk actions — bulk-select.js fills contact_ids[] on every data-bulk-ids form --}}
        @if ($canManage)
            <div data-bulk-bar hidden
                 class="rounded-box border border-primary/30 bg-primary/5 p-3 space-y-3">
                <div class="text-sm font-medium">
                    <span data-bulk-count>0</span> contact(s) selected
                </div>

                {{-- Group --}}
                <form method="POST" action="{{ route('whatsapp.groups.assign') }}" data-bulk-ids
                      class="flex flex-wrap items-center gap-2">
                    @csrf
                    <select name="group_id" class="select select-bordered select-sm">
                        <option value="">Choose a group…</option>
                        @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                    </select>
                    <span class="text-xs opacity-50">or</span>
                    <input name="new_group_name" maxlength="80" placeholder="new group name"
                           class="input input-bordered input-sm w-40">
                    <button name="action" value="add" class="btn btn-sm btn-primary">
                        <i class="ti ti-users-plus"></i> Add to group
                    </button>
                    <button name="action" value="remove" class="btn btn-sm btn-ghost">Remove from group</button>
                </form>

                {{-- Consent --}}
                <form method="POST" action="{{ route('whatsapp.contacts.bulk-opt-in') }}" data-bulk-ids
                      class="flex flex-wrap items-center gap-2"
                      data-confirm="Only mark contacts opted in if you actually hold their consent — WhatsApp bans numbers that send marketing to people who didn't opt in. Continue?">
                    @csrf
                    <button name="action" value="opt_in" class="btn btn-sm btn-outline btn-success">
                        <i class="ti ti-user-check"></i> Mark opted in
                    </button>
                    <button name="action" value="opt_out" class="btn btn-sm btn-outline btn-error">
                        <i class="ti ti-user-x"></i> Mark opted out
                    </button>
                    <span class="text-xs opacity-50">consent record — needed before a MARKETING campaign can reach them</span>
                </form>
            </div>
            @error('contact_ids')<p class="text-error text-xs">{{ $message }}</p>@enderror
            @error('group_id')<p class="text-error text-xs">{{ $message }}</p>@enderror
        @endif

        @if ($capped ?? false)
            <p class="text-xs opacity-60"><i class="ti ti-info-circle"></i> Showing the most recent {{ number_format($limit) }} contacts. Use an export for the full list.</p>
        @endif

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table" id="contacts-table" data-datatable
                   @if ($canManage) data-bulk @endif
                   data-order='[[{{ $canManage ? 5 : 4 }},"desc"]]' data-no-sort="{{ $canManage ? '0' : '' }}">
                <thead><tr>
                    @if ($canManage)<th class="w-8"><input type="checkbox" class="checkbox checkbox-sm js-bulk-all" title="Select all (every page)"></th>@endif
                    <th>Name</th><th>Phone</th><th>Opt-in</th><th>Groups</th><th>Added</th>
                </tr></thead>
                <tbody>
                    @foreach ($contacts as $c)
                        <tr class="hover">
                            @if ($canManage)
                                <td><input type="checkbox" class="checkbox checkbox-sm js-bulk-row" value="{{ $c->id }}"></td>
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
