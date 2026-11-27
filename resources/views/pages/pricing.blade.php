@php
    $systemSettings = app(\App\Services\System\SystemSettings::class);
    $publicPlans = $systemSettings->billingPlans();
    $lowestPrice = $systemSettings->lowestPlanPrice();
    $highestPrice = ! empty($publicPlans) ? max(array_column($publicPlans, 'final_price')) : 14999;
@endphp
<x-public-layout>
    <x-slot name="title">Pricing &amp; Plans (Starts ₹{{ number_format($lowestPrice) }}) — AHV WhatsApp Portal</x-slot>
    <x-slot name="description">Transparent WhatsApp marketing software pricing starting at ₹{{ number_format($lowestPrice) }}/month. Fixed ₹4,500 one-time Meta onboarding setup fee. Zero per-message markup on Meta conversation fees.</x-slot>

    {{-- Structured Data for Pricing & Offers --}}
    @php
        $pricingSchema = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => 'AHV WhatsApp Portal Software Access',
            'description' => 'Official Meta WhatsApp Cloud API software dashboard with broadcast campaigns, multi-agent live chat, and zero per-message markup.',
            'brand' => [
                '@type' => 'Brand',
                'name' => 'AHV Software'
            ],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'INR',
                'lowPrice' => (string) $lowestPrice,
                'highPrice' => (string) $highestPrice,
                'offerCount' => (string) count($publicPlans)
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $pricingSchema !!}</script>

    {{-- Page Header (Mobile-Friendly & Spacious) --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 bg-gradient-to-b from-emerald-50/60 to-slate-50 border-b border-slate-200">
        <div class="max-w-4xl mx-auto text-center space-y-3 px-2">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[0.7rem] sm:text-xs font-bold uppercase tracking-wider">
                Transparent SaaS Pricing
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Simple, Predictable Software Plans</h1>
            <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto leading-relaxed">
                Fixed one-time Meta onboarding setup fee + affordable software panel access starting at just ₹649/month. Zero per-message markup on Meta conversation fees.
            </p>
        </div>
    </section>

    {{-- Pricing Content --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-8 sm:space-y-12">
        {{-- Fixed Meta Setup Card (CSS Grid - Responsive on iOS, Android, Tablets, Laptops) --}}
        <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-500/10 via-white to-white border-2 border-emerald-500/50 p-5 sm:p-8 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                <div class="md:col-span-2 space-y-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-600 text-white text-[0.7rem] sm:text-xs font-bold">
                            One-Time Setup Fee (Fix)
                        </span>
                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-slate-100 border border-slate-300 text-slate-700 text-[0.7rem] sm:text-xs font-semibold">
                            Standard Mandatory Onboarding
                        </span>
                    </div>
                    <h2 class="text-lg sm:text-2xl font-bold text-slate-900">Meta Business Verification &amp; API Integration</h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Complete technical onboarding executed directly by AHV specialists: Meta WhatsApp Business Account (WABA) verification, Cloud API integration, permanent webhook setup, number registration, and broadcast template approvals.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 text-xs text-slate-700">
                        <div class="flex items-center gap-2"><i class="ti ti-check text-emerald-600 font-bold"></i> Meta WABA Verification Support</div>
                        <div class="flex items-center gap-2"><i class="ti ti-check text-emerald-600 font-bold"></i> Cloud API &amp; Webhook Provisioning</div>
                        <div class="flex items-center gap-2"><i class="ti ti-check text-emerald-600 font-bold"></i> Green Tick Readiness Check</div>
                        <div class="flex items-center gap-2"><i class="ti ti-check text-emerald-600 font-bold"></i> Template Approval Assistance</div>
                    </div>
                </div>

                <div class="md:col-span-1 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 text-center shadow-xs space-y-2">
                    <div class="text-[0.65rem] uppercase tracking-wider font-bold text-slate-400">Fixed Setup Charge</div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-emerald-600 font-mono">₹4,500</div>
                    <div class="text-[0.7rem] text-slate-500 mb-2">One-Time Setup Fee (Fix)</div>
                    <a href="https://wa.me/917878159887?text=Hello%20AHV%20Software,%20I%20am%20ready%20for%20the%20Meta%20WhatsApp%20Business%20Verification%20and%20API%20Integration%20Setup%20(₹4,500%20Fix)."
                       target="_blank" rel="noopener noreferrer"
                       class="btn btn-primary min-h-[42px] bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl w-full gap-1.5 text-xs font-bold justify-center border-0 shadow-sm">
                        <i class="ti ti-brand-whatsapp text-base"></i> Book Setup Onboarding
                    </a>
                </div>
            </div>
        </div>

        {{-- Software Dashboard Plans Grid (Responsive 1-col on mobile, 2-col on tablet, 4-col on desktop) --}}
        <div class="space-y-6">
            <div class="text-center space-y-1">
                <h3 class="text-xl sm:text-2xl font-bold text-slate-900">WhatsApp Dashboard Software Plans</h3>
                <p class="text-xs sm:text-sm text-slate-500">All plans include unlimited broadcast campaigns, template syncing, and live chat inbox.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach ($publicPlans as $planKey => $plan)
                    @php
                        $isLifetime = ($plan['days'] ?? null) === null;
                        $isFeatured = $planKey === 'monthly' || $isLifetime;
                        $hasDiscount = ($plan['discount_percent'] ?? 0) > 0;
                        $waText = rawurlencode("Hello AHV Software, I am interested in the {$plan['label']} (₹".number_format($plan['final_price']).") for WhatsApp Cloud Portal.");
                    @endphp
                    <div class="rounded-2xl bg-white {{ $isLifetime ? 'border-2 border-emerald-600' : ($isFeatured ? 'border-2 border-emerald-500' : 'border border-slate-200') }} shadow-sm flex flex-col justify-between p-5 space-y-4 hover:shadow-md transition-shadow">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-2 min-h-[1.25rem]">
                                @if ($isLifetime)
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-emerald-600 text-white text-[0.65rem] font-bold uppercase">
                                        ⭐ Lifetime Deal
                                    </span>
                                @elseif ($planKey === 'monthly')
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[0.65rem] font-bold uppercase">
                                        Starter Choice
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[0.65rem] font-semibold uppercase">
                                        {{ ($plan['days'] ?? 30).' Days' }}
                                    </span>
                                @endif

                                @if ($hasDiscount)
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[0.65rem] font-extrabold uppercase">
                                        {{ $plan['discount_percent'] }}% OFF
                                    </span>
                                @endif
                            </div>

                            <h4 class="font-bold text-base text-slate-900">{{ $plan['label'] }}</h4>
                            <div class="my-1">
                                @if ($hasDiscount)
                                    <span class="text-sm line-through text-slate-400 font-mono mr-1">₹{{ number_format($plan['price']) }}</span>
                                @endif
                                <span class="text-3xl font-extrabold font-mono {{ $isFeatured ? 'text-emerald-600' : 'text-slate-900' }}">₹{{ number_format($plan['final_price']) }}</span>
                                <span class="text-xs text-slate-500">/ {{ $isLifetime ? 'one-time' : ($plan['days'].' days') }}</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ $plan['description'] }}</p>
                            <ul class="text-xs space-y-2 text-slate-700 pt-2 border-t border-slate-100">
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-emerald-600 font-bold"></i> Broadcast Campaigns</li>
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-emerald-600 font-bold"></i> Live Chat 2-Way Inbox</li>
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-emerald-600 font-bold"></i> Template Management</li>
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-emerald-600 font-bold"></i> Direct Card Meta Billing</li>
                            </ul>
                        </div>
                        <div class="pt-2 border-t border-slate-100">
                            <a href="https://wa.me/917878159887?text={{ $waText }}"
                               target="_blank" rel="noopener noreferrer"
                               class="btn {{ $isFeatured ? 'btn-primary bg-emerald-600 hover:bg-emerald-700 text-white border-0' : 'btn-outline border-slate-200 hover:border-slate-300 text-slate-700' }} min-h-[40px] rounded-xl w-full gap-1.5 text-xs font-bold justify-center">
                                <i class="ti ti-brand-whatsapp {{ $isFeatured ? '' : 'text-emerald-600' }} text-base"></i> Get {{ $plan['label'] }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- AEO / Understanding Meta Billing Box --}}
        <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-7 space-y-4 shadow-2xs">
            <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                <i class="ti ti-info-circle text-emerald-600 text-lg"></i> Direct Meta Card Billing Explained (AEO Transparency)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-600 leading-relaxed">
                <div class="space-y-1.5 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="font-bold text-slate-900 text-xs">What You Pay to AHV Software:</div>
                    <p>Fixed One-Time Setup (₹4,500) + Monthly Dashboard Software Fee (Starts ₹649). This covers high-availability cloud servers, real-time webhooks, live chat, contact management, and technical support.</p>
                </div>
                <div class="space-y-1.5 bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="font-bold text-slate-900 text-xs">What You Pay to Meta Directly:</div>
                    <p>Conversation delivery charges billed directly to your credit or debit card by Meta at exact wholesale rates (approx ~₹0.74 per marketing conversation in India). AHV takes ₹0 markup on message costs.</p>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
