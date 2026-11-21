<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Primary SEO Meta Tags --}}
    <title>AHV WhatsApp Portal — Official Meta Cloud API WhatsApp Marketing Platform</title>
    <meta name="title" content="AHV WhatsApp Portal — Official Meta Cloud API WhatsApp Marketing Platform">
    <meta name="description" content="Official Meta Cloud API WhatsApp marketing software by AHV Software. Run broadcast campaigns, manage approved templates, live customer chat, and pay Meta directly with zero markup.">
    <meta name="keywords" content="WhatsApp Marketing, Meta Cloud API, WhatsApp Broadcast, Bulk WhatsApp Sender India, Official WhatsApp Marketing Software, AHV Software">
    <meta name="author" content="AHV Software Technologies">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="https://wap.ahvsoftware.com/">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://wap.ahvsoftware.com/">
    <meta property="og:title" content="AHV WhatsApp Portal — Official Meta Cloud API WhatsApp Marketing">
    <meta property="og:description" content="Run bulk WhatsApp broadcast campaigns with official Meta Cloud API. Zero ban risk, pay Meta directly on your card, live chat inbox.">
    <meta property="og:site_name" content="AHV WhatsApp Portal">
    <meta property="og:locale" content="en_IN">

    {{-- Fonts & CSS (Bundled by Vite — Zero external CDN) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>

    {{-- Structured Data (JSON-LD) --}}
    @php
        $schemaJson = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'AHV WhatsApp Marketing Portal',
            'operatingSystem' => 'Web, Cloud',
            'applicationCategory' => 'BusinessApplication',
            'url' => 'https://wap.ahvsoftware.com/',
            'description' => 'Official Meta WhatsApp Cloud API software for broadcasting marketing campaigns, managing approved templates, and 1-on-1 customer live chat.',
            'offers' => [
                ['@type' => 'Offer', 'name' => 'Monthly Starter Plan', 'price' => '649', 'priceCurrency' => 'INR'],
                ['@type' => 'Offer', 'name' => 'Lifetime License', 'price' => '14999', 'priceCurrency' => 'INR'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $schemaJson !!}</script>
</head>
<body class="min-h-screen bg-base-100 text-base-content antialiased flex flex-col selection:bg-emerald-500 selection:text-white">

    {{-- Top Notification Bar --}}
    <div class="bg-emerald-900 text-white text-[0.75rem] py-1.5 px-4 text-center font-medium">
        <span>🚀 100% Official Meta Cloud API &bull; Direct Meta Card Billing &bull; Zero Number Ban Risk</span>
    </div>

    {{-- Main Navbar --}}
    <header class="navbar bg-base-100/90 backdrop-blur-md sticky top-0 z-50 border-b border-base-200 px-4 sm:px-8">
        <div class="navbar-start gap-2">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                    <i class="ti ti-brand-whatsapp"></i>
                </div>
                <div class="leading-tight">
                    <span class="font-bold text-base sm:text-lg tracking-tight">{{ config('app.name', 'AHV WhatsApp') }}</span>
                    <span class="text-[0.65rem] block font-semibold text-emerald-600 uppercase tracking-widest">Official Meta API</span>
                </div>
            </a>
        </div>

        <div class="navbar-center hidden md:flex">
            <ul class="menu menu-horizontal px-1 font-semibold text-xs gap-1">
                <li><a href="#services">Services</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#contact-us">Contact Us</a></li>
            </ul>
        </div>

        <div class="navbar-end gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm gap-1.5 text-xs">
                    <i class="ti ti-layout-dashboard"></i> Dashboard
                </a>
            @else
                <a href="#contact-us" class="btn btn-primary btn-sm gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white border-0">
                    <i class="ti ti-mail-fast"></i> Request Access
                </a>
            @endauth
        </div>
    </header>

    {{-- Hero Section --}}
    <section class="py-14 sm:py-20 px-4 sm:px-6 relative overflow-hidden bg-gradient-to-b from-emerald-50/40 via-base-100 to-base-100 border-b border-base-200">
        <div class="max-w-4xl mx-auto text-center space-y-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-semibold shadow-xs">
                <i class="ti ti-shield-check text-emerald-600"></i>
                Official WhatsApp Cloud API Partner Architecture
            </div>

            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-base-content leading-tight">
                Scale Your Marketing With <span class="text-emerald-600">Official WhatsApp</span> Cloud Broadcasts
            </h1>

            <p class="text-sm sm:text-base opacity-75 max-w-2xl mx-auto leading-relaxed">
                Connect your Meta WhatsApp Business Account directly. Send broadcast campaigns, manage approved templates, converse with customers 1-on-1, and <strong>pay Meta directly from your card with ₹0 markup</strong>.
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20am%20interested%20in%20onboarding%20and%20accessing%20the%20WhatsApp%20Cloud%20Portal."
                   target="_blank" rel="noopener noreferrer"
                   class="btn btn-primary btn-md px-6 shadow-md gap-2 bg-emerald-600 hover:bg-emerald-700 text-white border-0 text-xs sm:text-sm">
                    <i class="ti ti-brand-whatsapp text-xl"></i> Request Access on WhatsApp
                </a>
                <a href="#pricing" class="btn btn-outline btn-md px-6 gap-2 text-xs sm:text-sm">
                    <i class="ti ti-tags"></i> View Pricing (Starts ₹649)
                </a>
            </div>

            {{-- Trust Indicators --}}
            <div class="pt-8 grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-3xl mx-auto text-left">
                <div class="p-3 rounded-xl bg-base-100 border border-base-300 shadow-2xs flex items-center gap-2.5">
                    <i class="ti ti-credit-card text-xl text-emerald-600 shrink-0"></i>
                    <div>
                        <div class="font-bold text-xs">Direct Card Billing</div>
                        <div class="text-[0.68rem] opacity-60">Charged by Meta directly</div>
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-base-100 border border-base-300 shadow-2xs flex items-center gap-2.5">
                    <i class="ti ti-checks text-xl text-emerald-600 shrink-0"></i>
                    <div>
                        <div class="font-bold text-xs">99.8% Delivery</div>
                        <div class="text-[0.68rem] opacity-60">High inbox placement</div>
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-base-100 border border-base-300 shadow-2xs flex items-center gap-2.5">
                    <i class="ti ti-lock text-xl text-emerald-600 shrink-0"></i>
                    <div>
                        <div class="font-bold text-xs">Multi-Tenant Scoped</div>
                        <div class="text-[0.68rem] opacity-60">Isolated private data</div>
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-base-100 border border-base-300 shadow-2xs flex items-center gap-2.5">
                    <i class="ti ti-circle-check text-xl text-emerald-600 shrink-0"></i>
                    <div>
                        <div class="font-bold text-xs">Green Tick Ready</div>
                        <div class="text-[0.68rem] opacity-60">Verified business profile</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Services Status Section --}}
    <section id="services" class="py-14 px-4 sm:px-8 max-w-5xl mx-auto space-y-6">
        <div class="text-center space-y-1.5">
            <span class="badge badge-success badge-sm text-white uppercase font-bold tracking-wider">Multi-Channel Suite</span>
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">Our Communication Services</h2>
            <p class="text-xs sm:text-sm opacity-60 max-w-lg mx-auto">
                Official WhatsApp Marketing is live and active today. Next-gen SMS and AI Voice calling agents are launching soon.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- WhatsApp (Active) --}}
            <div class="card bg-base-100 border-2 border-emerald-500 shadow-sm relative overflow-hidden">
                <div class="card-body p-5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl">
                            <i class="ti ti-brand-whatsapp"></i>
                        </div>
                        <span class="badge badge-success badge-sm font-bold text-white gap-1">
                            <i class="ti ti-point-filled"></i> Available &amp; Live
                        </span>
                    </div>
                    <h3 class="font-bold text-base">WhatsApp Cloud API Broadcasts</h3>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Official Meta Cloud API broadcast engine. Send rich media templates, converse in real-time with 2-way inbox, and pay Meta directly.
                    </p>
                    <ul class="text-[0.75rem] space-y-1 opacity-80 pt-1">
                        <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Bulk Marketing Campaigns</li>
                        <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Live 2-Way Multi-Agent Chat</li>
                        <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Real-time Read &amp; Delivery DLR</li>
                    </ul>
                </div>
            </div>

            {{-- SMS Gateway (Coming Soon) --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm relative overflow-hidden opacity-90">
                <div class="card-body p-5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xl">
                            <i class="ti ti-message-dots"></i>
                        </div>
                        <span class="badge badge-warning badge-sm font-semibold gap-1">
                            <i class="ti ti-clock"></i> Coming Soon
                        </span>
                    </div>
                    <h3 class="font-bold text-base">Enterprise SMS Gateway</h3>
                    <p class="text-xs opacity-75 leading-relaxed">
                        DLT-compliant SMS engine for India telecom routes. Register 6-character Alpha sender headers and send fast OTPs &amp; promotional blasts.
                    </p>
                    <ul class="text-[0.75rem] space-y-1 opacity-80 pt-1">
                        <li class="flex items-center gap-1.5"><i class="ti ti-clock text-warning"></i> DLT Header &amp; Template Sync</li>
                        <li class="flex items-center gap-1.5"><i class="ti ti-clock text-warning"></i> Instant OTPs (&lt; 2s delivery)</li>
                        <li class="flex items-center gap-1.5"><i class="ti ti-clock text-warning"></i> Auto DND Number Scrubbing</li>
                    </ul>
                </div>
            </div>

            {{-- AI Voice Calling (Coming Soon) --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm relative overflow-hidden opacity-90">
                <div class="card-body p-5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xl">
                            <i class="ti ti-phone-outgoing"></i>
                        </div>
                        <span class="badge badge-warning badge-sm font-semibold gap-1">
                            <i class="ti ti-clock"></i> Coming Soon
                        </span>
                    </div>
                    <h3 class="font-bold text-base">AI Voice Calling Agent</h3>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Automated outbound voice broadcast dialler and conversational AI agent for interactive reminders, announcements, and customer engagement.
                    </p>
                    <ul class="text-[0.75rem] space-y-1 opacity-80 pt-1">
                        <li class="flex items-center gap-1.5"><i class="ti ti-clock text-warning"></i> Automated Voice Broadcasts</li>
                        <li class="flex items-center gap-1.5"><i class="ti ti-clock text-warning"></i> Call Duration &amp; Pickup Analytics</li>
                        <li class="flex items-center gap-1.5"><i class="ti ti-clock text-warning"></i> DTMF Keypress Feedback</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Core WhatsApp Features --}}
    <section id="features" class="py-12 px-4 sm:px-8 bg-base-200/50 border-y border-base-300">
        <div class="max-w-5xl mx-auto space-y-8">
            <div class="text-center space-y-1.5">
                <span class="text-xs uppercase font-extrabold tracking-widest text-emerald-600">Platform Capabilities</span>
                <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">Built For High ROI Marketing</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card bg-base-100 border border-base-300 p-4 space-y-2">
                    <i class="ti ti-send text-2xl text-emerald-600"></i>
                    <h4 class="font-bold text-sm">Bulk Broadcasts</h4>
                    <p class="text-xs opacity-70">Schedule and dispatch personalized marketing broadcasts to thousands of contacts with zero number ban risk.</p>
                </div>
                <div class="card-body bg-base-100 border border-base-300 p-4 rounded-2xl space-y-2">
                    <i class="ti ti-messages text-2xl text-emerald-600"></i>
                    <h4 class="font-bold text-sm">Live 2-Way Chat</h4>
                    <p class="text-xs opacity-70">Unified team inbox to converse with inbound customer replies in real-time, send media, and close sales.</p>
                </div>
                <div class="card-body bg-base-100 border border-base-300 p-4 rounded-2xl space-y-2">
                    <i class="ti ti-template text-2xl text-emerald-600"></i>
                    <h4 class="font-bold text-sm">Approved Templates</h4>
                    <p class="text-xs opacity-70">Sync and submit Meta-approved message templates with buttons, custom parameters, and media headers.</p>
                </div>
                <div class="card-body bg-base-100 border border-base-300 p-4 rounded-2xl space-y-2">
                    <i class="ti ti-credit-card text-2xl text-emerald-600"></i>
                    <h4 class="font-bold text-sm">Direct Meta Billing</h4>
                    <p class="text-xs opacity-70">Pay Meta directly from your credit/debit card. Zero middleman commission on conversation fees.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Transparent Pricing Section --}}
    <section id="pricing" class="py-14 px-4 sm:px-8 max-w-5xl mx-auto space-y-10">
        <div class="text-center space-y-1.5">
            <span class="text-xs uppercase font-extrabold tracking-widest text-emerald-600">Pricing &amp; Plans</span>
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">Simple, Transparent Pricing</h2>
            <p class="text-xs sm:text-sm opacity-60 max-w-lg mx-auto">
                Flat software access fee. Monthly WhatsApp dashboard starts at just ₹649. Pay Meta official message fees directly at cost.
            </p>
        </div>

        {{-- Fixed Meta Setup Card (CSS Grid - Never squished) --}}
        <div class="card bg-gradient-to-r from-emerald-500/10 via-base-100 to-base-100 border-2 border-emerald-500/40 p-6 sm:p-7 rounded-2xl shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                <div class="md:col-span-2 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="badge badge-success badge-sm font-bold text-white">One-Time Setup Fee (Fix)</span>
                        <span class="badge badge-outline badge-sm font-semibold">Standard Mandatory Onboarding</span>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold">Meta Business Verification &amp; API Integration</h3>
                    <p class="text-xs sm:text-sm opacity-75 leading-relaxed">
                        Complete technical onboarding executed directly by AHV specialists: Meta WhatsApp Business Account (WABA) verification, Cloud API integration, permanent webhook setup, number registration, and broadcast template approvals.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-xs">
                        <div class="flex items-center gap-1.5"><i class="ti ti-check text-emerald-600 font-bold"></i> Meta WABA Verification Support</div>
                        <div class="flex items-center gap-1.5"><i class="ti ti-check text-emerald-600 font-bold"></i> Cloud API &amp; Webhook Provisioning</div>
                        <div class="flex items-center gap-1.5"><i class="ti ti-check text-emerald-600 font-bold"></i> Green Tick Readiness Check</div>
                        <div class="flex items-center gap-1.5"><i class="ti ti-check text-emerald-600 font-bold"></i> Template Approval Assistance</div>
                    </div>
                </div>

                <div class="md:col-span-1 bg-base-100 p-5 rounded-2xl border border-base-300 text-center shadow-xs">
                    <div class="text-[0.65rem] uppercase tracking-wider font-semibold opacity-60">Fixed Setup Charge</div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-emerald-600 font-mono my-0.5">₹4,500</div>
                    <div class="text-[0.7rem] opacity-60 mb-3">One-Time Setup Fee (Fix)</div>
                    <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20am%20ready%20for%20the%20Meta%20WhatsApp%20Business%20Verification%20and%20API%20Integration%20Setup%20(₹4,500%20Fix)."
                       target="_blank" rel="noopener noreferrer"
                       class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-sm w-full gap-1 text-xs">
                        <i class="ti ti-brand-whatsapp"></i> Book Setup Onboarding
                    </a>
                </div>
            </div>
        </div>

        {{-- Software Dashboard Plans Grid --}}
        <div>
            <div class="text-center mb-6">
                <h4 class="text-base font-bold">Choose Your WhatsApp Dashboard Plan</h4>
                <p class="text-xs opacity-60">All plans include unlimited broadcast campaigns, template syncing, and live chat inbox.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- 1 Month Plan (Starts at ₹649) --}}
                <div class="card bg-base-100 border-2 border-emerald-500/80 shadow-sm flex flex-col justify-between">
                    <div class="card-body p-5">
                        <span class="badge badge-success badge-xs font-bold text-white self-start mb-1">Starter Choice</span>
                        <h4 class="font-bold text-base">Monthly Plan</h4>
                        <div class="my-2">
                            <span class="text-3xl font-extrabold font-mono text-emerald-600">₹649</span>
                            <span class="text-xs opacity-60">/ 30 days</span>
                        </div>
                        <p class="text-xs opacity-70 mb-3">Affordable monthly access for small business broadcasts and promotions.</p>
                        <ul class="text-xs space-y-1.5 opacity-80">
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Broadcast Campaigns</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Live Chat 2-Way Inbox</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Template Management</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Direct Card Meta Billing</li>
                        </ul>
                    </div>
                    <div class="p-5 pt-0">
                        <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20am%20interested%20in%20the%20Monthly%20Plan%20(₹649)%20for%20WhatsApp%20Cloud%20Portal."
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-sm w-full gap-1 text-xs">
                            <i class="ti ti-brand-whatsapp"></i> Get Monthly Plan
                        </a>
                    </div>
                </div>

                {{-- 6 Months Plan --}}
                <div class="card bg-base-100 border border-base-300 shadow-sm flex flex-col justify-between">
                    <div class="card-body p-5">
                        <h4 class="font-bold text-base">6 Months Plan</h4>
                        <div class="my-2">
                            <span class="text-3xl font-extrabold font-mono">₹3,499</span>
                            <span class="text-xs opacity-60">/ 180 days</span>
                        </div>
                        <p class="text-xs opacity-70 mb-3">Popular choice for growing retail stores, clinics, and marketing agencies.</p>
                        <ul class="text-xs space-y-1.5 opacity-80">
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Everything in 1 Month</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Unlimited Contact Groups</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> CSV Contact Importer</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Priority Tech Support</li>
                        </ul>
                    </div>
                    <div class="p-5 pt-0">
                        <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20am%20interested%20in%20the%206%20Months%20Plan%20(₹3,499)%20for%20WhatsApp%20Cloud%20Portal."
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-outline btn-sm w-full gap-1 text-xs">
                            <i class="ti ti-brand-whatsapp text-emerald-600"></i> Inquire for 6 Months
                        </a>
                    </div>
                </div>

                {{-- 1 Year Plan --}}
                <div class="card bg-base-100 border border-base-300 shadow-sm flex flex-col justify-between">
                    <div class="card-body p-5">
                        <h4 class="font-bold text-base">1 Year Plan</h4>
                        <div class="my-2">
                            <span class="text-3xl font-extrabold font-mono">₹5,999</span>
                            <span class="text-xs opacity-60">/ 365 days</span>
                        </div>
                        <p class="text-xs opacity-70 mb-3">Best value for year-round brand awareness and repeat customer retention.</p>
                        <ul class="text-xs space-y-1.5 opacity-80">
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Full Year Dashboard Access</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> High-Speed Delivery Priority</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Dedicated Setup Assistance</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Multi-Tenant Privacy</li>
                        </ul>
                    </div>
                    <div class="p-5 pt-0">
                        <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20am%20interested%20in%20the%201%20Year%20Plan%20(₹5,999)%20for%20WhatsApp%20Cloud%20Portal."
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-outline btn-sm w-full gap-1 text-xs">
                            <i class="ti ti-brand-whatsapp text-emerald-600"></i> Inquire for 1 Year
                        </a>
                    </div>
                </div>

                {{-- Lifetime License --}}
                <div class="card bg-base-100 border-2 border-emerald-600 shadow-sm flex flex-col justify-between">
                    <div class="card-body p-5">
                        <span class="badge badge-success badge-xs font-bold text-white self-start mb-1">⭐ Lifetime Deal</span>
                        <h4 class="font-bold text-base">Lifetime License</h4>
                        <div class="my-2">
                            <span class="text-3xl font-extrabold font-mono text-emerald-600">₹14,999</span>
                            <span class="text-xs opacity-60">/ one-time</span>
                        </div>
                        <p class="text-xs opacity-70 mb-3">One-time payment. Permanent software access with zero recurring renewal fees.</p>
                        <ul class="text-xs space-y-1.5 opacity-80">
                            <li class="flex items-center gap-1.5"><i class="ti ti-circle-check text-emerald-600"></i> Permanent Lifetime Validity</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-circle-check text-emerald-600"></i> Zero Renewal Prompts</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-circle-check text-emerald-600"></i> All Future Core Updates</li>
                            <li class="flex items-center gap-1.5"><i class="ti ti-circle-check text-emerald-600"></i> Early Access to AI Chatbots</li>
                        </ul>
                    </div>
                    <div class="p-5 pt-0">
                        <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20want%20to%20claim%20the%20Lifetime%20Access%20License%20(₹14,999)%20for%20WhatsApp%20Cloud%20Portal."
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-sm w-full gap-1 text-xs">
                            <i class="ti ti-brand-whatsapp"></i> Get Lifetime Access
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-base-200 border border-base-300 text-center text-xs opacity-75 max-w-lg mx-auto mt-6">
                <i class="ti ti-info-circle text-primary"></i> Manual payment verification via <strong>UPI QR Code or Direct Bank Transfer</strong> with instant UTR verification by AHV team.
            </div>
        </div>
    </section>

    {{-- Contact Us / Request Access Section with Bot Security Captcha --}}
    @php
        $cNum1 = rand(2, 8);
        $cNum2 = rand(1, 9);
        session(['inquiry_captcha_num1' => $cNum1, 'inquiry_captcha_num2' => $cNum2]);
    @endphp
    <section id="contact-us" class="py-14 px-4 sm:px-8 max-w-5xl mx-auto space-y-6">
        <div class="card bg-gradient-to-br from-emerald-500/10 via-base-100 to-base-100 border-2 border-emerald-500/30 p-6 sm:p-8 rounded-3xl shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div class="lg:col-span-5 space-y-4">
                    <span class="badge badge-success badge-sm font-semibold text-white">Closed Enterprise Access</span>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">Request Platform Onboarding</h2>
                    <p class="text-xs sm:text-sm opacity-75 leading-relaxed">
                        To maintain 100% Meta Cloud API compliance, pristine number quality rating, and multi-tenant security, <strong>AHV WhatsApp Portal</strong> accounts are provisioned directly on request.
                    </p>
                    <p class="text-xs sm:text-sm opacity-75 leading-relaxed">
                        Each organization receives a dedicated workspace with verified WABA credentials, phone number routing, and custom sender isolation.
                    </p>

                    <div class="p-4 rounded-2xl bg-base-100 border border-base-300 space-y-2">
                        <div class="font-bold text-xs flex items-center gap-1.5 text-emerald-700">
                            <i class="ti ti-brand-whatsapp text-base"></i> Direct WhatsApp Inquiry
                        </div>
                        <p class="text-[0.75rem] opacity-75 leading-relaxed">
                            Need immediate setup or have existing Meta Business Manager questions? Chat directly with our technical onboarding leads:
                        </p>
                        <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20would%20like%20to%20request%20onboarding%20access%20to%20the%20WhatsApp%20Cloud%20Portal."
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-xs w-full gap-1.5 shadow-xs py-1 text-xs">
                            <i class="ti ti-brand-whatsapp"></i> Chat on WhatsApp (+91 81040 55511)
                        </a>
                    </div>
                </div>

                {{-- Contact Us Form with Bot Security (Captcha) --}}
                <div id="contact-form" class="lg:col-span-7 card bg-base-100 border border-base-300 p-5 sm:p-6 space-y-4 shadow-sm">
                    <div>
                        <h3 class="font-bold text-base">Contact Us &bull; Request Workspace Access</h3>
                        <p class="text-xs opacity-70">Fill out your details below. Our onboarding team reviews and activates accounts within 2-4 hours.</p>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success text-xs text-white p-3 rounded-xl shadow-xs flex items-center gap-2">
                            <i class="ti ti-circle-check text-lg shrink-0"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-error text-xs text-white p-3 rounded-xl shadow-xs">
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.inquiry.store') }}" class="space-y-3">
                        @csrf
                        {{-- Honeypot security field: hidden from humans, bots fill it out --}}
                        <input type="text" name="b_hp_security_check" style="display:none !important;" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label py-0.5 text-xs font-semibold">Your Name <span class="text-error">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Rahul Sharma"
                                       class="input input-sm input-bordered text-xs w-full">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5 text-xs font-semibold">Phone / WhatsApp Number <span class="text-error">*</span></label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="e.g. +91 98765 43210"
                                       class="input input-sm input-bordered text-xs w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label py-0.5 text-xs font-semibold">Business / Brand Name</label>
                                <input type="text" name="business_name" value="{{ old('business_name') }}" placeholder="e.g. Fit Ganganagar"
                                       class="input input-sm input-bordered text-xs w-full">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5 text-xs font-semibold">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="e.g. contact@business.com"
                                       class="input input-sm input-bordered text-xs w-full">
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5 text-xs font-semibold">Service You Need <span class="text-error">*</span></label>
                            <select name="service_interested" class="select select-sm select-bordered text-xs w-full" required>
                                <option value="whatsapp" {{ old('service_interested') === 'whatsapp' ? 'selected' : '' }}>
                                    WhatsApp Official Cloud API &amp; Broadcasts (Available &amp; Live)
                                </option>
                                <option value="sms" {{ old('service_interested') === 'sms' ? 'selected' : '' }}>
                                    Enterprise SMS Gateway &amp; DLT Route (Coming Soon)
                                </option>
                                <option value="voice" {{ old('service_interested') === 'voice' ? 'selected' : '' }}>
                                    AI Voice Calling Agent &amp; Dialler (Coming Soon)
                                </option>
                                <option value="all" {{ old('service_interested') === 'all' ? 'selected' : '' }}>
                                    Full CPaaS Suite (WhatsApp + SMS + Voice)
                                </option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5 text-xs font-semibold">Message / Expected Volume</label>
                            <textarea name="message" rows="2" placeholder="Tell us about your business or expected broadcast volume..."
                                      class="textarea textarea-bordered text-xs w-full">{{ old('message') }}</textarea>
                        </div>

                        {{-- Anti-Bot Security Captcha --}}
                        <div class="p-2.5 rounded-xl bg-base-200 border border-base-300 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2">
                                <i class="ti ti-shield-check text-emerald-600 text-base"></i>
                                <span class="text-xs font-semibold">Security Check:</span>
                                <span class="badge badge-neutral font-mono font-bold text-xs">{{ $cNum1 }} + {{ $cNum2 }} = ?</span>
                            </div>
                            <div class="w-full sm:w-28">
                                <input type="number" name="captcha_answer" required placeholder="Answer"
                                       class="input input-sm input-bordered text-xs w-full font-mono text-center font-bold">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-full gap-2 bg-emerald-600 hover:bg-emerald-700 text-white border-0 shadow-sm text-xs">
                            <i class="ti ti-send"></i> Submit Onboarding Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="mt-auto bg-base-100 border-t border-base-200 py-8 px-4 sm:px-8 text-xs opacity-75">
        <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2 font-semibold">
                <i class="ti ti-brand-whatsapp text-lg text-emerald-600"></i>
                <span>AHV WhatsApp Portal &bull; AHV Software Technologies</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="#services" class="hover:underline">Services</a>
                <a href="#features" class="hover:underline">Features</a>
                <a href="#pricing" class="hover:underline">Pricing</a>
                <a href="#contact-us" class="hover:underline">Contact Us</a>
            </div>
            <div>
                &copy; {{ date('Y') }} AHV Software. All rights reserved. Official Meta Cloud API Partner.
            </div>
        </div>
    </footer>

</body>
</html>
