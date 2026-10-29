<x-app-layout>
    <x-slot name="title">Manage Organization Plan · {{ $organization->name }}</x-slot>

    <div class="space-y-6 max-w-4xl mx-auto">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.organizations.index') }}" class="btn btn-ghost btn-sm btn-square">
                <i class="ti ti-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold">Manage Organization Plan &amp; Modules</h2>
                <p class="text-xs opacity-60">Company: <span class="font-semibold text-base-content">{{ $organization->name }}</span> (ID: #{{ $organization->getKey() }})</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">General Information</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium text-xs">Organization Name</span></label>
                            <input type="text" name="name" value="{{ old('name', $organization->name) }}" class="input input-bordered input-sm" required>
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium text-xs">Account Status</span></label>
                            <select name="status" class="select select-bordered select-sm">
                                <option value="active" {{ old('status', $organization->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $organization->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Subscription Plan &amp; Expiry</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-medium text-xs">Assigned Plan</span></label>
                            <select name="plan" id="admin-plan-select" class="select select-bordered select-sm" onchange="toggleLifetimeField()">
                                <option value="lifetime" {{ old('plan', $organization->plan) === 'lifetime' || $organization->is_lifetime ? 'selected' : '' }}>
                                    Lifetime Access (No Expiry - ₹20,000 / one-time)
                                </option>
                                <option value="yearly" {{ old('plan', $organization->plan) === 'yearly' && ! $organization->is_lifetime ? 'selected' : '' }}>
                                    1 Year Access (365 days)
                                </option>
                                <option value="half_yearly" {{ old('plan', $organization->plan) === 'half_yearly' && ! $organization->is_lifetime ? 'selected' : '' }}>
                                    6 Months Access (180 days)
                                </option>
                                <option value="monthly" {{ old('plan', $organization->plan) === 'monthly' && ! $organization->is_lifetime ? 'selected' : '' }}>
                                    1 Month Access (30 days)
                                </option>
                            </select>
                        </div>

                        <div class="form-control" id="expiry-wrapper">
                            <label class="label"><span class="label-text font-medium text-xs">Subscription Expiry Date</span></label>
                            <input type="datetime-local" name="subscription_expires_at" id="expiry-input"
                                   value="{{ old('subscription_expires_at', $organization->subscription_expires_at?->format('Y-m-d\TH:i')) }}"
                                   class="input input-bordered input-sm font-mono">
                            <span class="text-[0.7rem] opacity-60 mt-1">Leave empty to auto-calculate from selected plan duration.</span>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" name="is_lifetime" id="lifetime-checkbox" value="1"
                                   {{ old('is_lifetime', $organization->is_lifetime) ? 'checked' : '' }}
                                   class="checkbox checkbox-primary checkbox-sm" onchange="toggleLifetimeField()">
                            <div>
                                <span class="label-text font-bold text-xs">Permanent Lifetime Access</span>
                                <span class="block text-[0.7rem] opacity-60">This organization will never expire and will never be prompted for subscription renewal.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Allowed Modules</h3>
                        <span class="text-xs opacity-50">Select modules enabled for this client</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pt-2">
                        @foreach ($availableModules as $modKey => $modLabel)
                            @php($checked = $organization->hasModule($modKey))
                            <label class="flex items-center gap-2.5 p-2.5 rounded-lg border border-base-200 hover:bg-base-200/40 cursor-pointer">
                                <input type="checkbox" name="allowed_modules[]" value="{{ $modKey }}" {{ $checked ? 'checked' : '' }}
                                       class="checkbox checkbox-sm checkbox-primary">
                                <span class="text-xs font-medium">{{ $modLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.organizations.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm gap-2">
                    <i class="ti ti-check"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleLifetimeField() {
            const planSelect = document.getElementById('admin-plan-select');
            const lifetimeCheck = document.getElementById('lifetime-checkbox');
            const expiryWrapper = document.getElementById('expiry-wrapper');

            const isLifetime = (planSelect && planSelect.value === 'lifetime') || (lifetimeCheck && lifetimeCheck.checked);

            if (lifetimeCheck && planSelect && planSelect.value === 'lifetime') {
                lifetimeCheck.checked = true;
            }

            if (expiryWrapper) {
                expiryWrapper.style.opacity = isLifetime ? '0.4' : '1';
                expiryWrapper.style.pointerEvents = isLifetime ? 'none' : 'auto';
            }
        }
        toggleLifetimeField();
    </script>
</x-app-layout>

