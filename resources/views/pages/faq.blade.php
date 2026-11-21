<x-public-layout>
    <x-slot name="title">Frequently Asked Questions (FAQ) — AHV WhatsApp Portal</x-slot>

    {{-- Page Header --}}
    <section class="py-12 px-4 sm:px-8 bg-gradient-to-b from-emerald-50/40 to-base-100 border-b border-base-200">
        <div class="max-w-4xl mx-auto text-center space-y-3">
            <span class="badge badge-success badge-sm text-white font-bold uppercase tracking-wider">Help &amp; Knowledge Base</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Frequently Asked Questions</h1>
            <p class="text-xs sm:text-sm opacity-70 max-w-xl mx-auto">
                Everything you need to know about official WhatsApp marketing, direct Meta billing, and platform onboarding.
            </p>
        </div>
    </section>

    {{-- FAQ List --}}
    <section class="py-12 px-4 sm:px-8 max-w-3xl mx-auto space-y-4">
        <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-2xl">
            <input type="checkbox" checked />
            <div class="collapse-title text-sm font-bold">
                How does the direct Meta credit/debit card billing work?
            </div>
            <div class="collapse-content text-xs opacity-75 leading-relaxed">
                When you connect your WhatsApp Business Account (WABA) in Meta Business Manager, you attach your own credit or debit card under Payment Methods. Every time a broadcast message or conversation is delivered, Meta deducts the official conversation charge directly from your card at wholesale cost. AHV Software charges ₹0 markup on Meta conversation fees.
            </div>
        </div>

        <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-2xl">
            <input type="checkbox" />
            <div class="collapse-title text-sm font-bold">
                Will my WhatsApp number be blocked or banned?
            </div>
            <div class="collapse-content text-xs opacity-75 leading-relaxed">
                No. Unlike unofficial scraping bots or WhatsApp Web browser extensions that violate WhatsApp Terms of Service, AHV Portal uses the official Meta WhatsApp Cloud API. Messages are sent via Meta's authorized infrastructure with verified delivery receipts.
            </div>
        </div>

        <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-2xl">
            <input type="checkbox" />
            <div class="collapse-title text-sm font-bold">
                What does the ₹4,500 One-Time Setup Fee cover?
            </div>
            <div class="collapse-content text-xs opacity-75 leading-relaxed">
                The ₹4,500 setup fee is a mandatory one-time technical onboarding fee. Our engineers assist you directly with Meta WhatsApp Business Account (WABA) verification, official Cloud API integration, permanent webhook setup, number registration, and drafting your first set of broadcast templates for Meta approval.
            </div>
        </div>

        <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-2xl">
            <input type="checkbox" />
            <div class="collapse-title text-sm font-bold">
                What is included in the ₹649 Monthly Software Plan?
            </div>
            <div class="collapse-content text-xs opacity-75 leading-relaxed">
                The ₹649 plan gives you full 30-day access to the WhatsApp marketing software dashboard: unlimited broadcast campaigns, real-time 2-way live chat inbox, template management, contact segmentation, and detailed delivery analytics.
            </div>
        </div>

        <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-2xl">
            <input type="checkbox" />
            <div class="collapse-title text-sm font-bold">
                Can multiple team members chat with customers at the same time?
            </div>
            <div class="collapse-content text-xs opacity-75 leading-relaxed">
                Yes. Our live chat inbox supports multi-agent collaboration. Team members can view conversations, claim chats, send approved templates or free-form text/media within the 24-hour customer window, and track customer resolution.
            </div>
        </div>

        <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-2xl">
            <input type="checkbox" />
            <div class="collapse-title text-sm font-bold">
                When will SMS and Voice Calling be available?
            </div>
            <div class="collapse-content text-xs opacity-75 leading-relaxed">
                Both the DLT Enterprise SMS Gateway and AI Voice Calling Dialler are currently in private preview. Existing WhatsApp clients get priority beta access as telecom carrier routes are provisioned.
            </div>
        </div>

        <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-2xl">
            <input type="checkbox" />
            <div class="collapse-title text-sm font-bold">
                How do I pay for software subscriptions?
            </div>
            <div class="collapse-content text-xs opacity-75 leading-relaxed">
                Inside your portal dashboard under Subscription &amp; Billing, you will find our official UPI QR Code, UPI ID (ahvsoftware@upi), and direct Bank Account details. Simply transfer the amount and submit your 12-digit UTR/Reference number. The AHV team verifies it and activates your validity immediately.
            </div>
        </div>
    </section>

    {{-- Bottom CTA --}}
    <section class="py-10 px-4 sm:px-8 text-center space-y-3">
        <h4 class="font-bold text-sm">Have more questions?</h4>
        <a href="{{ route('public.contact') }}" class="btn btn-outline btn-sm px-6 text-xs gap-1">
            <i class="ti ti-mail"></i> Ask Our Support Team
        </a>
    </section>
</x-public-layout>
