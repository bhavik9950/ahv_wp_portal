@php
    $cNum1 = rand(2, 8);
    $cNum2 = rand(1, 9);
    session(['inquiry_captcha_num1' => $cNum1, 'inquiry_captcha_num2' => $cNum2]);
@endphp

<x-public-layout>
    <x-slot name="title">Contact Us &amp; Request Access — AHV WhatsApp Portal</x-slot>
    <x-slot name="description">Get in touch with AHV Software for WhatsApp Cloud API onboarding, Meta verification setup, and platform inquiries. Call/WhatsApp: +91 78781 59887.</x-slot>

    {{-- Page Header --}}
    <section class="py-10 sm:py-14 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-emerald-50/50 to-white border-b border-slate-200/80">
        <div class="max-w-4xl mx-auto text-center space-y-2">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider">
                Direct Support &amp; Onboarding
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Let's Get Your Business Connected</h1>
            <p class="text-xs sm:text-sm text-slate-600 max-w-lg mx-auto">
                Request your workspace access below or reach our technical team directly on phone or WhatsApp.
            </p>
        </div>
    </section>

    {{-- Main Contact Section (Modern, Minimalist & Spacious) --}}
    <section class="py-12 sm:py-16 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 sm:gap-12 items-start">
            
            {{-- Left Column: Direct Contact Info (Punchy, Clean, No Fluff) --}}
            <div class="lg:col-span-5 space-y-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Contact Information</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                        We assist with complete Meta Business verification, Cloud API integration, and template setup.
                    </p>
                </div>

                {{-- Direct Action Cards --}}
                <div class="space-y-3">
                    {{-- Phone & WhatsApp --}}
                    <a href="https://wa.me/917878159887?text=Hello%20AHV%20Software,%20I%20am%20interested%20in%20the%20WhatsApp%20Cloud%20Portal."
                       target="_blank" rel="noopener noreferrer"
                       class="group p-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-300 hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl font-bold group-hover:scale-110 transition-transform shrink-0">
                            <i class="ti ti-brand-whatsapp"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Call / WhatsApp</div>
                            <div class="text-sm font-extrabold text-slate-900 group-hover:text-emerald-700 transition-colors">+91 78781 59887</div>
                            <div class="text-[0.68rem] text-emerald-600 font-semibold">Instant reply on WhatsApp</div>
                        </div>
                    </a>

                    {{-- Primary Email --}}
                    <a href="mailto:contact@ahvsoftware.com"
                       class="group p-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-300 hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform shrink-0">
                            <i class="ti ti-mail"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Official Inquiries</div>
                            <div class="text-sm font-extrabold text-slate-900 truncate">contact@ahvsoftware.com</div>
                            <div class="text-[0.68rem] text-slate-500">Business &amp; enterprise support</div>
                        </div>
                    </a>

                    {{-- Direct Support Email --}}
                    <a href="mailto:infoahvsoft@gmail.com"
                       class="group p-4 rounded-2xl bg-white border border-slate-200 hover:border-emerald-300 hover:shadow-md transition-all flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform shrink-0">
                            <i class="ti ti-at"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Direct Desk</div>
                            <div class="text-sm font-extrabold text-slate-900 truncate">infoahvsoft@gmail.com</div>
                            <div class="text-[0.68rem] text-slate-500">Quick team response</div>
                        </div>
                    </a>
                </div>

                {{-- Transparent Summary Note --}}
                <div class="p-4 rounded-2xl bg-slate-100/80 border border-slate-200 text-xs text-slate-600 space-y-1.5">
                    <div class="font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="ti ti-clock-check text-emerald-600 text-sm"></i> Onboarding SLA: 2–4 Hours
                    </div>
                    <p class="leading-relaxed">
                        Accounts are activated upon requirement review. Setup fee: <strong>₹4,500 (one-time fix)</strong> for complete Meta WABA verification &amp; webhook provisioning.
                    </p>
                </div>
            </div>

            {{-- Right Column: Sleek Modern Form --}}
            <div class="lg:col-span-7">
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-5">
                    <div>
                        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900">Request Workspace Access</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Fill out your details to receive access credentials.</p>
                    </div>

                    @if (session('success'))
                        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-semibold flex items-center gap-2.5">
                            <i class="ti ti-circle-check text-xl text-emerald-600 shrink-0"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800">
                            <ul class="list-disc list-inside space-y-0.5 font-medium">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.inquiry.store') }}" class="space-y-4">
                        @csrf
                        {{-- Honeypot security field: hidden from humans --}}
                        <input type="text" name="b_hp_security_check" style="display:none !important;" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-700 block">Your Name <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Rahul Sharma"
                                       class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:outline-none focus:ring-3 focus:ring-emerald-500/10 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-700 block">Phone / WhatsApp Number <span class="text-rose-500">*</span></label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="e.g. +91 98765 43210"
                                       class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:outline-none focus:ring-3 focus:ring-emerald-500/10 transition-all">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-700 block">Business / Brand Name</label>
                                <input type="text" name="business_name" value="{{ old('business_name') }}" placeholder="e.g. Fit Ganganagar"
                                       class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:outline-none focus:ring-3 focus:ring-emerald-500/10 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-700 block">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="e.g. contact@business.com"
                                       class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:outline-none focus:ring-3 focus:ring-emerald-500/10 transition-all">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700 block">Service Needed <span class="text-rose-500">*</span></label>
                            <select name="service_interested" class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:outline-none focus:ring-3 focus:ring-emerald-500/10 transition-all" required>
                                <option value="whatsapp" {{ old('service_interested') === 'whatsapp' ? 'selected' : '' }}>
                                    WhatsApp Official Cloud API &amp; Broadcasts (Live)
                                </option>
                                <option value="sms" {{ old('service_interested') === 'sms' ? 'selected' : '' }}>
                                    Enterprise SMS Gateway &amp; DLT Route (Coming Soon)
                                </option>
                                <option value="voice" {{ old('service_interested') === 'voice' ? 'selected' : '' }}>
                                    AI Voice Calling Agent &amp; Dialler (Coming Soon)
                                </option>
                                <option value="all" {{ old('service_interested') === 'all' ? 'selected' : '' }}>
                                    Full Multi-Channel Suite (WhatsApp + SMS + Voice)
                                </option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700 block">Message / Requirements</label>
                            <textarea name="message" rows="2" placeholder="Tell us about your business or expected monthly broadcast volume..."
                                      class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:outline-none focus:ring-3 focus:ring-emerald-500/10 transition-all">{{ old('message') }}</textarea>
                        </div>

                        {{-- Minimalist Captcha --}}
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-bold text-slate-700">Security Check:</span>
                                <span class="font-mono font-extrabold text-xs bg-white px-2 py-1 rounded-md border border-slate-200 text-slate-800">{{ $cNum1 }} + {{ $cNum2 }} = ?</span>
                            </div>
                            <div class="w-24">
                                <input type="number" name="captcha_answer" required placeholder="Result"
                                       class="w-full px-3 py-1.5 text-xs text-center font-mono font-bold bg-white border border-slate-300 rounded-lg focus:border-emerald-500 focus:outline-none">
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full py-3.5 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs sm:text-sm shadow-md shadow-emerald-600/20 hover:scale-[1.01] transition-all flex items-center justify-center gap-2">
                            <i class="ti ti-send text-base"></i> Submit Onboarding Request &rarr;
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
