<x-app-layout>
    <x-slot name="title">Add Contact</x-slot>

    <form method="POST" action="{{ route('whatsapp.contacts.store') }}" class="max-w-lg card bg-base-100 border border-base-300">
        @csrf
        <div class="card-body space-y-4">
            <div>
                <label class="label"><span class="label-text">Name</span></label>
                <input name="name" value="{{ old('name') }}" class="input input-bordered w-full">
                @error('name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="label"><span class="label-text">Country code</span></label>
                    <input name="country_code" value="{{ old('country_code') }}" class="input input-bordered w-full" placeholder="{{ config('services.whatsapp.default_country_code') }}">
                </div>
                <div class="col-span-2">
                    <label class="label"><span class="label-text">Phone</span></label>
                    <input name="phone" value="{{ old('phone') }}" class="input input-bordered w-full" placeholder="9876543210 or +91 98765 43210" required>
                    @error('phone')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="label"><span class="label-text">Email</span></label>
                <input name="email" type="email" value="{{ old('email') }}" class="input input-bordered w-full">
                @error('email')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            @if ($groups->isNotEmpty())
                <div>
                    <label class="label"><span class="label-text">Groups</span></label>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($groups as $g)
                            <label class="label cursor-pointer gap-1">
                                <input type="checkbox" name="groups[]" value="{{ $g->id }}" class="checkbox checkbox-sm">
                                <span class="label-text">{{ $g->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="flex gap-2">
                <button class="btn btn-primary">Add contact</button>
                <a href="{{ route('whatsapp.contacts.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </div>
    </form>
</x-app-layout>
