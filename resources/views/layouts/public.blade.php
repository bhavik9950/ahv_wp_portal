<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'AHV WhatsApp Portal — Official Meta Cloud API Marketing Platform' }}</title>
    <meta name="title" content="{{ $title ?? 'AHV WhatsApp Portal — Official Meta Cloud API Marketing Platform' }}">
    <meta name="description" content="{{ $description ?? 'Official Meta Cloud API WhatsApp marketing software by AHV Software. Run broadcast campaigns, manage approved templates, live customer chat, and pay Meta directly with zero markup.' }}">
    <meta name="keywords" content="WhatsApp Marketing, Meta Cloud API, WhatsApp Broadcast, Bulk WhatsApp Sender India, Official WhatsApp Marketing Software, AHV Software">
    <meta name="author" content="AHV Software Technologies">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Fonts & Vite Assets (Bundled locally — Zero external CDN) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-base-100 text-base-content antialiased flex flex-col selection:bg-emerald-500 selection:text-white" data-turbo="false">

    {{-- Top Notification Bar --}}
    <div class="bg-emerald-950 text-white text-[0.72rem] py-1.5 px-4 text-center font-medium">
        <span>🚀 100% Official Meta Cloud API &bull; Direct Meta Card Billing &bull; Zero Number Ban Risk</span>
    </div>

    {{-- Main Navbar (Bulletproof Flexbox) --}}
    <header class="w-full bg-base-100/95 backdrop-blur-md sticky top-0 z-50 border-b border-base-200 px-4 sm:px-8 py-2.5 flex items-center justify-between">
        {{-- Brand Logo --}}
        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                    <i class="ti ti-brand-whatsapp"></i>
                </div>
                <div class="leading-tight">
                    <span class="font-bold text-base sm:text-lg tracking-tight">{{ config('app.name', 'AHV WhatsApp') }}</span>
                    <span class="text-[0.65rem] block font-semibold text-emerald-600 uppercase tracking-widest">Official Meta API</span>
                </div>
            </a>
        </div>

        {{-- Desktop Navigation Links --}}
        <nav class="hidden md:flex items-center gap-1 font-semibold text-xs">
            <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('home') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'opacity-75 hover:opacity-100 hover:bg-base-200' }}">Home</a>
            <a href="{{ route('public.services') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('public.services') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'opacity-75 hover:opacity-100 hover:bg-base-200' }}">Services</a>
            <a href="{{ route('public.pricing') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('public.pricing') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'opacity-75 hover:opacity-100 hover:bg-base-200' }}">Pricing</a>
            <a href="{{ route('public.about') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('public.about') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'opacity-75 hover:opacity-100 hover:bg-base-200' }}">About Us</a>
            <a href="{{ route('public.faq') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('public.faq') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'opacity-75 hover:opacity-100 hover:bg-base-200' }}">FAQ</a>
            <a href="{{ route('public.contact') }}" class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('public.contact') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'opacity-75 hover:opacity-100 hover:bg-base-200' }}">Contact</a>
        </nav>

        {{-- Right Actions --}}
        <div class="flex items-center gap-2 shrink-0">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm gap-1.5 text-xs">
                    <i class="ti ti-layout-dashboard"></i> Dashboard
                </a>
            @else
                <a href="{{ route('public.contact') }}" class="btn btn-primary btn-sm gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white border-0 shadow-xs">
                    <i class="ti ti-mail-fast"></i> Request Access
                </a>
            @endauth

            {{-- Mobile Hamburger Dropdown Menu --}}
            <div class="dropdown dropdown-end md:hidden">
                <button type="button" tabindex="0" class="btn btn-ghost btn-sm btn-square" aria-label="Open menu">
                    <i class="ti ti-menu-2 text-xl"></i>
                </button>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-50 p-2 shadow-xl bg-base-100 rounded-2xl w-52 border border-base-300 font-semibold text-xs space-y-1">
                    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-home"></i> Home</a></li>
                    <li><a href="{{ route('public.services') }}" class="{{ request()->routeIs('public.services') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-apps"></i> Services</a></li>
                    <li><a href="{{ route('public.pricing') }}" class="{{ request()->routeIs('public.pricing') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-tags"></i> Pricing (From ₹649)</a></li>
                    <li><a href="{{ route('public.about') }}" class="{{ request()->routeIs('public.about') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-info-circle"></i> About Us</a></li>
                    <li><a href="{{ route('public.faq') }}" class="{{ request()->routeIs('public.faq') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-help"></i> FAQ</a></li>
                    <li><a href="{{ route('public.contact') }}" class="{{ request()->routeIs('public.contact') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-mail"></i> Contact Us</a></li>
                </ul>
            </div>
        </div>
    </header>

    {{-- Main Page Content --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="mt-auto bg-base-200/60 border-t border-base-200 py-10 px-4 sm:px-8 text-xs opacity-80">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 font-semibold">
                    <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-sm">
                        <i class="ti ti-brand-whatsapp"></i>
                    </div>
                    <span>AHV WhatsApp Portal &bull; AHV Software Technologies</span>
                </div>
                <div class="flex items-center gap-5 flex-wrap justify-center">
                    <a href="{{ route('home') }}" class="hover:underline">Home</a>
                    <a href="{{ route('public.services') }}" class="hover:underline">Services</a>
                    <a href="{{ route('public.pricing') }}" class="hover:underline">Pricing</a>
                    <a href="{{ route('public.about') }}" class="hover:underline">About Us</a>
                    <a href="{{ route('public.faq') }}" class="hover:underline">FAQ</a>
                    <a href="{{ route('public.contact') }}" class="hover:underline">Contact Us</a>
                </div>
            </div>
            <div class="text-center text-[0.7rem] opacity-60 border-t border-base-300 pt-4">
                &copy; {{ date('Y') }} AHV Software Technologies. All rights reserved. 100% Official Meta Cloud API Infrastructure.
            </div>
        </div>
    </footer>

</body>
</html>
