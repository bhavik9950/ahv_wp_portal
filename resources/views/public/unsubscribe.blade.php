<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unsubscribe · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-base-200 text-base-content">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="card w-full max-w-md bg-base-100 shadow border border-base-300">
            <div class="card-body text-center">
                @if ($done || session('done'))
                    <i class="ti ti-circle-check text-4xl text-success mx-auto"></i>
                    <h1 class="text-lg font-semibold mt-2">You've been unsubscribed</h1>
                    <p class="text-sm opacity-70">You will no longer receive marketing messages from this business on WhatsApp.</p>
                @else
                    <h1 class="text-lg font-semibold">Unsubscribe from marketing messages</h1>
                    <p class="text-sm opacity-70 mt-1">
                        This will stop marketing messages to
                        <span class="font-mono">•••{{ substr($contact->phone_e164, -4) }}</span>.
                        Service and transactional messages may still be sent.
                    </p>
                    <form method="POST" action="{{ url()->current() }}" class="mt-4">
                        @csrf
                        <button class="btn btn-error btn-wide">Unsubscribe</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
