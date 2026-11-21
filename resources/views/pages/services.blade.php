<x-public-layout>
    <x-slot name="title">Services &amp; Architecture — AHV Multi-Channel Suite</x-slot>
    <x-slot name="description">Explore official Meta WhatsApp Cloud API broadcasts, DLT enterprise SMS gateway, and AI conversational voice calling agents. Built for enterprise delivery and multi-tenant security.</x-slot>

    {{-- Page Header --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 bg-gradient-to-b from-emerald-50/60 to-slate-50 border-b border-slate-200">
        <div class="max-w-4xl mx-auto text-center space-y-3 px-2">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[0.7rem] sm:text-xs font-bold uppercase tracking-wider">
                Multi-Channel CPaaS
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Enterprise Communication Services</h1>
            <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto leading-relaxed">
                Explore our active Meta WhatsApp Cloud API software and upcoming enterprise SMS &amp; AI Telephony dialler modules.
            </p>
        </div>
    </section>

    {{-- Detailed Services Breakdown --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-8 sm:space-y-12">
        {{-- WhatsApp Service Detailed --}}
        <div class="rounded-2xl sm:rounded-3xl bg-white border-2 border-emerald-500 shadow-sm p-5 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-2xl shrink-0">
                        <i class="ti ti-brand-whatsapp"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900">Official Meta WhatsApp Cloud API</h2>
                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[0.7rem] font-bold">Active &amp; Live</span>
                        </div>
                        <p class="text-xs text-slate-500">High-volume broadcast engine with zero phone number ban risk.</p>
                    </div>
                </div>
                <a href="{{ route('public.pricing') }}" class="btn btn-primary min-h-[38px] bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold px-4 border-0 shadow-sm">
                    View WhatsApp Plans (From ₹649)
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-emerald-700">
                        <i class="ti ti-rocket"></i> Bulk Broadcast Campaigns
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Schedule campaigns to thousands of verified contacts. Personalize messages with dynamic customer names, order IDs, and custom variables.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-emerald-700">
                        <i class="ti ti-messages"></i> Live 2-Way Multi-Agent Chat
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Customer replies flow instantly into a collaborative team inbox. Send documents, images, and close orders with real-time delivered/read ticks.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-emerald-700">
                        <i class="ti ti-credit-card"></i> Direct Meta Card Billing
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Attach your own business credit/debit card in Meta Business Manager. Meta bills you directly for conversations at exact wholesale rates.
                    </p>
                </div>
            </div>
        </div>

        {{-- SMS Gateway Service Detailed --}}
        <div class="rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-sm p-5 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-2xl shrink-0">
                        <i class="ti ti-message-dots"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900">Enterprise SMS Cloud Gateway</h2>
                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[0.7rem] font-semibold">Coming Soon</span>
                        </div>
                        <p class="text-xs text-slate-500">100% TRAI DLT Compliant telecom routing across India.</p>
                    </div>
                </div>
                <span class="inline-block px-3 py-1 rounded-full bg-slate-100 border border-slate-300 text-slate-600 text-xs font-semibold">In Private Preview</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-amber-700">
                        <i class="ti ti-shield-check"></i> DLT Header &amp; Template Sync
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Register your 6-character Alpha sender headers (e.g. AHVSFT, FITGNR) and content template IDs approved by Airtel, Jio, or VI DLT portals.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-amber-700">
                        <i class="ti ti-bolt"></i> Ultra-Fast OTP Delivery
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Sub-2-second delivery latency for critical authentication OTPs, password resets, and transaction confirmations.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-amber-700">
                        <i class="ti ti-git-fork"></i> Smart Telecom Failover
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Intelligent multi-carrier routing that reroutes traffic when an operator experiences latency spikes.
                    </p>
                </div>
            </div>
        </div>

        {{-- AI Voice Calling Detailed --}}
        <div class="rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-sm p-5 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-2xl shrink-0">
                        <i class="ti ti-phone-outgoing"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900">AI Voice Calling Agent &amp; Dialler</h2>
                            <span class="inline-block px-2.5 py-0.5 rounded-full bg-sky-100 text-sky-800 text-[0.7rem] font-semibold">Coming Soon</span>
                        </div>
                        <p class="text-xs text-slate-500">Automated outbound voice calls and interactive AI conversational agent.</p>
                    </div>
                </div>
                <span class="inline-block px-3 py-1 rounded-full bg-slate-100 border border-slate-300 text-slate-600 text-xs font-semibold">In Development</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-sky-700">
                        <i class="ti ti-robot"></i> Conversational AI Dialler
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Natural human-like AI agents conduct outbound phone calls for payment reminders, lead qualification, and customer survey feedback.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-sky-700">
                        <i class="ti ti-volume"></i> Audio Broadcast Dialler
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Blast pre-recorded audio messages to tens of thousands of contacts simultaneously with real-time call answer/duration metrics.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-sky-700">
                        <i class="ti ti-file-text"></i> Real-time Speech-to-Text
                    </h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Automatic speech recognition logs call transcriptions and customer intent directly into your CRM.
                    </p>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
