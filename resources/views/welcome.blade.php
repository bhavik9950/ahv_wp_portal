<x-public-layout>
    <x-slot name="title">AHV WhatsApp Portal — Official Meta Cloud API WhatsApp Marketing Platform</x-slot>

    {{-- Clean Hero Section --}}
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
                <a href="{{ route('public.pricing') }}" class="btn btn-outline btn-md px-6 gap-2 text-xs sm:text-sm">
                    <i class="ti ti-tags"></i> View Pricing (Starts ₹649)
                </a>
            </div>

            {{-- Trust Badges --}}
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

    {{-- Services Snapshot --}}
    <section class="py-14 px-4 sm:px-8 max-w-5xl mx-auto space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <span class="badge badge-success badge-sm text-white uppercase font-bold tracking-wider">Multi-Channel Suite</span>
                <h2 class="text-2xl font-bold tracking-tight mt-1">Our Communication Platforms</h2>
            </div>
            <a href="{{ route('public.services') }}" class="link link-primary text-xs font-semibold flex items-center gap-1">
                Explore All Services &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            {{-- WhatsApp (Active) --}}
            <div class="card bg-base-100 border-2 border-emerald-500 shadow-sm">
                <div class="card-body p-5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl">
                            <i class="ti ti-brand-whatsapp"></i>
                        </div>
                        <span class="badge badge-success badge-sm font-bold text-white">Active &amp; Live</span>
                    </div>
                    <h3 class="font-bold text-base">WhatsApp Cloud API Broadcasts</h3>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Official Meta Cloud API marketing. Send rich templates, converse 1-on-1 with live 2-way inbox, and pay Meta directly with zero markup.
                    </p>
                    <div class="pt-2">
                        <a href="{{ route('public.services') }}" class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-xs w-full gap-1">
                            Learn More &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- SMS Gateway (Coming Soon) --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm opacity-90">
                <div class="card-body p-5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xl">
                            <i class="ti ti-message-dots"></i>
                        </div>
                        <span class="badge badge-warning badge-sm font-semibold">Coming Soon</span>
                    </div>
                    <h3 class="font-bold text-base">Enterprise SMS Gateway</h3>
                    <p class="text-xs opacity-75 leading-relaxed">
                        DLT-compliant SMS engine for India telecom routes. Register 6-character Alpha sender headers and send fast OTPs &amp; promotional blasts.
                    </p>
                    <div class="pt-2">
                        <a href="{{ route('public.services') }}" class="btn btn-outline btn-xs w-full gap-1">
                            View Roadmap &rarr;
                        </a>
                    </div>
                </div>
            </div>

            {{-- AI Voice Calling (Coming Soon) --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm opacity-90">
                <div class="card-body p-5 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-xl">
                            <i class="ti ti-phone-outgoing"></i>
                        </div>
                        <span class="badge badge-warning badge-sm font-semibold">Coming Soon</span>
                    </div>
                    <h3 class="font-bold text-base">AI Voice Calling Agent</h3>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Automated outbound voice broadcast dialler and conversational AI agent for interactive reminders, announcements, and customer engagement.
                    </p>
                    <div class="pt-2">
                        <a href="{{ route('public.services') }}" class="btn btn-outline btn-xs w-full gap-1">
                            View Roadmap &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing Teaser --}}
    <section class="py-12 px-4 sm:px-8 bg-base-200/50 border-y border-base-300">
        <div class="max-w-4xl mx-auto text-center space-y-4">
            <span class="text-xs uppercase font-extrabold tracking-widest text-emerald-600">Affordable Software Pricing</span>
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">Monthly Plans Starting at Just ₹649</h2>
            <p class="text-xs sm:text-sm opacity-75 max-w-xl mx-auto leading-relaxed">
                No per-message software commission. Choose from monthly (₹649), half-yearly, annual, or permanent lifetime licenses with direct Meta card billing.
            </p>
            <div class="pt-2">
                <a href="{{ route('public.pricing') }}" class="btn btn-primary btn-sm px-6 bg-emerald-600 hover:bg-emerald-700 text-white border-0 gap-1.5 shadow-sm">
                    <i class="ti ti-tags"></i> View Detailed Pricing Plans &rarr;
                </a>
            </div>
        </div>
    </section>

    {{-- Quick CTA Banner to Contact --}}
    <section class="py-14 px-4 sm:px-8 max-w-4xl mx-auto">
        <div class="card bg-gradient-to-r from-emerald-600 to-emerald-800 text-white p-6 sm:p-8 rounded-3xl shadow-md text-center space-y-4">
            <h2 class="text-2xl sm:text-3xl font-bold">Ready to Launch Your WhatsApp Broadcasts?</h2>
            <p class="text-xs sm:text-sm opacity-90 max-w-lg mx-auto">
                Request your enterprise workspace today. We help verify your Meta Business Account and integrate the Cloud API within 24 hours.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
                <a href="{{ route('public.contact') }}" class="btn btn-white bg-white text-emerald-800 hover:bg-emerald-50 btn-sm px-6 font-bold border-0">
                    <i class="ti ti-mail-fast"></i> Request Access / Contact Us
                </a>
                <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20would%20like%20to%20inquire%20about%20the%20WhatsApp%20Cloud%20Portal."
                   target="_blank" rel="noopener noreferrer"
                   class="btn btn-outline border-white text-white hover:bg-white/10 btn-sm px-5">
                    <i class="ti ti-brand-whatsapp text-lg"></i> WhatsApp +91 81040 55511
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
