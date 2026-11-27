@php($lowestPrice = app(\App\Services\System\SystemSettings::class)->lowestPlanPrice())
<x-public-layout>
    <x-slot name="title">AHV WhatsApp Portal — Official Meta Cloud API WhatsApp Marketing Platform</x-slot>
    <x-slot name="description">Official Meta WhatsApp Cloud API software for high-deliverability broadcast campaigns, 2-way live chat inbox, and direct Meta card billing with ₹0 markup. Plans start at ₹{{ number_format($lowestPrice) }}/mo.</x-slot>

    {{-- 1. Hero Section (Ultra-Responsive: 360px Mobile to 4K Desktop + AEO Semantic Structure) --}}
    <section class="py-12 sm:py-20 lg:py-28 px-3 sm:px-6 lg:px-8 relative overflow-hidden bg-gradient-to-b from-emerald-50/60 via-white to-white border-b border-slate-200/80">
        {{-- Subtle radial background decoration --}}
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-80 sm:h-96 bg-gradient-to-b from-emerald-100/40 to-transparent blur-3xl pointer-events-none -z-10"></div>

        <div class="max-w-5xl mx-auto text-center space-y-5 sm:space-y-8">
            {{-- Top Badge --}}
            <div class="inline-flex items-center gap-2 px-3 py-1 sm:px-4 sm:py-1.5 rounded-full bg-emerald-50 border border-emerald-200/90 text-emerald-800 text-[0.68rem] sm:text-xs font-semibold shadow-2xs max-w-full">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                <span class="truncate">Official Meta WhatsApp Cloud API Partner Architecture</span>
            </div>

            {{-- Main Headline (Mobile wrap-friendly & responsive) --}}
            <h1 class="text-2xl xs:text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.2] sm:leading-[1.15]">
                Enterprise Customer Reach With <br class="hidden sm:inline" />
                <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">Official WhatsApp Cloud API</span>
            </h1>

            {{-- Subtitle (AEO Optimized concise definition) --}}
            <p class="text-xs sm:text-base lg:text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed font-normal px-2">
                Connect directly to your Meta WhatsApp Business Account. Send high-converting broadcast campaigns, converse 1-on-1 through a multi-agent live inbox, and <strong>pay Meta directly from your card with ₹0 middleman markup</strong>.
            </p>

            {{-- Action Buttons (Full-width on mobile, auto-width on tablet/desktop) --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 pt-2 max-w-md mx-auto sm:max-w-none">
                <a href="https://wa.me/917878159887?text=Hello%20AHV%20Software,%20I%20am%20interested%20in%20onboarding%20and%20accessing%20the%20WhatsApp%20Cloud%20Portal."
                   target="_blank" rel="noopener noreferrer"
                   class="btn btn-primary min-h-[44px] px-6 sm:px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white border-0 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-bold shadow-md shadow-emerald-600/20 gap-2 sm:gap-2.5 justify-center">
                    <i class="ti ti-brand-whatsapp text-xl"></i> Request Access on WhatsApp
                </a>
                <a href="{{ route('public.pricing') }}"
                   class="btn btn-outline min-h-[44px] px-6 sm:px-8 py-3 border-slate-300 hover:border-slate-400 bg-white hover:bg-slate-50 text-slate-700 rounded-xl sm:rounded-2xl text-xs sm:text-sm font-bold shadow-2xs gap-2 justify-center">
                    <i class="ti ti-tags text-base text-emerald-600"></i> View Pricing (Starts ₹{{ number_format($lowestPrice) }})
                </a>
            </div>

            {{-- Trust & Key Numbers Strip (Responsive: 2-col on mobile, 4-col on tablet/desktop) --}}
            <div class="pt-8 sm:pt-14 grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-6 max-w-4xl mx-auto text-center">
                <div class="p-3.5 sm:p-6 rounded-xl sm:rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-0.5 sm:space-y-1">
                    <div class="text-xl sm:text-3xl font-extrabold text-emerald-600">99.8%</div>
                    <div class="text-[0.72rem] sm:text-xs font-bold text-slate-800">Delivery Placement</div>
                    <div class="text-[0.65rem] sm:text-[0.72rem] text-slate-500">Official Cloud API</div>
                </div>
                <div class="p-3.5 sm:p-6 rounded-xl sm:rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-0.5 sm:space-y-1">
                    <div class="text-xl sm:text-3xl font-extrabold text-slate-900">₹0 Markup</div>
                    <div class="text-[0.72rem] sm:text-xs font-bold text-slate-800">Direct Meta Billing</div>
                    <div class="text-[0.65rem] sm:text-[0.72rem] text-slate-500">Pay Meta directly</div>
                </div>
                <div class="p-3.5 sm:p-6 rounded-xl sm:rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-0.5 sm:space-y-1">
                    <div class="text-xl sm:text-3xl font-extrabold text-emerald-600">0 Ban Risk</div>
                    <div class="text-[0.72rem] sm:text-xs font-bold text-slate-800">100% Policy Compliant</div>
                    <div class="text-[0.65rem] sm:text-[0.72rem] text-slate-500">No scraping tools</div>
                </div>
                <div class="p-3.5 sm:p-6 rounded-xl sm:rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-0.5 sm:space-y-1">
                    <div class="text-xl sm:text-3xl font-extrabold text-slate-900">2-Way</div>
                    <div class="text-[0.72rem] sm:text-xs font-bold text-slate-800">Live Support Inbox</div>
                    <div class="text-[0.65rem] sm:text-[0.72rem] text-slate-500">Multi-agent shared</div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Multi-Channel Suite (Spacious, Responsive, MSG91-Style Cards) --}}
    <section class="py-14 sm:py-24 px-3 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-10 sm:space-y-14">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto space-y-2.5 sm:space-y-3 px-2">
            <span class="inline-block text-[0.7rem] sm:text-xs font-extrabold tracking-widest text-emerald-600 uppercase">
                Enterprise Communication Suite
            </span>
            <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                Channels Built for Massive Conversions
            </h2>
            <p class="text-xs sm:text-base text-slate-600 leading-relaxed">
                Connect with customers across multiple verified channels with high delivery speed, enterprise isolation, and real-time observability.
            </p>
        </div>

        {{-- 3 Product Cards (Responsive: 1-col on mobile, 3-col on desktop) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 sm:gap-8">
            {{-- WhatsApp (Active & Live) --}}
            <div class="rounded-2xl sm:rounded-3xl bg-white border-2 border-emerald-500 shadow-md p-5 sm:p-8 lg:p-10 flex flex-col justify-between space-y-5 hover:shadow-xl transition-all">
                <div class="space-y-4 sm:space-y-5">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-emerald-100/80 text-emerald-700 flex items-center justify-center text-2xl sm:text-3xl font-bold shadow-2xs">
                            <i class="ti ti-brand-whatsapp"></i>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[0.7rem] sm:text-xs font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Live &amp; Production Ready
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900">WhatsApp Cloud API</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Official Meta Cloud API broadcast engine. Reach thousands of opted-in customers with verified templates, rich media, and 2-way live chat.
                        </p>
                    </div>

                    <ul class="space-y-2 sm:space-y-3 text-xs sm:text-sm text-slate-700 pt-3 border-t border-slate-100">
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-emerald-600 font-bold shrink-0"></i>
                            <span>High-volume promotional broadcasts</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-emerald-600 font-bold shrink-0"></i>
                            <span>2-way live chat inbox for support agents</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-emerald-600 font-bold shrink-0"></i>
                            <span>Template approvals &amp; real-time webhooks</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-emerald-600 font-bold shrink-0"></i>
                            <span>Direct card billing to Meta (₹0 markup)</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <a href="{{ route('public.services') }}" class="btn btn-primary w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold py-2.5 gap-2 shadow-sm min-h-[40px] justify-center">
                        Explore WhatsApp Features &rarr;
                    </a>
                </div>
            </div>

            {{-- Enterprise SMS Gateway (Coming Soon) --}}
            <div class="rounded-2xl sm:rounded-3xl bg-white border border-slate-200/90 shadow-sm p-5 sm:p-8 lg:p-10 flex flex-col justify-between space-y-5 hover:border-amber-300 hover:shadow-md transition-all">
                <div class="space-y-4 sm:space-y-5">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-amber-100/80 text-amber-700 flex items-center justify-center text-2xl sm:text-3xl font-bold shadow-2xs">
                            <i class="ti ti-message-dots"></i>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 text-[0.7rem] sm:text-xs font-semibold">
                            <i class="ti ti-clock text-xs"></i> Coming Soon
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900">Enterprise SMS Gateway</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            DLT-compliant Indian telecom SMS routing engine. Send mission-critical OTPs, transactional receipts, and mass promotional SMS.
                        </p>
                    </div>

                    <ul class="space-y-2 sm:space-y-3 text-xs sm:text-sm text-slate-700 pt-3 border-t border-slate-100">
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-amber-600 font-bold shrink-0"></i>
                            <span>100% TRAI DLT compliance &amp; header binding</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-amber-600 font-bold shrink-0"></i>
                            <span>2–5 second lightning OTP routing</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-amber-600 font-bold shrink-0"></i>
                            <span>Smart operator failover (Airtel, Jio, Vi)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-amber-600 font-bold shrink-0"></i>
                            <span>Real-time delivery status reports</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <a href="{{ route('public.services') }}" class="btn btn-outline w-full border-slate-300 hover:border-slate-400 text-slate-700 rounded-xl text-xs font-bold py-2.5 gap-2 min-h-[40px] justify-center">
                        View SMS Roadmap &rarr;
                    </a>
                </div>
            </div>

            {{-- AI Voice Calling Agent (Coming Soon) --}}
            <div class="rounded-2xl sm:rounded-3xl bg-white border border-slate-200/90 shadow-sm p-5 sm:p-8 lg:p-10 flex flex-col justify-between space-y-5 hover:border-sky-300 hover:shadow-md transition-all">
                <div class="space-y-4 sm:space-y-5">
                    <div class="flex items-center justify-between">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-sky-100/80 text-sky-700 flex items-center justify-center text-2xl sm:text-3xl font-bold shadow-2xs">
                            <i class="ti ti-phone-outgoing"></i>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-sky-100 text-sky-800 text-[0.7rem] sm:text-xs font-semibold">
                            <i class="ti ti-clock text-xs"></i> Coming Soon
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900">AI Voice Calling Agent</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                            Outbound automated voice broadcasting and interactive conversational AI dialler for payment reminders, feedback, and announcements.
                        </p>
                    </div>

                    <ul class="space-y-2 sm:space-y-3 text-xs sm:text-sm text-slate-700 pt-3 border-t border-slate-100">
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-sky-600 font-bold shrink-0"></i>
                            <span>Human-like conversational AI bots</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-sky-600 font-bold shrink-0"></i>
                            <span>High-capacity outbound dialler</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-sky-600 font-bold shrink-0"></i>
                            <span>Live call recordings &amp; transcriptions</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="ti ti-check text-sky-600 font-bold shrink-0"></i>
                            <span>Custom voice prompts &amp; DTMF menu routing</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <a href="{{ route('public.services') }}" class="btn btn-outline w-full border-slate-300 hover:border-slate-400 text-slate-700 rounded-xl text-xs font-bold py-2.5 gap-2 min-h-[40px] justify-center">
                        View Voice Roadmap &rarr;
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Why Choose AHV Platform (AEO Answer Block & 4-Feature Grid) --}}
    <section class="py-14 sm:py-24 px-3 sm:px-6 lg:px-8 bg-slate-100/70 border-y border-slate-200">
        <div class="max-w-7xl mx-auto space-y-10 sm:space-y-14">
            <div class="text-center max-w-3xl mx-auto space-y-2.5 sm:space-y-3 px-2">
                <span class="inline-block text-[0.7rem] sm:text-xs font-extrabold tracking-widest text-emerald-600 uppercase">
                    Why Businesses Choose Us
                </span>
                <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                    Built for Reliability, Transparency &amp; Zero Risk
                </h2>
                <p class="text-xs sm:text-base text-slate-600 leading-relaxed">
                    Avoid unofficial scraping bots that get your brand's phone numbers permanently banned.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-8">
                {{-- Feature 1 --}}
                <div class="p-6 sm:p-10 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xs space-y-3 sm:space-y-4 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl sm:text-2xl font-bold">
                        <i class="ti ti-credit-card"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900">Direct Meta Card Billing (₹0 Markup)</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Add your credit/debit card directly inside Meta Business Manager. Meta bills you at exact standard conversation rates (~₹0.74 / marketing conversation). We take <strong>₹0 commission or per-message cut</strong>.
                    </p>
                </div>

                {{-- Feature 2 --}}
                <div class="p-6 sm:p-10 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xs space-y-3 sm:space-y-4 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl sm:text-2xl font-bold">
                        <i class="ti ti-shield-check"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900">Zero Ban Guarantee (Official Cloud API)</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Stop risking your business reputation with unofficial QR-code web-automation bots. AHV WhatsApp Portal connects directly to Meta’s official Graph API servers, keeping your sender number 100% compliant and ban-free.
                    </p>
                </div>

                {{-- Feature 3 --}}
                <div class="p-6 sm:p-10 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xs space-y-3 sm:space-y-4 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl sm:text-2xl font-bold">
                        <i class="ti ti-messages"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900">Multi-Agent Live Chat Inbox</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Allow your entire sales and customer support team to converse with incoming leads from one centralized dashboard. Tag contacts, assign conversations, and respond in real-time.
                    </p>
                </div>

                {{-- Feature 4 --}}
                <div class="p-6 sm:p-10 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xs space-y-3 sm:space-y-4 hover:shadow-md transition-shadow">
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl sm:text-2xl font-bold">
                        <i class="ti ti-tool"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-slate-900">Fixed ₹4,500 Onboarding &amp; Verification</h3>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Stuck with Meta Business Manager verification? Our engineering team executes full end-to-end setup: Meta business verification, Cloud API webhook configuration, and initial template approvals.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Transparent Pricing Teaser (Clean, Mobile-Friendly, Links to /pricing) --}}
    <section class="py-14 sm:py-24 px-3 sm:px-6 lg:px-8 max-w-5xl mx-auto">
        <div class="p-6 sm:p-12 lg:p-14 rounded-2xl sm:rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white shadow-xl text-center space-y-4 sm:space-y-6">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-[0.7rem] sm:text-xs font-bold uppercase tracking-wider border border-emerald-500/30">
                Transparent SaaS Pricing
            </span>

            <h2 class="text-2xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">
                Monthly Plans Starting at Just <span class="text-emerald-400">₹{{ number_format($lowestPrice) }}</span>
            </h2>

            <p class="text-xs sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Choose flexible monthly plans, discounted annual subscriptions, or a one-time lifetime license. No hidden per-message markups — pay Meta directly.
            </p>

            <div class="pt-2 sm:pt-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3">
                <a href="{{ route('public.pricing') }}" class="btn btn-primary min-h-[44px] px-6 sm:px-8 bg-emerald-500 hover:bg-emerald-600 text-slate-900 font-extrabold rounded-xl border-0 gap-2 shadow-md justify-center text-xs sm:text-sm">
                    <i class="ti ti-tags text-base"></i> View Detailed Pricing Plans &rarr;
                </a>
                <a href="{{ route('public.contact') }}" class="btn btn-outline min-h-[44px] px-6 sm:px-8 border-slate-600 hover:border-slate-400 text-white hover:bg-white/10 rounded-xl font-bold justify-center text-xs sm:text-sm">
                    Talk to Onboarding Specialist
                </a>
            </div>
        </div>
    </section>

    {{-- 5. FAQ Preview for AEO (Answer Engine Optimization) --}}
    <section class="py-12 sm:py-20 px-3 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8">
        <div class="text-center space-y-2">
            <span class="text-xs font-extrabold text-emerald-600 uppercase tracking-wider">Frequently Asked Questions</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Direct Answers to Common Questions</h2>
            <p class="text-xs sm:text-sm text-slate-600">Everything you need to know about Official WhatsApp Cloud API and AHV Portal.</p>
        </div>

        <div class="space-y-3">
            <details class="group bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 [&_summary::-webkit-details-marker]:hidden open:border-emerald-300 shadow-2xs">
                <summary class="flex items-center justify-between cursor-pointer font-bold text-xs sm:text-sm text-slate-900">
                    <span>What is AHV WhatsApp Portal?</span>
                    <span class="transition group-open:rotate-180 text-emerald-600"><i class="ti ti-chevron-down"></i></span>
                </summary>
                <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    AHV WhatsApp Portal is an official Meta Cloud API-powered marketing and customer communication software. It lets businesses broadcast promotional campaigns to opt-in contacts, manage approved templates, and chat 2-way with leads via a multi-agent shared inbox, with zero risk of number bans.
                </p>
            </details>

            <details class="group bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 [&_summary::-webkit-details-marker]:hidden open:border-emerald-300 shadow-2xs">
                <summary class="flex items-center justify-between cursor-pointer font-bold text-xs sm:text-sm text-slate-900">
                    <span>Does AHV charge per-message commissions?</span>
                    <span class="transition group-open:rotate-180 text-emerald-600"><i class="ti ti-chevron-down"></i></span>
                </summary>
                <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    No. AHV WhatsApp Portal charges ₹0 per-message commission. You add your payment card directly to Meta Business Manager, and Meta bills you directly at standard official conversation rates. You only pay for the software dashboard subscription starting at ₹649/month.
                </p>
            </details>

            <details class="group bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 [&_summary::-webkit-details-marker]:hidden open:border-emerald-300 shadow-2xs">
                <summary class="flex items-center justify-between cursor-pointer font-bold text-xs sm:text-sm text-slate-900">
                    <span>Why is there a ₹4,500 One-Time Setup Fee?</span>
                    <span class="transition group-open:rotate-180 text-emerald-600"><i class="ti ti-chevron-down"></i></span>
                </summary>
                <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    The fixed ₹4,500 fee covers complete technical onboarding executed by AHV engineers: Meta WhatsApp Business Account (WABA) verification, official Graph API registration, permanent webhook endpoint binding, and template approval assistance.
                </p>
            </details>
        </div>

        <div class="text-center pt-2">
            <a href="{{ route('public.faq') }}" class="link link-primary font-bold text-xs">
                View All Frequently Asked Questions &rarr;
            </a>
        </div>
    </section>

    {{-- 6. Final Call to Action Banner --}}
    <section class="py-12 sm:py-20 px-3 sm:px-6 lg:px-8 max-w-6xl mx-auto">
        <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white p-7 sm:p-14 text-center space-y-4 sm:space-y-6 shadow-xl">
            <h2 class="text-2xl sm:text-4xl font-extrabold leading-snug">Ready to Supercharge Your Customer Conversations?</h2>
            <p class="text-xs sm:text-base text-emerald-100 max-w-2xl mx-auto leading-relaxed">
                Get your dedicated multi-tenant workspace provisioned today. Fully compliant, ultra-high inbox delivery, and direct Meta card billing.
            </p>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 pt-2 max-w-md mx-auto sm:max-w-none">
                <a href="{{ route('public.contact') }}" class="btn btn-white min-h-[44px] bg-white text-emerald-900 hover:bg-emerald-50 px-6 sm:px-8 font-extrabold rounded-xl border-0 shadow-md justify-center text-xs sm:text-sm">
                    <i class="ti ti-mail-fast text-base"></i> Request Workspace Access
                </a>
                <a href="https://wa.me/917878159887?text=Hello%20AHV%20Software,%20I%20would%20like%20to%20inquire%20about%20the%20WhatsApp%20Cloud%20Portal."
                   target="_blank" rel="noopener noreferrer"
                   class="btn btn-outline min-h-[44px] border-white text-white hover:bg-white/10 px-6 sm:px-7 font-bold rounded-xl justify-center text-xs sm:text-sm">
                    <i class="ti ti-brand-whatsapp text-lg"></i> Chat on WhatsApp (+91 78781 59887)
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
