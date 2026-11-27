<x-app-layout>
    <x-slot name="title">Manage Client Subscription Plans</x-slot>

    <div class="space-y-6 max-w-6xl mx-auto">
        {{-- Admin Header Banner --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body sm:flex-row sm:items-center sm:justify-between gap-4 p-5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-primary badge-sm font-bold">Platform Admin</span>
                        <h2 class="text-xl font-bold">Client Subscription Plans</h2>
                    </div>
                    <p class="text-xs opacity-70 mt-1">
                        Add, edit, or remove the subscription plans shown to your client organizations (e.g. FIT GANGANAGAR SOCIETY).
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.organizations.index') }}" class="btn btn-outline btn-sm gap-1.5">
                        <i class="ti ti-building"></i> Client Organizations
                    </a>
                    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-outline btn-sm gap-1.5">
                        <i class="ti ti-receipt-2"></i> UTR Requests
                    </a>
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-primary btn-sm gap-1.5">
                        <i class="ti ti-file-invoice"></i> Invoices &amp; Billing
                    </a>
                </div>
            </div>
        </div>

        {{-- Existing Plans (Editable Cards) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($plans as $planKey => $plan)
                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="badge badge-outline font-mono text-xs">{{ $planKey }}</span>
                                @if (($plan['days'] ?? null) === null)
                                    <span class="badge badge-success badge-xs">Lifetime</span>
                                @else
                                    <span class="badge badge-ghost badge-xs">{{ $plan['days'] }} days</span>
                                @endif
                            </div>

                            @if (count($plans) > 1)
                                <form method="POST" action="{{ route('admin.subscriptions.plans.delete', $planKey) }}"
                                      onsubmit="return confirm('Remove plan {{ $plan['label'] }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-xs text-error gap-1">
                                        <i class="ti ti-trash"></i> Remove
                                    </button>
                                </form>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.subscriptions.plans.save') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="plan_key" value="{{ $planKey }}">

                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <div class="form-control sm:col-span-2">
                                    <label class="label py-0.5"><span class="label-text text-xs font-semibold">Plan Name *</span></label>
                                    <input type="text" name="label" value="{{ $plan['label'] }}" class="input input-bordered input-sm" required>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0.5"><span class="label-text text-xs font-semibold">Price (₹) *</span></label>
                                    <input type="number" name="price" min="0" value="{{ $plan['price'] }}" class="input input-bordered input-sm font-mono" required>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0.5"><span class="label-text text-xs font-semibold">Discount (% OFF)</span></label>
                                    <input type="number" name="discount_percent" min="0" max="95" value="{{ $plan['discount_percent'] ?? 0 }}" class="input input-bordered input-sm font-mono">
                                </div>
                            </div>

                            @if (($plan['discount_percent'] ?? 0) > 0)
                                <div class="text-xs text-success font-semibold bg-success/10 px-2.5 py-1 rounded-lg flex items-center justify-between">
                                    <span>Client Pays ({{ $plan['discount_percent'] }}% OFF):</span>
                                    <span class="font-mono">
                                        <span class="line-through opacity-60 mr-1">₹{{ number_format($plan['price']) }}</span>
                                        ₹{{ number_format($plan['final_price']) }}
                                    </span>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                                <div class="form-control">
                                    <label class="label py-0.5"><span class="label-text text-xs font-semibold">Duration (Days)</span></label>
                                    <input type="number" name="days" min="1" max="3650" value="{{ $plan['days'] ?? 30 }}" class="input input-bordered input-sm font-mono">
                                </div>
                                <div class="form-control pt-4">
                                    <label class="label cursor-pointer justify-start gap-2">
                                        <input type="checkbox" name="is_lifetime" value="1" {{ ($plan['days'] ?? null) === null ? 'checked' : '' }} class="checkbox checkbox-primary checkbox-sm">
                                        <span class="label-text text-xs font-semibold">Lifetime (No Expiry)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs font-semibold">Description *</span></label>
                                <input type="text" name="description" value="{{ $plan['description'] }}" class="input input-bordered input-sm" required>
                            </div>

                            <div class="flex justify-end pt-1">
                                <button type="submit" class="btn btn-primary btn-xs gap-1">
                                    <i class="ti ti-check"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Add New Plan --}}
        <div class="card bg-base-100 border border-dashed border-primary/40 shadow-sm">
            <div class="card-body p-5 space-y-4">
                <div>
                    <h3 class="font-bold text-sm uppercase tracking-wide text-primary flex items-center gap-1.5">
                        <i class="ti ti-plus"></i> Add New Client Plan
                    </h3>
                    <p class="text-xs opacity-60">Create a new plan (e.g. quarterly, enterprise, trial) that clients can choose from.</p>
                </div>

                <form method="POST" action="{{ route('admin.subscriptions.plans.save') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                    @csrf
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-semibold">Plan Key *</span></label>
                        <input type="text" name="plan_key" placeholder="e.g. quarterly" pattern="[a-z0-9_]+" class="input input-bordered input-sm font-mono" required>
                    </div>
                    <div class="form-control lg:col-span-2">
                        <label class="label py-0.5"><span class="label-text text-xs font-semibold">Display Name *</span></label>
                        <input type="text" name="label" placeholder="e.g. 3 Months Access" class="input input-bordered input-sm" required>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-semibold">Price (₹) *</span></label>
                        <input type="number" name="price" min="0" placeholder="1799" class="input input-bordered input-sm font-mono" required>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-semibold">Discount (% OFF)</span></label>
                        <input type="number" name="discount_percent" min="0" max="95" value="0" class="input input-bordered input-sm font-mono">
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-semibold">Days</span></label>
                        <input type="number" name="days" min="1" max="3650" value="90" class="input input-bordered input-sm font-mono">
                    </div>
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-2 pb-2">
                            <input type="checkbox" name="is_lifetime" value="1" class="checkbox checkbox-primary checkbox-sm">
                            <span class="label-text text-xs font-semibold">Lifetime</span>
                        </label>
                    </div>
                    <div class="form-control sm:col-span-2 lg:col-span-4">
                        <label class="label py-0.5"><span class="label-text text-xs font-semibold">Description *</span></label>
                        <input type="text" name="description" placeholder="Short description shown on client pricing card..." class="input input-bordered input-sm" required>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary btn-sm w-full gap-1.5">
                            <i class="ti ti-plus"></i> Add Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
