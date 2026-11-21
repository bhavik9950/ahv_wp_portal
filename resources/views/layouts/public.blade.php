<!DOCTYPE html>
<html lang="en" data-theme="light" class="overflow-x-hidden scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">

    {{-- Primary SEO Meta Tags --}}
    <title>{{ $title ?? 'AHV WhatsApp Portal — Official Meta Cloud API WhatsApp Marketing Platform' }}</title>
    <meta name="title" content="{{ $title ?? 'AHV WhatsApp Portal — Official Meta Cloud API WhatsApp Marketing Platform' }}">
    <meta name="description" content="{{ $description ?? 'Official Meta WhatsApp Cloud API software for high-deliverability broadcast campaigns, 2-way live chat inbox, template management, and direct Meta card billing with ₹0 markup.' }}">
    <meta name="keywords" content="WhatsApp Marketing Platform, Official Meta Cloud API, WhatsApp Broadcast Software, Bulk WhatsApp Marketing India, Meta WABA Verification, DLT SMS Gateway, AI Voice Calling Agent, AHV Software">
    <meta name="author" content="AHV Software Technologies">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook / LinkedIn SEO --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'AHV WhatsApp Portal — Official Meta Cloud API Platform' }}">
    <meta property="og:description" content="{{ $description ?? 'Official Meta WhatsApp Cloud API software. Send broadcast campaigns, live 2-way customer chat, ₹0 markup on Meta fees.' }}">
    <meta property="og:site_name" content="AHV WhatsApp Portal">
    <meta property="og:locale" content="en_IN">

    {{-- Twitter SEO Cards --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $title ?? 'AHV WhatsApp Portal — Official Meta Cloud API' }}">
    <meta name="twitter:description" content="{{ $description ?? 'Enterprise WhatsApp marketing software by AHV Software. Direct Meta billing with zero markup.' }}">

    {{-- Mobile Web App & Theme Color (iOS / Android) --}}
    <meta name="theme-color" content="#059669">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">

    {{-- AEO & Structured Data (JSON-LD Schema for Google, Gemini, Perplexity, ChatGPT Search) --}}
    @php
        $schemaJson = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => 'https://wap.ahvsoftware.com/#organization',
                    'name' => 'AHV Software Technologies',
                    'url' => 'https://wap.ahvsoftware.com',
                    'logo' => 'https://wap.ahvsoftware.com/favicon.ico',
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'telephone' => '+91-8104055511',
                        'contactType' => 'Customer Support and Sales',
                        'areaServed' => 'IN',
                        'availableLanguage' => ['en', 'hi']
                    ]
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => 'https://wap.ahvsoftware.com/#software',
                    'name' => 'AHV WhatsApp Portal',
                    'applicationCategory' => 'BusinessApplication, MarketingApplication',
                    'operatingSystem' => 'Web Browser, Cloud, Android, iOS',
                    'url' => 'https://wap.ahvsoftware.com',
                    'description' => 'Official Meta WhatsApp Cloud API marketing software for bulk broadcasts, live 2-way multi-agent chat, and direct Meta card billing.',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '649',
                        'priceCurrency' => 'INR',
                        'priceValidUntil' => '2027-12-31',
                        'availability' => 'https://schema.org/InStock',
                        'description' => 'Monthly starter plan at ₹649/month with fixed ₹4,500 one-time onboarding and verification setup.'
                    ],
                    'aggregateRating' => [
                        '@type' => 'AggregateRating',
                        'ratingValue' => '4.9',
                        'ratingCount' => '142',
                        'bestRating' => '5',
                        'worstRating' => '1'
                    ]
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => 'https://wap.ahvsoftware.com/#website',
                    'url' => 'https://wap.ahvsoftware.com',
                    'name' => 'AHV WhatsApp Portal',
                    'publisher' => [
                        '@id' => 'https://wap.ahvsoftware.com/#organization'
                    ]
                ]
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $schemaJson !!}</script>

    {{-- Fonts & Vite Compiled Assets --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-tap-highlight-color: transparent;
            text-size-adjust: 100%;
        }

        /* Bulletproof Responsive Navigation */
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

        /* Safe area handling for iOS dynamic island / notch */
        @supports (padding-top: env(safe-area-inset-top)) {
            .safe-top {
                padding-top: env(safe-area-inset-top);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased flex flex-col selection:bg-emerald-500 selection:text-white overflow-x-hidden w-full" data-turbo="false">

    {{-- 1. Top Announcement Bar (Responsive on Mobile, Tablet & Desktop) --}}
    <div class="bg-slate-900 text-white text-[0.68rem] sm:text-xs py-1.5 sm:py-2 px-3 sm:px-4 text-center font-medium border-b border-slate-800 leading-snug">
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-2 flex-wrap">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
            <span class="font-semibold text-emerald-400">Official Meta Cloud API</span>
            <span class="opacity-40">&bull;</span>
            <span>Direct Meta Card Billing</span>
            <span class="opacity-40 hidden xs:inline">&bull;</span>
            <span class="text-slate-300 hidden xs:inline">Zero Ban Risk</span>
        </div>
    </div>

    {{-- 2. Main Navbar (Responsive: iOS, Android, Tablets, Laptops & Desktops) --}}
    <header class="w-full bg-white/95 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/90 shadow-2xs">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 sm:py-3.5 flex items-center justify-between gap-2 sm:gap-4">
            {{-- Brand Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 sm:gap-3 shrink-0 group">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg sm:text-xl shadow-xs group-hover:scale-105 transition-transform">
                    <i class="ti ti-brand-whatsapp"></i>
                </div>
                <div class="leading-none">
                    <span class="font-extrabold text-base sm:text-lg lg:text-xl tracking-tight text-slate-900 block">
                        {{ config('app.name', 'AHV WhatsApp') }}
                    </span>
                    <span class="text-[0.6rem] sm:text-[0.65rem] font-bold text-emerald-600 uppercase tracking-wider block mt-0.5">
                        Official Meta Cloud API
                    </span>
                </div>
            </a>

            {{-- Desktop Navigation (Horizontal on Desktop/Laptop >= 860px) --}}
            <nav class="public-desktop-nav items-center gap-1 font-semibold text-xs lg:text-sm text-slate-600" aria-label="Main Navigation">
                <a href="{{ route('home') }}" class="px-3 py-2 rounded-xl transition-all {{ request()->routeIs('home') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">Home</a>
                <a href="{{ route('public.services') }}" class="px-3 py-2 rounded-xl transition-all {{ request()->routeIs('public.services') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">Services</a>
                <a href="{{ route('public.pricing') }}" class="px-3 py-2 rounded-xl transition-all {{ request()->routeIs('public.pricing') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">
                    Pricing <span class="badge badge-success badge-xs font-bold text-white ml-0.5">₹649</span>
                </a>
                <a href="{{ route('public.about') }}" class="px-3 py-2 rounded-xl transition-all {{ request()->routeIs('public.about') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">About Us</a>
                <a href="{{ route('public.faq') }}" class="px-3 py-2 rounded-xl transition-all {{ request()->routeIs('public.faq') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">FAQ</a>
                <a href="{{ route('public.contact') }}" class="px-3 py-2 rounded-xl transition-all {{ request()->routeIs('public.contact') ? 'text-emerald-700 font-bold bg-emerald-50 border border-emerald-200' : 'hover:text-emerald-600 hover:bg-slate-100' }}">Contact</a>
            </nav>

            {{-- Right CTA / Mobile Controls --}}
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                {{-- Visible on tablet/desktop, hidden on tiny mobile so logo doesn't wrap --}}
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-xs sm:btn-sm px-3 sm:px-4 text-xs font-bold rounded-xl gap-1.5 shadow-xs">
                        <i class="ti ti-layout-dashboard"></i> <span class="hidden xs:inline">Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('public.contact') }}" class="hidden sm:inline-flex btn btn-primary btn-sm px-4 bg-emerald-600 hover:bg-emerald-700 text-white border-0 text-xs font-bold rounded-xl gap-1.5 shadow-xs">
                        <i class="ti ti-mail-fast text-sm"></i> Request Access
                    </a>
                    <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20am%20interested%20in%20the%20WhatsApp%20Cloud%20Portal."
                       target="_blank" rel="noopener noreferrer"
                       class="inline-flex sm:hidden p-2 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-lg hover:bg-emerald-100 transition-colors"
                       aria-label="Direct WhatsApp Contact">
                        <i class="ti ti-brand-whatsapp"></i>
                    </a>
                @endauth

                {{-- Mobile Hamburger Dropdown (iOS / Android / Tablets < 860px) --}}
                <div class="public-mobile-toggle dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-sm btn-square rounded-xl border border-slate-200 bg-slate-50" aria-label="Toggle navigation menu">
                        <i class="ti ti-menu-2 text-xl text-slate-800"></i>
                    </label>
                    <div tabindex="0" class="dropdown-content mt-3 z-50 p-4 shadow-2xl bg-white rounded-2xl w-72 sm:w-80 border border-slate-200 space-y-3">
                        <div class="font-extrabold text-xs text-slate-400 uppercase tracking-wider px-2">Navigation Menu</div>
                        <ul class="menu menu-sm p-0 space-y-1 font-semibold text-xs text-slate-700">
                            <li>
                                <a href="{{ route('home') }}" class="p-2.5 rounded-xl {{ request()->routeIs('home') ? 'bg-emerald-50 text-emerald-700 font-bold' : 'hover:bg-slate-100' }}">
                                    <i class="ti ti-home text-base"></i> Home
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('public.services') }}" class="p-2.5 rounded-xl {{ request()->routeIs('public.services') ? 'bg-emerald-50 text-emerald-700 font-bold' : 'hover:bg-slate-100' }}">
                                    <i class="ti ti-apps text-base"></i> Communication Services
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('public.pricing') }}" class="p-2.5 rounded-xl {{ request()->routeIs('public.pricing') ? 'bg-emerald-50 text-emerald-700 font-bold' : 'hover:bg-slate-100' }}">
                                    <i class="ti ti-tags text-base"></i> Pricing Plans (From ₹649)
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('public.about') }}" class="p-2.5 rounded-xl {{ request()->routeIs('public.about') ? 'bg-emerald-50 text-emerald-700 font-bold' : 'hover:bg-slate-100' }}">
                                    <i class="ti ti-info-circle text-base"></i> About AHV Software
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('public.faq') }}" class="p-2.5 rounded-xl {{ request()->routeIs('public.faq') ? 'bg-emerald-50 text-emerald-700 font-bold' : 'hover:bg-slate-100' }}">
                                    <i class="ti ti-help text-base"></i> Frequently Asked Questions
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('public.contact') }}" class="p-2.5 rounded-xl {{ request()->routeIs('public.contact') ? 'bg-emerald-50 text-emerald-700 font-bold' : 'hover:bg-slate-100' }}">
                                    <i class="ti ti-mail text-base"></i> Contact &amp; Onboarding
                                </a>
                            </li>
                        </ul>

                        <div class="pt-2 border-t border-slate-100 space-y-2">
                            <a href="{{ route('public.contact') }}" class="btn btn-primary btn-sm w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs gap-1.5 border-0 shadow-sm">
                                <i class="ti ti-mail-fast"></i> Request Workspace Access
                            </a>
                            <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20would%20like%20to%20inquire%20about%20the%20WhatsApp%20Cloud%20Portal."
                               target="_blank" rel="noopener noreferrer"
                               class="btn btn-outline btn-sm w-full border-slate-200 text-slate-700 hover:bg-slate-100 font-semibold rounded-xl text-xs gap-1.5">
                                <i class="ti ti-brand-whatsapp text-emerald-600"></i> WhatsApp (+91 81040 55511)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Page Content --}}
    <main class="flex-1 w-full overflow-x-hidden">
        {{ $slot }}
    </main>

    {{-- Spacious, Responsive Footer (iOS, Android, Tablet, Laptop, Desktop) --}}
    <footer class="mt-auto bg-slate-900 text-slate-300 border-t border-slate-800 pt-12 sm:pt-16 pb-10 sm:pb-12 px-4 sm:px-8 text-xs w-full overflow-x-hidden">
        <div class="max-w-7xl mx-auto space-y-10 sm:space-y-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                {{-- Col 1: Brand Info --}}
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-bold text-lg">
                            <i class="ti ti-brand-whatsapp"></i>
                        </div>
                        <span class="font-extrabold text-base text-white">AHV WhatsApp Portal</span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Enterprise cloud communication platform. Official Meta WhatsApp Cloud API software for broadcasting campaigns, customer live chat, and automated messaging.
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
                        <li><a href="{{ route('public.services') }}" class="hover:text-white transition-colors">Enterprise SMS Gateway (Roadmap)</a></li>
                        <li><a href="{{ route('public.services') }}" class="hover:text-white transition-colors">AI Voice Calling Agent (Roadmap)</a></li>
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
