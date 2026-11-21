@php
    $cNum1 = rand(2, 8);
    $cNum2 = rand(1, 9);
    session(['inquiry_captcha_num1' => $cNum1, 'inquiry_captcha_num2' => $cNum2]);
@endphp

<x-public-layout>
    <x-slot name="title">Contact Us &amp; Request Access — AHV WhatsApp Portal</x-slot>
    <x-slot name="description">Request access to AHV WhatsApp Portal or get in touch with our technical onboarding specialists for Meta WABA verification and Cloud API setup.</x-slot>

    {{-- Page Header --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 bg-gradient-to-b from-emerald-50/60 to-slate-50 border-b border-slate-200">
        <div class="max-w-4xl mx-auto text-center space-y-3 px-2">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[0.7rem] sm:text-xs font-bold uppercase tracking-wider">
                Enterprise Onboarding
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Contact Us &amp; Request Workspace</h1>
            <p class="text-xs sm:text-sm text-slate-600 max-w-xl mx-auto leading-relaxed">
                Fill out the onboarding inquiry form below. Our technical specialists review requirements and provision accounts within 2-4 hours.
            </p>
        </div>
    </section>

    {{-- Contact Section --}}
    <section class="py-10 sm:py-16 px-3 sm:px-6 lg:px-8 max-w-5xl mx-auto">
        <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-500/10 via-white to-white border-2 border-emerald-500/30 p-5 sm:p-8 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-start">
                <div class="lg:col-span-5 space-y-4">
                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-600 text-white text-[0.7rem] font-bold">Closed Enterprise Access</span>
                    <h2 class="text-xl sm:text-3xl font-bold tracking-tight text-slate-900">Dedicated Onboarding Support</h2>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        To maintain 100% Meta Cloud API compliance, pristine number quality rating, and multi-tenant security, <strong>AHV WhatsApp Portal</strong> is an invite-only platform.
                    </p>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Each organization receives a dedicated workspace with verified WABA credentials, phone number routing, and custom sender isolation.
                    </p>

                    <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-2.5 shadow-2xs">
                        <div class="font-bold text-xs flex items-center gap-1.5 text-emerald-700">
                            <i class="ti ti-brand-whatsapp text-base"></i> Direct WhatsApp Inquiry
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Need immediate setup or have existing Meta Business Manager questions? Chat directly with our technical onboarding leads:
                        </p>
                        <a href="https://wa.me/918104055511?text=Hello%20AHV%20Software,%20I%20would%20like%20to%20request%20onboarding%20access%20to%20the%20WhatsApp%20Cloud%20Portal."
                           target="_blank" rel="noopener noreferrer"
                           class="btn btn-primary min-h-[40px] bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl w-full gap-1.5 text-xs font-bold border-0 shadow-sm justify-center">
                            <i class="ti ti-brand-whatsapp text-base"></i> Chat on WhatsApp (+91 81040 55511)
                        </a>
                    </div>

                    <div class="pt-1 flex items-center gap-2 text-xs text-slate-500">
                        <i class="ti ti-shield-check text-emerald-600 text-lg shrink-0"></i>
                        <span>Zero public signup spam &bull; Strict client data isolation</span>
                    </div>
                </div>

                {{-- Contact Us Form with Bot Security (Captcha) --}}
                <div id="contact-form" class="lg:col-span-7 rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 space-y-4 shadow-sm">
                    <div>
                        <h3 class="font-bold text-base text-slate-900">Request Workspace Access</h3>
                        <p class="text-xs text-slate-500">Submit your business details and requirements below.</p>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success text-xs text-white p-3 rounded-xl shadow-xs flex items-center gap-2">
                            <i class="ti ti-circle-check text-lg shrink-0"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-error text-xs text-white p-3 rounded-xl shadow-xs">
                            <ul class="list-disc list-inside space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.inquiry.store') }}" class="space-y-3">
                        @csrf
                        {{-- Honeypot security field: hidden from humans, bots fill it out --}}
                        <input type="text" name="b_hp_security_check" style="display:none !important;" tabindex="-1" autocomplete="off" aria-hidden="true">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label py-0.5 text-xs font-semibold text-slate-700">Your Name <span class="text-error">*</span></label>
                                <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Rahul Sharma"
                                       class="input input-bordered text-xs sm:text-sm w-full rounded-xl min-h-[42px]">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5 text-xs font-semibold text-slate-700">Phone / WhatsApp Number <span class="text-error">*</span></label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="e.g. +91 98765 43210"
                                       class="input input-bordered text-xs sm:text-sm w-full rounded-xl min-h-[42px]">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="form-control">
                                <label class="label py-0.5 text-xs font-semibold text-slate-700">Business / Brand Name</label>
                                <input type="text" name="business_name" value="{{ old('business_name') }}" placeholder="e.g. Fit Ganganagar"
                                       class="input input-bordered text-xs sm:text-sm w-full rounded-xl min-h-[42px]">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5 text-xs font-semibold text-slate-700">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="e.g. contact@business.com"
                                       class="input input-bordered text-xs sm:text-sm w-full rounded-xl min-h-[42px]">
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5 text-xs font-semibold text-slate-700">Service You Need <span class="text-error">*</span></label>
                            <select name="service_interested" class="select select-bordered text-xs sm:text-sm w-full rounded-xl min-h-[42px]" required>
                                <option value="whatsapp" {{ old('service_interested') === 'whatsapp' ? 'selected' : '' }}>
                                    WhatsApp Official Cloud API &amp; Broadcasts (Available &amp; Live)
                                </option>
                                <option value="sms" {{ old('service_interested') === 'sms' ? 'selected' : '' }}>
                                    Enterprise SMS Gateway &amp; DLT Route (Coming Soon)
                                </option>
                                <option value="voice" {{ old('service_interested') === 'voice' ? 'selected' : '' }}>
                                    AI Voice Calling Agent &amp; Dialler (Coming Soon)
                                </option>
                                <option value="all" {{ old('service_interested') === 'all' ? 'selected' : '' }}>
                                    Full CPaaS Suite (WhatsApp + SMS + Voice)
                                </option>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5 text-xs font-semibold text-slate-700">Message / Expected Volume</label>
                            <textarea name="message" rows="2" placeholder="Tell us about your business or expected broadcast volume..."
                                      class="textarea textarea-bordered text-xs sm:text-sm w-full rounded-xl">{{ old('message') }}</textarea>
                        </div>

                        {{-- Anti-Bot Security Captcha --}}
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                            <div class="flex items-center gap-2">
                                <i class="ti ti-shield-check text-emerald-600 text-base"></i>
                                <span class="text-xs font-semibold text-slate-700">Security Check:</span>
                                <span class="badge badge-neutral font-mono font-bold text-xs">{{ $cNum1 }} + {{ $cNum2 }} = ?</span>
                            </div>
                            <div class="w-full sm:w-28">
                                <input type="number" name="captcha_answer" required placeholder="Answer"
                                       class="input input-bordered text-xs sm:text-sm w-full font-mono text-center font-bold rounded-xl min-h-[38px]">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary min-h-[44px] w-full gap-2 bg-emerald-600 hover:bg-emerald-700 text-white border-0 shadow-sm text-xs sm:text-sm font-bold rounded-xl justify-center">
                            <i class="ti ti-send"></i> Submit Onboarding Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
