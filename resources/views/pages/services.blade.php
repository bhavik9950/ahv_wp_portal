<x-public-layout>
    <x-slot name="title">Services &amp; Architecture — AHV Multi-Channel Suite</x-slot>

    {{-- Page Header --}}
    <section class="py-12 px-4 sm:px-8 bg-gradient-to-b from-emerald-50/40 to-base-100 border-b border-base-200">
        <div class="max-w-4xl mx-auto text-center space-y-3">
            <span class="badge badge-success badge-sm text-white font-bold uppercase tracking-wider">Multi-Channel CPaaS</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Enterprise Communication Services</h1>
            <p class="text-xs sm:text-sm opacity-70 max-w-xl mx-auto">
                Explore our active Meta WhatsApp Cloud API software and upcoming enterprise SMS &amp; AI Telephony dialler modules.
            </p>
        </div>
    </section>

    {{-- Detailed Services Breakdown --}}
    <section class="py-12 px-4 sm:px-8 max-w-5xl mx-auto space-y-12">
        {{-- WhatsApp Service Detailed --}}
        <div class="card bg-base-100 border-2 border-emerald-500 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-base-200 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-2xl">
                        <i class="ti ti-brand-whatsapp"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold">Official Meta WhatsApp Cloud API</h2>
                            <span class="badge badge-success badge-sm text-white font-bold">Active &amp; Live</span>
                        </div>
                        <p class="text-xs opacity-70">High-volume broadcast engine with zero phone number ban risk.</p>
                    </div>
                </div>
                <a href="{{ route('public.pricing') }}" class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-sm text-xs">
                    View WhatsApp Plans (From ₹649)
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-emerald-700">
                        <i class="ti ti-rocket"></i> Bulk Broadcast Campaigns
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Schedule campaigns to thousands of verified contacts. Personalize messages with dynamic customer names, order IDs, and custom variables.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-emerald-700">
                        <i class="ti ti-messages"></i> Live 2-Way Multi-Agent Chat
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Customer replies flow instantly into a collaborative team inbox. Send documents, images, and close orders with real-time delivered/read ticks.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-emerald-700">
                        <i class="ti ti-credit-card"></i> Direct Meta Card Billing
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Attach your own business credit/debit card in Meta Business Manager. Meta bills you directly for conversations at exact wholesale rates.
                    </p>
                </div>
            </div>
        </div>

        {{-- SMS Gateway Service Detailed --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-base-200 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-2xl">
                        <i class="ti ti-message-dots"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold">Enterprise SMS Cloud Gateway</h2>
                            <span class="badge badge-warning badge-sm font-semibold">Coming Soon</span>
                        </div>
                        <p class="text-xs opacity-70">100% TRAI DLT Compliant telecom routing across India.</p>
                    </div>
                </div>
                <span class="badge badge-outline badge-sm">In Private Preview</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-amber-700">
                        <i class="ti ti-shield-check"></i> DLT Header &amp; Template Sync
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Register your 6-character Alpha sender headers (e.g. AHVSFT, FITGNR) and content template IDs approved by Airtel, Jio, or VI DLT portals.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-amber-700">
                        <i class="ti ti-bolt"></i> Ultra-Fast OTP Delivery
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Sub-2-second delivery latency for critical authentication OTPs, password resets, and transaction confirmations.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-amber-700">
                        <i class="ti ti-chart-bar"></i> Delivery Reports (DLR)
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Real-time telecom carrier delivery status receipts, bounce tracking, and DND auto-filtering.
                    </p>
                </div>
            </div>
        </div>

        {{-- AI Voice Calling Agent Detailed --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-base-200 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-2xl">
                        <i class="ti ti-phone-outgoing"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold">AI Voice Calling Agent &amp; Outbound Dialler</h2>
                            <span class="badge badge-warning badge-sm font-semibold">Coming Soon</span>
                        </div>
                        <p class="text-xs opacity-70">Automated telephony broadcasting and conversational voice AI.</p>
                    </div>
                </div>
                <span class="badge badge-outline badge-sm">In Private Preview</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-sky-700">
                        <i class="ti ti-broadcast"></i> Pre-Recorded Voice Broadcasts
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Broadcast promotional audio clips, event announcements, and urgent payment reminders to customer lists simultaneously.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-sky-700">
                        <i class="ti ti-keyboard"></i> DTMF Keypress Capture
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Prompt users to press keys (e.g., "Press 1 to confirm, 2 to reschedule") with real-time webhook callback processing.
                    </p>
                </div>
                <div class="space-y-2">
                    <h4 class="font-bold text-sm flex items-center gap-1.5 text-sky-700">
                        <i class="ti ti-clock-check"></i> Pickup &amp; Duration Analytics
                    </h4>
                    <p class="text-xs opacity-75 leading-relaxed">
                        Granular analytics tracking total calls placed, answered rates, listening duration, and cost per connected second.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Bottom CTA --}}
    <section class="py-12 px-4 sm:px-8 bg-base-200/50 border-t border-base-300 text-center space-y-4">
        <h3 class="text-xl font-bold">Need a Custom CPaaS Integration?</h3>
        <p class="text-xs opacity-75 max-w-md mx-auto">Talk to our integration architects to evaluate WhatsApp Cloud API, SMS routes, and conversational AI for your stack.</p>
        <a href="{{ route('public.contact') }}" class="btn btn-primary btn-sm bg-emerald-600 hover:bg-emerald-700 text-white border-0 px-6">
            Contact Technical Team &rarr;
        </a>
    </section>
</x-public-layout>
