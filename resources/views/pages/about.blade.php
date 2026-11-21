<x-public-layout>
    <x-slot name="title">About Us — AHV Software Technologies</x-slot>

    {{-- Page Header --}}
    <section class="py-12 px-4 sm:px-8 bg-gradient-to-b from-emerald-50/40 to-base-100 border-b border-base-200">
        <div class="max-w-4xl mx-auto text-center space-y-3">
            <span class="badge badge-success badge-sm text-white font-bold uppercase tracking-wider">About AHV Software</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Enterprise Messaging Without Middlemen</h1>
            <p class="text-xs sm:text-sm opacity-70 max-w-xl mx-auto">
                Built by AHV Software Technologies to empower businesses with 100% compliant, official Meta Cloud API infrastructure.
            </p>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-12 px-4 sm:px-8 max-w-4xl mx-auto space-y-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
            <div class="space-y-4">
                <h2 class="text-2xl font-bold tracking-tight">Our Mission: Zero Number Bans, Zero Hidden Markup</h2>
                <p class="text-xs sm:text-sm opacity-75 leading-relaxed">
                    Most WhatsApp marketing tools in the market rely on unofficial WhatsApp Web scraping bots. They cause permanent phone number bans, message delivery failures, and high operational risk for real brands.
                </p>
                <p class="text-xs sm:text-sm opacity-75 leading-relaxed">
                    <strong>AHV WhatsApp Portal</strong> was developed to give businesses access to the official <strong>Meta Cloud API</strong> at transparent pricing. You connect your own Meta Business Manager, pay Meta directly on your business card, and manage everything from our high-speed SaaS dashboard.
                </p>
            </div>

            <div class="card bg-base-100 border border-base-300 p-6 space-y-4 shadow-sm">
                <h3 class="font-bold text-sm flex items-center gap-2 text-emerald-700">
                    <i class="ti ti-shield-check text-xl"></i> Core Principles
                </h3>
                <div class="space-y-3 text-xs opacity-80">
                    <div class="flex items-start gap-2.5">
                        <i class="ti ti-check text-success mt-0.5"></i>
                        <div><strong>100% Official Compliance:</strong> Strictly uses official Meta endpoints. No unofficial automation or extension hacks.</div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <i class="ti ti-check text-success mt-0.5"></i>
                        <div><strong>Strict Multi-Tenant Isolation:</strong> Every client's contacts, campaigns, and chat conversations are segregated at the database level.</div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <i class="ti ti-check text-success mt-0.5"></i>
                        <div><strong>Transparent Wholesale Rates:</strong> You pay exact Meta conversation costs without 2x-3x reseller markup.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Company Details --}}
        <div class="p-6 rounded-2xl bg-base-200/50 border border-base-300 text-center space-y-3 max-w-2xl mx-auto">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-lg mx-auto">
                <i class="ti ti-building"></i>
            </div>
            <h3 class="font-bold text-base">AHV Software Technologies</h3>
            <p class="text-xs opacity-70 leading-relaxed">
                A premier software development company specializing in cloud CPaaS, enterprise telephony, high-volume broadcast platforms, and conversational AI agents.
            </p>
            <div class="pt-2">
                <a href="{{ route('public.contact') }}" class="btn btn-emerald bg-emerald-600 hover:bg-emerald-700 text-white btn-sm px-6 text-xs">
                    Get in Touch With Us &rarr;
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
