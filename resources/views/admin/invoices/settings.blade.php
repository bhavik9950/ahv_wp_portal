<x-app-layout>
    <x-slot name="title">Company &amp; Meta Billing Settings</x-slot>

    <div class="space-y-6 max-w-4xl mx-auto">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-ghost btn-sm btn-square">
                <i class="ti ti-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold">Company Profile &amp; Meta Message Rates</h2>
                <p class="text-xs opacity-60">These details appear on all generated PDF invoices and calculate monthly message usage bills.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.invoices.settings.update') }}" class="space-y-6">
            @csrf

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Your Company Details (Printed on Bills)</h3>
                        <img src="{{ asset('images/logo-full.png') }}" alt="Logo" class="h-8">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Company Name *</span></label>
                            <input type="text" name="company_name" value="{{ old('company_name', $company['name'] ?? '') }}" class="input input-bordered input-sm" required>
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Billing Email *</span></label>
                            <input type="email" name="company_email" value="{{ old('company_email', $company['email'] ?? '') }}" class="input input-bordered input-sm" required>
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Phone</span></label>
                            <input type="text" name="company_phone" value="{{ old('company_phone', $company['phone'] ?? '') }}" class="input input-bordered input-sm">
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">GSTIN (optional)</span></label>
                            <input type="text" name="company_gstin" value="{{ old('company_gstin', $company['gstin'] ?? '') }}" class="input input-bordered input-sm font-mono">
                        </div>
                        <div class="form-control sm:col-span-2">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Company Address</span></label>
                            <input type="text" name="company_address" value="{{ old('company_address', $company['address'] ?? '') }}" class="input input-bordered input-sm">
                        </div>
                        <div class="form-control sm:col-span-2">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Website</span></label>
                            <input type="text" name="company_website" value="{{ old('company_website', $company['website'] ?? '') }}" class="input input-bordered input-sm">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <div>
                        <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Meta Per-Message Rates (INR ₹)</h3>
                        <p class="text-xs opacity-60">Applied only when an organization's Meta Card Mode is set to <strong>Company Meta Card</strong>.</p>
                    </div>

                    <div class="grid sm:grid-cols-4 gap-4">
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Marketing (₹)</span></label>
                            <input type="number" step="0.01" min="0" name="rate_marketing" value="{{ old('rate_marketing', $metaRates['marketing']) }}" class="input input-bordered input-sm font-mono" required>
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Utility (₹)</span></label>
                            <input type="number" step="0.01" min="0" name="rate_utility" value="{{ old('rate_utility', $metaRates['utility']) }}" class="input input-bordered input-sm font-mono" required>
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Authentication (₹)</span></label>
                            <input type="number" step="0.01" min="0" name="rate_authentication" value="{{ old('rate_authentication', $metaRates['authentication']) }}" class="input input-bordered input-sm font-mono" required>
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Service / Single (₹)</span></label>
                            <input type="number" step="0.01" min="0" name="rate_service" value="{{ old('rate_service', $metaRates['service']) }}" class="input input-bordered input-sm font-mono" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm">Save Billing Settings</button>
            </div>
        </form>
    </div>
</x-app-layout>
