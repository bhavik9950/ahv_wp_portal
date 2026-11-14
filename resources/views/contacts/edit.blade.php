<x-app-layout>
    <x-slot name="title">Edit Contact · {{ $contact->name ?: $contact->phone_e164 }}</x-slot>

    <div class="max-w-lg space-y-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('whatsapp.contacts.index') }}" class="btn btn-ghost btn-sm">
                <i class="ti ti-arrow-left"></i> Contacts
            </a>
            <form method="POST" action="{{ route('whatsapp.contacts.destroy', $contact) }}" data-confirm="Delete this contact?">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline btn-error"><i class="ti ti-trash"></i> Delete contact</button>
            </form>
        </div>

        <form method="POST" action="{{ route('whatsapp.contacts.update', $contact) }}" class="card bg-base-100 border border-base-300">
            @csrf @method('PUT')
            <div class="card-body space-y-4">
                <h2 class="card-title text-base font-semibold flex items-center gap-2">
                    <i class="ti ti-user-edit"></i> Edit Contact Details
                </h2>

                <div>
                    <label class="label"><span class="label-text font-medium text-xs">Name</span></label>
                    <input name="name" value="{{ old('name', $contact->name) }}" class="input input-bordered w-full" placeholder="Contact name">
                    @error('name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="label"><span class="label-text font-medium text-xs">Country code</span></label>
                        <input name="country_code" value="{{ old('country_code', $contact->country_code) }}" class="input input-bordered w-full" placeholder="91">
                    </div>
                    <div class="col-span-2">
                        <label class="label"><span class="label-text font-medium text-xs">Phone</span></label>
                        <input name="phone" value="{{ old('phone', $contact->phone_e164) }}" class="input input-bordered w-full font-mono" required>
                        @error('phone')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text font-medium text-xs">Email</span></label>
                    <input name="email" type="email" value="{{ old('email', $contact->email) }}" class="input input-bordered w-full" placeholder="email@example.com">
                    @error('email')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                @if ($groups->isNotEmpty())
                    @php($assignedGroupIds = $contact->groups->pluck('id')->all())
                    <div>
                        <label class="label"><span class="label-text font-medium text-xs">Group Memberships</span></label>
                        <div class="flex flex-wrap gap-2 p-3 bg-base-200/50 rounded-lg border border-base-200">
                            @foreach ($groups as $g)
                                <label class="label cursor-pointer gap-1.5 py-1">
                                    <input type="checkbox" name="groups[]" value="{{ $g->id }}"
                                           class="checkbox checkbox-sm checkbox-primary"
                                           @checked(in_array($g->id, old('groups', $assignedGroupIds), true))>
                                    <span class="label-text text-xs">{{ $g->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex gap-2 pt-2">
                    <button class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('whatsapp.contacts.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
