<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Messages (30d)', '—', 'ti-messages', 'text-primary'],
            ['Delivery rate', '—', 'ti-checks', 'text-success'],
            ['Active campaigns', '—', 'ti-send', 'text-info'],
            ['Opted-in contacts', '—', 'ti-address-book', 'text-secondary'],
        ] as [$label, $value, $icon, $color])
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body flex-row items-center gap-4 py-5">
                    <i class="ti {{ $icon }} text-3xl {{ $color }}"></i>
                    <div>
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                        <div class="text-xs opacity-60">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card bg-base-100 border border-base-300 mt-6">
        <div class="card-body">
            <h2 class="card-title text-base">Getting started</h2>
            <p class="text-sm opacity-70">
                Connect your WhatsApp Business Account under
                <a class="link" href="{{ \Illuminate\Support\Facades\Route::has('whatsapp.settings.edit') ? route('whatsapp.settings.edit') : '#' }}">WhatsApp → Settings</a>,
                then run the connection checks. Dashboard metrics populate once messages start flowing.
            </p>
        </div>
    </div>
</x-app-layout>
