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

    {{-- Fonts & Vite Assets --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Bulletproof Navigation Breakpoint (Guaranteed visible on desktop) */
        @media (min-width: 860px) {
            .public-desktop-nav {
                display: flex !important;
            }
            .public-mobile-toggle {
                display: none !important;
            }
        }
        @media (max-width: 859px) {
            .public-desktop-nav {
                display: none !important;
            }
            .public-mobile-toggle {
                display: inline-flex !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased flex flex-col selection:bg-emerald-500 selection:text-white" data-turbo="false">

    {{-- Top Announcement Bar --}}
    <div class="bg-slate-900 text-white text-[0.72rem] py-2 px-4 text-center font-medium border-b border-slate-800">
        <span class="inline-flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            100% Official Meta Cloud API &bull; Direct Meta Card Billing &bull; Zero Number Ban Risk
        </span>
    </div>

    {{-- Main Navbar --}}
    <header class="w-full bg-white/95 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-8 py-3.5 flex items-center justify-between gap-4">
            {{-- Brand Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xl shadow-sm">
                    <i class="ti ti-brand-whatsapp"></i>
                </div>
                <div class="leading-tight">
                    <span class="font-extrabold text-lg sm:text-xl tracking-tight text-slate-900">{{ config('app.name', 'AHV WhatsApp') }}</span>
                    <span class="text-[0.65rem] block font-semibold text-emerald-600 uppercase tracking-widest">Official Meta Cloud API</span>
                </div>
            </a>

            {{-- Desktop Navigation (Horizontal in center) --}}
            <nav class="public-desktop-nav items-center gap-1 font-semibold text-sm text-slate-600">
                <a href="{{ route('home') }}" class="px-3.5 py-2 rounded-xl transition-all {{ request()->routeIs('home') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">Home</a>
                <a href="{{ route('public.services') }}" class="px-3.5 py-2 rounded-xl transition-all {{ request()->routeIs('public.services') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">Services</a>
                <a href="{{ route('public.pricing') }}" class="px-3.5 py-2 rounded-xl transition-all {{ request()->routeIs('public.pricing') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">Pricing <span class="badge badge-success badge-xs font-bold text-white ml-0.5">₹649</span></a>
                <a href="{{ route('public.about') }}" class="px-3.5 py-2 rounded-xl transition-all {{ request()->routeIs('public.about') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">About Us</a>
                <a href="{{ route('public.faq') }}" class="px-3.5 py-2 rounded-xl transition-all {{ request()->routeIs('public.faq') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">FAQ</a>
                <a href="{{ route('public.contact') }}" class="px-3.5 py-2 rounded-xl transition-all {{ request()->routeIs('public.contact') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">Contact</a>
            </nav>

            {{-- Right CTA / Mobile Toggle --}}
            <div class="flex items-center gap-3 shrink-0">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm px-4 text-xs font-bold rounded-xl gap-1.5 shadow-sm">
                        <i class="ti ti-layout-dashboard"></i> Dashboard
                    </a>
                @else
                    <a href="{{ route('public.contact') }}" class="btn btn-primary btn-sm px-5 bg-emerald-600 hover:bg-emerald-700 text-white border-0 text-xs font-bold rounded-xl gap-1.5 shadow-sm">
                        <i class="ti ti-mail-fast text-sm"></i> Request Access
                    </a>
                @endauth

                {{-- Mobile Hamburger Button (Only on screen < 860px) --}}
                <div class="public-mobile-toggle dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-sm btn-square">
                        <i class="ti ti-menu-2 text-xl text-slate-800"></i>
                    </label>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-50 p-3 shadow-2xl bg-white rounded-2xl w-60 border border-slate-200 font-semibold text-xs space-y-1">
                        <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-home"></i> Home</a></li>
                        <li><a href="{{ route('public.services') }}" class="{{ request()->routeIs('public.services') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-apps"></i> Services</a></li>
                        <li><a href="{{ route('public.pricing') }}" class="{{ request()->routeIs('public.pricing') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-tags"></i> Pricing (From ₹649)</a></li>
                        <li><a href="{{ route('public.about') }}" class="{{ request()->routeIs('public.about') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-info-circle"></i> About Us</a></li>
                        <li><a href="{{ route('public.faq') }}" class="{{ request()->routeIs('public.faq') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-help"></i> FAQ</a></li>
                        <li><a href="{{ route('public.contact') }}" class="{{ request()->routeIs('public.contact') ? 'active bg-emerald-50 text-emerald-700 font-bold' : '' }}"><i class="ti ti-mail"></i> Contact Us</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Page Content --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- Spacious, Premium Footer (Like MSG91) --}}
    <footer class="mt-auto bg-slate-900 text-slate-300 border-t border-slate-800 pt-16 pb-12 px-4 sm:px-8 text-xs">
        <div class="max-w-7xl mx-auto space-y-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
                {{-- Col 1: Brand Info --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-bold text-lg">
                            <i class="ti ti-brand-whatsapp"></i>
                        </div>
                        <span class="font-extrabold text-base text-white">AHV WhatsApp Portal</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Enterprise communication cloud platform. Official Meta WhatsApp Cloud API software for broadcasting campaigns, customer live chat, and automated messaging.
                    </p>
                    <div class="text-[0.7rem] text-slate-500">
                        A product of AHV Software Technologies.
                    </div>
                </div>

                {{-- Col 2: Services --}}
                <div class="space-y-3">
                    <h4 class="font-bold text-sm text-white uppercase tracking-wider text-[0.75rem]">Communication Services</h4>
                    <ul class="space-y-2 text-slate-400">
                        <li><a href="{{ route('public.services') }}" class="hover:text-white transition-colors">WhatsApp Cloud API (Live)</a></li>
                        <li><a href="{{ route('public.services') }}" class="hover:text-white transition-colors">Enterprise SMS Gateway</a></li>
                        <li><a href="{{ route('public.services') }}" class="hover:text-white transition-colors">AI Voice Calling Agent</a></li>
                        <li><a href="{{ route('public.services') }}" class="hover:text-white transition-colors">Multi-Agent Live Chat Inbox</a></li>
                    </ul>
                </div>

                {{-- Col 3: Company & Pricing --}}
                <div class="space-y-3">
                    <h4 class="font-bold text-sm text-white uppercase tracking-wider text-[0.75rem]">Company &amp; Plans</h4>
                    <ul class="space-y-2 text-slate-400">
                        <li><a href="{{ route('public.pricing') }}" class="hover:text-white transition-colors">Pricing Plans (From ₹649)</a></li>
                        <li><a href="{{ route('public.pricing') }}" class="hover:text-white transition-colors">One-Time Setup (₹4,500 Fix)</a></li>
                        <li><a href="{{ route('public.about') }}" class="hover:text-white transition-colors">About AHV Software</a></li>
                        <li><a href="{{ route('public.faq') }}" class="hover:text-white transition-colors">Frequently Asked Questions</a></li>
                    </ul>
                </div>

                {{-- Col 4: Support & Contact --}}
                <div class="space-y-3">
                    <h4 class="font-bold text-sm text-white uppercase tracking-wider text-[0.75rem]">Enterprise Support</h4>
                    <p class="text-xs text-slate-400">Direct technical onboarding assistance via WhatsApp and Phone:</p>
                    <div>
                        <a href="https://wa.me/918104055511" target="_blank" rel="noopener noreferrer"
                           class="btn btn-outline btn-sm text-white border-slate-700 hover:bg-emerald-600 hover:border-emerald-600 w-full gap-2 text-xs">
                            <i class="ti ti-brand-whatsapp text-emerald-400 text-base"></i> +91 81040 55511
                        </a>
                    </div>
                    <div class="pt-1">
                        <a href="{{ route('public.contact') }}" class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-sm w-full text-xs border-0">
                            Submit Onboarding Request &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-slate-500 text-[0.7rem]">
                <div>
                    &copy; {{ date('Y') }} AHV Software Technologies. All rights reserved. 100% Official Meta Cloud API Architecture.
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('public.services') }}" class="hover:text-slate-400">Services</a>
                    <a href="{{ route('public.pricing') }}" class="hover:text-slate-400">Pricing</a>
                    <a href="{{ route('public.about') }}" class="hover:text-slate-400">About</a>
                    <a href="{{ route('public.contact') }}" class="hover:text-slate-400">Contact</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
