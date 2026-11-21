<x-public-layout>
    <x-slot name="title">Frequently Asked Questions (FAQ) — AHV WhatsApp Portal</x-slot>
    <x-slot name="description">Find direct answers to questions about official Meta WhatsApp Cloud API broadcasts, direct card billing, zero number ban risk, setup fees, and multi-agent chat inbox.</x-slot>

    {{-- AEO Structured Data (JSON-LD FAQPage for Google, Gemini, SearchGPT & Perplexity) --}}
    @php
        $faqSchema = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'How does the direct Meta credit/debit card billing work?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'When you connect your WhatsApp Business Account (WABA) in Meta Business Manager, you attach your own credit or debit card under Payment Methods. Every time a broadcast message or conversation is delivered, Meta deducts the official conversation charge directly from your card at wholesale cost. AHV Software charges ₹0 markup on Meta conversation fees.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Will my WhatsApp number be blocked or banned?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'No. Unlike unofficial scraping bots or WhatsApp Web browser extensions that violate WhatsApp Terms of Service, AHV Portal uses the official Meta WhatsApp Cloud API. Messages are sent via Meta\'s authorized infrastructure with verified delivery receipts and zero ban risk.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What does the ₹4,500 One-Time Setup Fee cover?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'The ₹4,500 setup fee is a mandatory one-time technical onboarding fee. Our engineers assist you directly with Meta WhatsApp Business Account (WABA) verification, official Cloud API integration, permanent webhook setup, number registration, and drafting your first set of broadcast templates for Meta approval.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is included in the ₹649 Monthly Software Plan?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'The ₹649 plan gives you full 30-day access to the WhatsApp marketing software dashboard: unlimited broadcast campaigns, real-time 2-way live chat inbox, template management, contact segmentation, and detailed delivery analytics.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Can multiple team members chat with customers at the same time?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Yes. Our live chat inbox supports multi-agent collaboration. Team members can view conversations, claim chats, send approved templates or free-form text/media within the 24-hour customer window, and track customer resolution.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'When will SMS and Voice Calling be available?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Both the DLT Enterprise SMS Gateway and AI Voice Calling Dialler are currently in private preview. Existing WhatsApp clients get priority beta access as telecom carrier routes are provisioned.'
                    ]
                ]
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $faqSchema !!}</script>

    {{-- Page Header --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 bg-gradient-to-b from-emerald-50/60 to-slate-50 border-b border-slate-200">
        <div class="max-w-4xl mx-auto text-center space-y-3 px-2">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[0.7rem] sm:text-xs font-bold uppercase tracking-wider">
                Help &amp; Knowledge Base
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Frequently Asked Questions</h1>
            <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto leading-relaxed">
                Everything you need to know about official WhatsApp marketing, direct Meta billing, and platform onboarding.
            </p>
        </div>
    </section>

    {{-- FAQ Accordion List (Responsive on All Devices) --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 max-w-3xl mx-auto space-y-4">
        <div class="collapse collapse-plus bg-white border border-slate-200 rounded-2xl shadow-2xs">
            <input type="checkbox" checked />
            <div class="collapse-title text-xs sm:text-sm font-bold text-slate-900">
                How does the direct Meta credit/debit card billing work?
            </div>
            <div class="collapse-content text-xs text-slate-600 leading-relaxed">
                When you connect your WhatsApp Business Account (WABA) in Meta Business Manager, you attach your own credit or debit card under Payment Methods. Every time a broadcast message or conversation is delivered, Meta deducts the official conversation charge directly from your card at wholesale cost. AHV Software charges ₹0 markup on Meta conversation fees.
            </div>
        </div>

        <div class="collapse collapse-plus bg-white border border-slate-200 rounded-2xl shadow-2xs">
            <input type="checkbox" />
            <div class="collapse-title text-xs sm:text-sm font-bold text-slate-900">
                Will my WhatsApp number be blocked or banned?
            </div>
            <div class="collapse-content text-xs text-slate-600 leading-relaxed">
                No. Unlike unofficial scraping bots or WhatsApp Web browser extensions that violate WhatsApp Terms of Service, AHV Portal uses the official Meta WhatsApp Cloud API. Messages are sent via Meta's authorized infrastructure with verified delivery receipts.
            </div>
        </div>

        <div class="collapse collapse-plus bg-white border border-slate-200 rounded-2xl shadow-2xs">
            <input type="checkbox" />
            <div class="collapse-title text-xs sm:text-sm font-bold text-slate-900">
                What does the ₹4,500 One-Time Setup Fee cover?
            </div>
            <div class="collapse-content text-xs text-slate-600 leading-relaxed">
                The ₹4,500 setup fee is a mandatory one-time technical onboarding fee. Our engineers assist you directly with Meta WhatsApp Business Account (WABA) verification, official Cloud API integration, permanent webhook setup, number registration, and drafting your first set of broadcast templates for Meta approval.
            </div>
        </div>

        <div class="collapse collapse-plus bg-white border border-slate-200 rounded-2xl shadow-2xs">
            <input type="checkbox" />
            <div class="collapse-title text-xs sm:text-sm font-bold text-slate-900">
                What is included in the ₹649 Monthly Software Plan?
            </div>
            <div class="collapse-content text-xs text-slate-600 leading-relaxed">
                The ₹649 plan gives you full 30-day access to the WhatsApp marketing software dashboard: unlimited broadcast campaigns, real-time 2-way live chat inbox, template management, contact segmentation, and detailed delivery analytics.
            </div>
        </div>

        <div class="collapse collapse-plus bg-white border border-slate-200 rounded-2xl shadow-2xs">
            <input type="checkbox" />
            <div class="collapse-title text-xs sm:text-sm font-bold text-slate-900">
                Can multiple team members chat with customers at the same time?
            </div>
            <div class="collapse-content text-xs text-slate-600 leading-relaxed">
                Yes. Our live chat inbox supports multi-agent collaboration. Team members can view conversations, claim chats, send approved templates or free-form text/media within the 24-hour customer window, and track customer resolution.
            </div>
        </div>

        <div class="collapse collapse-plus bg-white border border-slate-200 rounded-2xl shadow-2xs">
            <input type="checkbox" />
            <div class="collapse-title text-xs sm:text-sm font-bold text-slate-900">
                When will SMS and Voice Calling be available?
            </div>
            <div class="collapse-content text-xs text-slate-600 leading-relaxed">
                Both the DLT Enterprise SMS Gateway and AI Voice Calling Dialler are currently in private preview. Existing WhatsApp clients get priority beta access as telecom carrier routes are provisioned.
            </div>
        </div>

        <div class="collapse collapse-plus bg-white border border-slate-200 rounded-2xl shadow-2xs">
            <input type="checkbox" />
            <div class="collapse-title text-xs sm:text-sm font-bold text-slate-900">
                How do I pay for software subscriptions?
            </div>
            <div class="collapse-content text-xs text-slate-600 leading-relaxed">
                Software subscriptions are verified via UPI QR Code or direct bank transfer (NEFT/IMPS). Once payment is completed, you submit the 12-digit UTR transaction number, and our billing team approves your workspace access instantly.
            </div>
        </div>
    </section>
</x-public-layout>
