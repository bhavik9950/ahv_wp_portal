<x-public-layout>
    <x-slot name="title">About Us — AHV Software Technologies</x-slot>
    <x-slot name="description">Learn about AHV Software Technologies and our mission to provide 100% compliant Official Meta WhatsApp Cloud API software with zero number bans and direct Meta card billing.</x-slot>

    {{-- Page Header --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 bg-gradient-to-b from-emerald-50/60 to-slate-50 border-b border-slate-200">
        <div class="max-w-4xl mx-auto text-center space-y-3 px-2">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[0.7rem] sm:text-xs font-bold uppercase tracking-wider">
                About AHV Software
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Enterprise Messaging Without Middlemen</h1>
            <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto leading-relaxed">
                Built by AHV Software Technologies to empower businesses with 100% compliant, official Meta Cloud API infrastructure.
            </p>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8 sm:space-y-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 items-center">
            <div class="space-y-3 sm:space-y-4">
                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">Our Mission: Zero Number Bans, Zero Hidden Markup</h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Most WhatsApp marketing tools in the market rely on unofficial WhatsApp Web scraping bots. They cause permanent phone number bans, message delivery failures, and high operational risk for real brands.
                </p>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    <strong>AHV WhatsApp Portal</strong> was developed to give businesses access to the official <strong>Meta Cloud API</strong> at transparent pricing. You connect your own Meta Business Manager, pay Meta directly on your business card, and manage everything from our high-speed SaaS dashboard.
                </p>
            </div>

            <div class="rounded-2xl sm:rounded-3xl bg-white border border-slate-200 p-5 sm:p-6 space-y-4 shadow-2xs">
                <h3 class="font-bold text-sm flex items-center gap-2 text-emerald-700">
                    <i class="ti ti-shield-check text-xl"></i> Core Architecture Principles
                </h3>
                <div class="space-y-3 text-xs text-slate-600">
                    <div class="flex items-start gap-2.5">
                        <i class="ti ti-check text-emerald-600 font-bold mt-0.5 shrink-0"></i>
                        <div><strong>100% Official Compliance:</strong> Strictly uses official Meta Graph API endpoints. No unofficial automation or extension hacks.</div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <i class="ti ti-check text-emerald-600 font-bold mt-0.5 shrink-0"></i>
                        <div><strong>Strict Multi-Tenant Isolation:</strong> Every client's contacts, campaigns, and chat conversations are segregated at the database level.</div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <i class="ti ti-check text-emerald-600 font-bold mt-0.5 shrink-0"></i>
                        <div><strong>Transparent Wholesale Rates:</strong> You pay exact Meta conversation costs without 2x-3x reseller markup.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Company Details --}}
        <div class="p-6 sm:p-8 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 text-center space-y-3 max-w-2xl mx-auto shadow-2xs">
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xl mx-auto shadow-xs">
                <i class="ti ti-building"></i>
            </div>
            <h3 class="font-bold text-lg text-slate-900">AHV Software Technologies</h3>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                A premier software development company specializing in cloud CPaaS, enterprise telephony, high-volume broadcast platforms, and conversational AI agents.
            </p>
            <div class="pt-3 flex flex-wrap items-center justify-center gap-3 text-xs">
                <a href="https://wa.me/917878159887" target="_blank" rel="noopener noreferrer"
                   class="btn btn-outline btn-sm rounded-xl border-slate-300 text-slate-700 hover:border-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 gap-1.5">
                    <i class="ti ti-brand-whatsapp text-emerald-600 text-base"></i> +91 78781 59887
                </a>
                <a href="mailto:contact@ahvsoftware.com"
                   class="btn btn-outline btn-sm rounded-xl border-slate-300 text-slate-700 hover:border-slate-400 gap-1.5">
                    <i class="ti ti-mail text-base"></i> contact@ahvsoftware.com
                </a>
                <a href="{{ route('public.contact') }}" class="btn btn-primary btn-sm min-h-[38px] bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-5 text-xs font-bold border-0 shadow-sm">
                    Open Contact Form &rarr;
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
