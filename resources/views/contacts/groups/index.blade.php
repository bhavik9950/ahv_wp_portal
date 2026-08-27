<x-app-layout>
    <x-slot name="title">Contact Groups</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::ContactManage->value))

    <div class="max-w-2xl space-y-4">
        @if ($canManage)
            <form method="POST" action="{{ route('whatsapp.groups.store') }}" class="card bg-base-100 border border-base-300">
                @csrf
                <div class="card-body flex-row items-end gap-2">
                    <div class="flex-1">
                        <label class="label"><span class="label-text">New group</span></label>
                        <input name="name" class="input input-bordered w-full" placeholder="e.g. VIP customers" required>
                    </div>
                    <div class="flex-1">
                        <label class="label"><span class="label-text">Description</span></label>
                        <input name="description" class="input input-bordered w-full">
                    </div>
                    <button class="btn btn-primary">Add</button>
                </div>
            </form>
        @endif

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table" id="groups-table" data-datatable data-order='[[0,"asc"]]' data-no-sort="{{ $canManage ? '3' : '' }}">
                <thead><tr><th>Name</th><th>Description</th><th>Contacts</th>@if ($canManage)<th></th>@endif</tr></thead>
                <tbody>
                    @foreach ($groups as $g)
                        <tr>
                            <td>{{ $g->name }}</td>
                            <td class="opacity-70">{{ $g->description ?: '—' }}</td>
                            <td data-order="{{ $g->contacts_count }}">{{ $g->contacts_count }}</td>
                            @if ($canManage)
                                <td>
                                    <form method="POST" action="{{ route('whatsapp.groups.destroy', $g) }}" data-confirm="Delete group “{{ $g->name }}”? Contacts are kept.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-xs btn-ghost text-error">Delete</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
