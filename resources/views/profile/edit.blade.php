<x-app-layout>
    <x-slot name="title">Profile</x-slot>

    <div class="max-w-2xl space-y-6">
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</x-app-layout>
