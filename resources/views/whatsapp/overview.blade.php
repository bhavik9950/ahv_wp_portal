<x-app-layout>
    <x-slot name="title">WhatsApp Overview</x-slot>

    <div class="card bg-base-100 border border-base-300">
        <div class="card-body">
            <h2 class="card-title text-base">WhatsApp Business Platform</h2>
            <p class="text-sm opacity-70">
                This portal connects to the official Meta WhatsApp Cloud API. Configure your
                WhatsApp Business Account and phone number under
                <strong>WhatsApp → Settings</strong>, then run the connection checks before sending.
            </p>
            <div class="text-sm opacity-70 mt-2">
                Driver in use:
                <span class="badge badge-outline">{{ config('services.whatsapp.driver') }}</span>
                &nbsp;·&nbsp; Sending:
                <span class="badge {{ config('services.whatsapp.sending_enabled') ? 'badge-success' : 'badge-error' }}">
                    {{ config('services.whatsapp.sending_enabled') ? 'enabled' : 'disabled (kill switch)' }}
                </span>
            </div>
        </div>
    </div>
</x-app-layout>
