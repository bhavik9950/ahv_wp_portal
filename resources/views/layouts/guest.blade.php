<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AH&V WhatsApp Portal') }} &bull; Sign In</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-base-200/50 text-base-content min-h-screen flex flex-col justify-center items-center px-4 py-8">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-success/10 text-success flex items-center justify-center shadow-sm border border-success/20 group-hover:scale-105 transition">
                    <i class="ti ti-brand-whatsapp text-2xl"></i>
                </div>
                <div class="text-left">
                    <span class="text-xl font-bold tracking-tight block text-base-content">{{ config('app.name', 'AH&V WhatsApp Portal') }}</span>
                    <span class="text-[11px] font-semibold tracking-wider uppercase text-success block">Official Meta Cloud API</span>
                </div>
            </a>
        </div>

        <!-- Auth Card -->
        <div class="card bg-base-100 shadow-xl border border-base-300 rounded-2xl">
            <div class="card-body p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>

        <!-- Security / Provisioning note -->
        <div class="mt-6 text-center space-y-1">
            <p class="text-xs text-base-content/60 flex items-center justify-center gap-1.5">
                <i class="ti ti-shield-check text-success"></i>
                <span>Multi-Tenant Enterprise Isolation &bull; Accounts provisioned by Admin</span>
            </p>
            <p class="text-[11px] text-base-content/40">
                &copy; {{ date('Y') }} {{ config('app.name', 'AH&V WhatsApp Portal') }}. All rights reserved.
            </p>
        </div>
    </div>

    <script type="application/json" id="flash-notify">@json(session('flash_notify'))</script>
</body>
</html>
