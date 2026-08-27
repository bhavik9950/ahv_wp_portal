<section>
    <header>
        <h2 class="text-lg font-semibold">Profile information</h2>
        <p class="mt-1 text-sm opacity-60">Update your name and email address.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label class="label" for="name"><span class="label-text">Name</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                   class="input input-bordered w-full" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label class="label" for="email"><span class="label-text">Email</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                   class="input input-bordered w-full" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn btn-primary">Save</button>
            @if (session('status') === 'profile-updated')
                <p x-data="{show:true}" x-show="show" x-transition x-init="setTimeout(()=>show=false, 2000)"
                   class="text-sm opacity-60">Saved.</p>
            @endif
        </div>
    </form>
</section>
