<x-app-layout>
    <x-slot name="title">Add Contact(s)</x-slot>

    <form method="POST" action="{{ route('whatsapp.contacts.store') }}" class="max-w-xl card bg-base-100 border border-base-300">
        @csrf
        <div class="card-body space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="card-title text-base">Add Contact(s)</h2>
                <a href="{{ route('whatsapp.contacts.index') }}" class="btn btn-xs btn-ghost gap-1">
                    <i class="ti ti-arrow-left"></i> Back to contacts
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div class="sm:col-span-1">
                    <label class="label"><span class="label-text font-medium">Country code</span></label>
                    <input name="country_code" value="{{ old('country_code', config('services.whatsapp.default_country_code', '91')) }}"
                           class="input input-bordered w-full font-mono text-sm" placeholder="91">
                    <span class="text-[11px] opacity-50 mt-1 block">Default: 91</span>
                </div>
                <div class="sm:col-span-3">
                    <label class="label">
                        <span class="label-text font-medium">Phone Number(s) <span class="text-error">*</span></span>
                    </label>
                    <textarea name="phone" rows="3" class="textarea textarea-bordered w-full font-mono text-sm leading-relaxed"
                              placeholder="Single number or multiple separated by comma or new line&#10;e.g. 9876543210, 9123456780, +91 99887 76655" required>{{ old('phone') }}</textarea>
                    <span class="text-[11px] opacity-60 mt-1 block">
                        <i class="ti ti-info-circle"></i> Ek sath multiple numbers comma (<code>,</code>) ya new line laga kar paste kar sakte hain.
                    </span>
                    @error('phone')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="divider my-1 text-xs opacity-50">Details (Optional)</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="label"><span class="label-text">Name <span class="text-xs opacity-50">(Optional)</span></span></label>
                    <input name="name" value="{{ old('name') }}" class="input input-bordered w-full" placeholder="John Doe">
                    @error('name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label"><span class="label-text">Email <span class="text-xs opacity-50">(Optional)</span></span></label>
                    <input name="email" type="email" value="{{ old('email') }}" class="input input-bordered w-full" placeholder="john@example.com">
                    @error('email')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-lg border border-primary/20 bg-primary/5 p-3">
                <label class="label cursor-pointer justify-start gap-3 p-0">
                    <input type="checkbox" name="opt_in" value="1" class="checkbox checkbox-primary checkbox-sm" {{ old('opt_in', '1') ? 'checked' : '' }}>
                    <div>
                        <span class="label-text font-semibold text-primary">Direct Opt-in (Recommended)</span>
                        <p class="text-xs opacity-70 mt-0.5">Contacts ko instantly "Opted-In" mark karein taaki marketing templates aur campaigns bina kisi delay ke send kiye ja sakein.</p>
                    </div>
                </label>
            </div>

            @if ($groups->isNotEmpty())
                <div>
                    <label class="label"><span class="label-text font-medium">Assign to Groups <span class="text-xs opacity-50">(Optional)</span></span></label>
                    <div class="flex flex-wrap gap-2 max-h-36 overflow-y-auto p-2 border border-base-300 rounded-lg">
                        @foreach ($groups as $g)
                            <label class="label cursor-pointer gap-2 py-1 px-2 rounded hover:bg-base-200">
                                <input type="checkbox" name="groups[]" value="{{ $g->id }}" class="checkbox checkbox-xs"
                                       {{ in_array($g->id, (array) old('groups', [])) ? 'checked' : '' }}>
                                <span class="label-text text-xs">{{ $g->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-2 pt-2">
                <button class="btn btn-primary gap-1">
                    <i class="ti ti-user-plus"></i> Add Contact(s)
                </button>
                <a href="{{ route('whatsapp.contacts.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</x-app-layout>
