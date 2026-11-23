<x-app-layout>
    <x-slot name="title">Contact Groups</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::ContactManage->value))

    <div class="max-w-4xl space-y-4">
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
                                <td class="text-right flex items-center justify-end gap-1">
                                    <button type="button" onclick="document.getElementById('add-contacts-{{ $g->id }}').showModal()" class="btn btn-xs btn-primary gap-1">
                                        <i class="ti ti-user-plus"></i> Add Contacts
                                    </button>
                                    <button type="button" onclick="document.getElementById('edit-group-{{ $g->id }}').showModal()" class="btn btn-xs btn-ghost gap-1">
                                        <i class="ti ti-edit"></i> Edit
                                    </button>
                                    <form method="POST" action="{{ route('whatsapp.groups.destroy', $g) }}" data-confirm="Delete group “{{ $g->name }}”? Contacts are kept.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-xs btn-ghost text-error" title="Delete group"><i class="ti ti-trash"></i></button>
                                    </form>

                                    {{-- Add Contacts Modal --}}
                                    <dialog id="add-contacts-{{ $g->id }}" class="modal text-left">
                                        <div class="modal-box max-w-lg">
                                            <h3 class="font-bold text-base mb-1 flex items-center gap-2">
                                                <i class="ti ti-user-plus text-primary"></i> Add Contacts to “{{ $g->name }}”
                                            </h3>
                                            <p class="text-xs opacity-70 mb-3">
                                                Upload a CSV/text file with numbers or paste them below. Existing contacts are mapped to this group, and new numbers are created automatically.
                                            </p>
                                            <form method="POST" action="{{ route('whatsapp.groups.contacts.add', $g) }}" enctype="multipart/form-data" class="space-y-3">
                                                @csrf
                                                <div class="form-control">
                                                    <label class="label py-1"><span class="label-text text-xs font-semibold">Option A: Upload CSV File</span></label>
                                                    <input type="file" name="file" accept=".csv,.txt" class="file-input file-input-bordered file-input-sm w-full">
                                                    <span class="text-[0.7rem] opacity-50 mt-0.5">Accepts failed campaign CSVs or any CSV containing phone numbers.</span>
                                                </div>

                                                <div class="divider text-xs opacity-50 my-1 font-semibold">OR</div>

                                                <div class="form-control">
                                                    <label class="label py-1"><span class="label-text text-xs font-semibold">Option B: Paste Phone Numbers</span></label>
                                                    <textarea name="numbers" rows="4" class="textarea textarea-bordered text-xs font-mono w-full" placeholder="918440940641&#10;919828364996&#10;or comma separated..."></textarea>
                                                    <span class="text-[0.7rem] opacity-50 mt-0.5">Paste comma or line separated numbers.</span>
                                                </div>

                                                <div class="modal-action mt-4">
                                                    <button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('add-contacts-{{ $g->id }}').close()">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-primary gap-1">
                                                        <i class="ti ti-plus"></i> Add to Group
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                                    </dialog>

                                    {{-- Edit Group Modal --}}
                                    <dialog id="edit-group-{{ $g->id }}" class="modal text-left">
                                        <div class="modal-box max-w-md">
                                            <h3 class="font-bold text-base mb-3 flex items-center gap-2">
                                                <i class="ti ti-edit"></i> Edit Group: {{ $g->name }}
                                            </h3>
                                            <form method="POST" action="{{ route('whatsapp.groups.update', $g) }}" class="space-y-3">
                                                @csrf @method('PUT')
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text text-xs font-medium">Group Name</span></label>
                                                    <input type="text" name="name" value="{{ $g->name }}" class="input input-bordered input-sm w-full" required maxlength="80">
                                                </div>
                                                <div class="form-control">
                                                    <label class="label"><span class="label-text text-xs font-medium">Description</span></label>
                                                    <input type="text" name="description" value="{{ $g->description }}" class="input input-bordered input-sm w-full" maxlength="255">
                                                </div>
                                                <div class="modal-action">
                                                    <button type="button" class="btn btn-sm btn-ghost" onclick="document.getElementById('edit-group-{{ $g->id }}').close()">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                                    </dialog>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
