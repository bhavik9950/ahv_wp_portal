<x-app-layout>
    <x-slot name="title">Subscription &amp; Billing</x-slot>

    <div class="space-y-6 max-w-6xl mx-auto">
        {{-- Current Status Banner --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm">
            <div class="card-body sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold">Organization Subscription</h2>
                        @if ($organization->is_lifetime)
                            <span class="badge badge-success font-semibold gap-1"><i class="ti ti-infinity"></i> Lifetime Access</span>
                        @elseif ($organization->hasActiveSubscription())
                            <span class="badge badge-success font-semibold gap-1"><i class="ti ti-circle-check"></i> Active</span>
                        @else
                            <span class="badge badge-error font-semibold gap-1"><i class="ti ti-alert-circle"></i> Expired (Read-only)</span>
                        @endif
                    </div>
                    <p class="text-sm opacity-70">
                        Organization: <span class="font-semibold text-base-content">{{ $organization->name }}</span>
                        @if (! $organization->is_lifetime && $organization->subscription_expires_at)
                            &bull; Valid until: <span class="font-semibold text-base-content">{{ $organization->subscription_expires_at->format('d M Y, h:i A') }}</span>
                            ({{ $organization->subscriptionDaysRemaining() }} days left)
                        @endif
                    </p>
                </div>
                <div>
                    <a href="#plans-section" class="btn btn-primary btn-sm gap-2">
                        <i class="ti ti-sparkles"></i> Upgrade / Renew Plan
                    </a>
                </div>
            </div>
        </div>

        {{-- Pricing Plans --}}
        <div id="plans-section" class="space-y-4">
            <div>
                <h3 class="text-lg font-bold">Available Subscription Plans</h3>
                <p class="text-xs opacity-60">Choose the best duration for your business broadcasts and customer chats.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($plans as $planKey => $plan)
                    <div class="card bg-base-100 border {{ $planKey === 'lifetime' ? 'border-primary ring-2 ring-primary/20' : 'border-base-300' }} shadow-sm flex flex-col justify-between">
                        <div class="card-body p-5">
                            @if ($planKey === 'lifetime')
                                <span class="badge badge-primary badge-sm uppercase font-bold self-start mb-2">⭐ Best Value</span>
                            @endif
                            <h4 class="font-bold text-base">{{ $plan['label'] }}</h4>
                            <div class="mt-2 mb-3">
                                <span class="text-3xl font-extrabold font-mono">₹{{ number_format($plan['price']) }}</span>
                                <span class="text-xs opacity-60">/ {{ $planKey === 'lifetime' ? 'one-time' : ($plan['days'] ? $plan['days'].' days' : 'period') }}</span>
                            </div>
                            <p class="text-xs opacity-70 mb-4">{{ $plan['description'] }}</p>
                            <ul class="text-xs space-y-1.5 opacity-80 mb-4">
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Broadcast Campaigns</li>
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> 1-on-1 Live Chat Inbox</li>
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Meta Approved Templates</li>
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Contact Groups &amp; CSV Import</li>
                                <li class="flex items-center gap-1.5"><i class="ti ti-check text-success"></i> Direct Card / Meta Billing</li>
                            </ul>
                        </div>
                        <div class="p-5 pt-0">
                            <button type="button"
                                    onclick="selectPlan('{{ $planKey }}', {{ $plan['price'] }})"
                                    class="btn {{ $planKey === 'lifetime' ? 'btn-primary' : 'btn-outline' }} btn-sm w-full gap-1">
                                <i class="ti ti-check"></i> Choose Plan
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Payment Methods & UTR Submission --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            {{-- Payment Details --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body p-5 space-y-4">
                        <h3 class="font-bold text-base flex items-center gap-2">
                            <i class="ti ti-qrcode text-lg text-primary"></i> Scan &amp; Pay via UPI
                        </h3>
                        <p class="text-xs opacity-70">
                            Pay using any UPI app (Google Pay, PhonePe, Paytm, BHIM, Cred) directly to AHV:
                        </p>

                        <div class="flex flex-col items-center justify-center p-4 bg-base-200/50 rounded-xl border border-base-300">
                            @php($qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='.urlencode('upi://pay?pa='.$upiId.'&pn=AHV%20Software&cu=INR'))
                            <img src="{{ $qrUrl }}" alt="UPI QR Code" class="w-44 h-44 rounded-lg shadow-sm border bg-white p-2">
                            <div class="mt-3 text-center">
                                <span class="text-xs opacity-60 block">UPI ID:</span>
                                <div class="font-mono text-sm font-semibold flex items-center gap-1 justify-center mt-0.5">
                                    <span id="upi-text">{{ $upiId }}</span>
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $upiId }}'); alert('UPI ID copied!')"
                                            class="btn btn-ghost btn-xs" title="Copy UPI ID">
                                        <i class="ti ti-copy text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="divider my-1 text-xs opacity-50">OR BANK TRANSFER</div>

                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-base-200">
                                <span class="opacity-60">Bank Name</span>
                                <span class="font-semibold">{{ $bankDetails['bank_name'] ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-base-200">
                                <span class="opacity-60">Account Holder</span>
                                <span class="font-semibold">{{ $bankDetails['account_holder'] ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-base-200">
                                <span class="opacity-60">Account Number</span>
                                <span class="font-mono font-semibold">{{ $bankDetails['account_number'] ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="opacity-60">IFSC Code</span>
                                <span class="font-mono font-semibold">{{ $bankDetails['ifsc'] ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- UTR Submission Form --}}
            <div class="lg:col-span-7">
                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body p-5">
                        <h3 class="font-bold text-base flex items-center gap-2 mb-1">
                            <i class="ti ti-file-certificate text-lg text-success"></i> Submit Payment Reference (UTR)
                        </h3>
                        <p class="text-xs opacity-70 mb-4">
                            Payment transfer karne ke baad apna UTR / Reference number niche submit karein. AHV team verify karke aapka dashboard turant activate kar degi.
                        </p>

                        <form method="POST" action="{{ route('subscription.store') }}" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-medium text-xs">Selected Plan</span></label>
                                    <select name="plan" id="plan-select" class="select select-bordered select-sm w-full" onchange="updatePlanAmount()" required>
                                        @foreach ($plans as $key => $p)
                                            <option value="{{ $key }}" data-price="{{ $p['price'] }}" {{ old('plan') === $key ? 'selected' : '' }}>
                                                {{ $p['label'] }} (₹{{ number_format($p['price']) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-control">
                                    <label class="label"><span class="label-text font-medium text-xs">Amount Paid (₹)</span></label>
                                    <input type="number" name="amount" id="amount-input" value="{{ old('amount', $plans['monthly']['price']) }}"
                                           class="input input-bordered input-sm w-full font-mono font-semibold" required readonly>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="form-control">
                                    <label class="label"><span class="label-text font-medium text-xs">Payment Method</span></label>
                                    <select name="payment_method" class="select select-bordered select-sm w-full" required>
                                        <option value="upi" {{ old('payment_method') === 'upi' ? 'selected' : '' }}>UPI (GPay / PhonePe / Paytm / QR)</option>
                                        <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Direct Bank Transfer (IMPS / NEFT)</option>
                                    </select>
                                </div>

                                <div class="form-control">
                                    <label class="label"><span class="label-text font-medium text-xs">UTR / Transaction Ref No.</span></label>
                                    <input type="text" name="utr_number" value="{{ old('utr_number') }}"
                                           placeholder="e.g. 430129849201" class="input input-bordered input-sm w-full font-mono" required>
                                    @error('utr_number')
                                        <span class="text-error text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-control">
                                <label class="label"><span class="label-text font-medium text-xs">Notes / Sender Name (Optional)</span></label>
                                <input type="text" name="notes" value="{{ old('notes') }}"
                                       placeholder="e.g. Paid from HDFC account in name of Rahul Sharma" class="input input-bordered input-sm w-full">
                            </div>

                            <div class="pt-2 flex justify-end">
                                <button type="submit" class="btn btn-success btn-sm text-white gap-2">
                                    <i class="ti ti-send"></i> Submit Payment Verification
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Previous Submissions --}}
                @if ($requests->isNotEmpty())
                    <div class="card bg-base-100 border border-base-300 shadow-sm mt-6">
                        <div class="card-body p-4">
                            <h4 class="font-bold text-xs uppercase tracking-wide opacity-60 mb-2">Previous Verification Requests</h4>
                            <div class="overflow-x-auto">
                                <table class="table table-xs">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Plan</th>
                                            <th>Amount</th>
                                            <th>UTR Number</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($requests as $req)
                                            <tr>
                                                <td class="text-xs opacity-60">{{ $req->created_at->format('d M Y') }}</td>
                                                <td class="font-medium">{{ ucfirst(str_replace('_', ' ', $req->plan)) }}</td>
                                                <td class="font-mono">₹{{ number_format($req->amount) }}</td>
                                                <td class="font-mono text-xs">{{ $req->utr_number }}</td>
                                                <td>
                                                    @if ($req->isApproved())
                                                        <span class="badge badge-success badge-xs gap-1">Approved</span>
                                                    @elseif ($req->isPending())
                                                        <span class="badge badge-warning badge-xs gap-1">Pending Verification</span>
                                                    @else
                                                        <span class="badge badge-error badge-xs gap-1">Rejected</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function selectPlan(planKey, price) {
            const select = document.getElementById('plan-select');
            if (select) {
                select.value = planKey;
                updatePlanAmount();
                select.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function updatePlanAmount() {
            const select = document.getElementById('plan-select');
            const amountInput = document.getElementById('amount-input');
            if (select && amountInput) {
                const opt = select.options[select.selectedIndex];
                const price = opt.getAttribute('data-price');
                if (price) {
                    amountInput.value = price;
                }
            }
        }
    </script>
</x-app-layout>
