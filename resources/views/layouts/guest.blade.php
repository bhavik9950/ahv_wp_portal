<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-base-200 text-base-content">
    <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10">
        <div class="flex items-center gap-2 mb-6">
            <i class="ti ti-brand-whatsapp text-3xl text-success"></i>
            <span class="text-xl font-bold">{{ config('app.name') }}</span>
        </div>

        <div class="card w-full max-w-md bg-base-100 shadow-lg border border-base-300">
            <div class="card-body">
                {{ $slot }}
            </div>
        </div>

        <p class="text-xs opacity-50 mt-6">Accounts are provisioned by an administrator.</p>
    </div>

    <script type="application/json" id="flash-notify">@json(session('flash_notify'))</script>
</body>
</html>
