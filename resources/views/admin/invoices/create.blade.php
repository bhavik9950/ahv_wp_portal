<x-app-layout>
    <x-slot name="title">Generate Invoice</x-slot>

    <div class="space-y-5 max-w-5xl mx-auto"
         x-data="invoiceBuilder({
             organizations: @js($organizations),
             plans: @js($plans),
             defaultType: @js($defaultType),
             defaultMonth: @js($defaultMonth),
             previewUrl: @js(route('admin.invoices.preview-usage'))
         })">

        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-ghost btn-sm btn-square">
                    <i class="ti ti-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold">Generate Client Bill / Receipt</h2>
                    <p class="text-xs opacity-60">Create subscription receipts, monthly Meta usage bills, or custom invoices for past work.</p>
                </div>
            </div>
        </div>

        {{-- Bill Type Tabs --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <button type="button" @click="setType('subscription')"
                    :class="type === 'subscription' ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-base-300 bg-base-100'"
                    class="p-4 rounded-xl border text-left transition cursor-pointer">
                <div class="flex items-center gap-2 font-bold text-sm">
                    <i class="ti ti-crown text-primary text-lg"></i>
                    Subscription / Membership
                </div>
                <p class="text-xs opacity-60 mt-1">Monthly, half-yearly, yearly, or lifetime plan bill with validity dates.</p>
            </button>

            <button type="button" @click="setType('monthly_usage')"
                    :class="type === 'monthly_usage' ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-base-300 bg-base-100'"
                    class="p-4 rounded-xl border text-left transition cursor-pointer">
                <div class="flex items-center gap-2 font-bold text-sm">
                    <i class="ti ti-message-dollar text-emerald-600 text-lg"></i>
                    Monthly Message Usage
                </div>
                <p class="text-xs opacity-60 mt-1">Single + Campaign Meta charges (conditional on Client vs Company Meta Card).</p>
            </button>

            <button type="button" @click="setType('custom')"
                    :class="type === 'custom' ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-base-300 bg-base-100'"
                    class="p-4 rounded-xl border text-left transition cursor-pointer">
                <div class="flex items-center gap-2 font-bold text-sm">
                    <i class="ti ti-file-plus text-amber-600 text-lg"></i>
                    Custom / Past Work Bill
                </div>
                <p class="text-xs opacity-60 mt-1">Custom line items for past projects, setup fees, or consulting services.</p>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.invoices.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="type" :value="type">

            {{-- Client Selection & Details --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Client / Organization Details</h3>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-1">
                                <span class="label-text text-xs font-semibold">
                                    Select Organization <span x-show="type !== 'custom'" class="text-error">*</span>
                                </span>
                            </label>
                            <select name="organization_id" x-model="organizationId" @change="onOrgChange()"
                                    :required="type !== 'custom'"
                                    class="select select-bordered select-sm">
                                <option value="">— Select Client / Custom Client —</option>
                                <template x-for="org in organizations" :key="org.id">
                                    <option :value="org.id" x-text="org.name + (org.meta_billing_mode === 'company_card' ? ' (Company Card)' : ' (Client Card)')"></option>
                                </template>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Client / Company Name *</span></label>
                            <input type="text" name="client_name" x-model="clientName" required class="input input-bordered input-sm" placeholder="Client or organization name">
                        </div>

                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Client Billing Email *</span></label>
                            <input type="email" name="client_email" x-model="clientEmail" required class="input input-bordered input-sm" placeholder="client@example.com">
                        </div>

                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Client GSTIN (optional)</span></label>
                            <input type="text" name="client_gst" x-model="clientGst" class="input input-bordered input-sm font-mono">
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Client Address (optional)</span></label>
                            <input type="text" name="client_address" x-model="clientAddress" class="input input-bordered input-sm">
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 1: SUBSCRIPTION / MEMBERSHIP --}}
            <div x-show="type === 'subscription'" class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Subscription Plan &amp; Validity</h3>

                    <div class="grid sm:grid-cols-4 gap-4">
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Plan *</span></label>
                            <select name="plan" x-model="plan" @change="onPlanChange()" :disabled="type !== 'subscription'" class="select select-bordered select-sm">
                                <template x-for="(p, key) in plans" :key="key">
                                    <option :value="key" x-text="p.label + ' (₹' + p.price + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Amount (₹) *</span></label>
                            <input type="number" step="0.01" min="0" name="plan_amount" x-model="planAmount" :disabled="type !== 'subscription'" class="input input-bordered input-sm font-mono" required>
                        </div>

                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Valid From *</span></label>
                            <input type="date" name="valid_from" x-model="validFrom" @change="onPlanChange()" :disabled="type !== 'subscription'" class="input input-bordered input-sm font-mono" required>
                        </div>

                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Valid Until (blank = Lifetime)</span></label>
                            <input type="date" name="valid_until" x-model="validUntil" :disabled="type !== 'subscription'" class="input input-bordered input-sm font-mono">
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: MONTHLY META MESSAGE USAGE --}}
            <div x-show="type === 'monthly_usage'" class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Monthly Meta Message Charges (Single + Campaign)</h3>
                            <p class="text-xs opacity-60">Automatically fetches outbound sent/delivered/read messages for the selected month.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="month" name="billing_month" x-model="billingMonth" @change="fetchUsage()" :disabled="type !== 'monthly_usage'" class="input input-bordered input-sm font-mono">
                            <button type="button" @click="fetchUsage()" class="btn btn-sm btn-outline gap-1">
                                <i class="ti ti-refresh"></i> Fetch Usage
                            </button>
                        </div>
                    </div>

                    <div x-show="usageLoading" class="text-xs opacity-60 flex items-center gap-2 py-2">
                        <span class="loading loading-spinner loading-xs"></span> Calculating monthly message usage...
                    </div>

                    <template x-if="usageData && !usageLoading">
                        <div class="space-y-4">
                            {{-- Conditional Card Banner --}}
                            <div :class="usageData.uses_company_card ? 'alert-info' : 'alert-warning'" class="alert text-xs">
                                <i :class="usageData.uses_company_card ? 'ti-credit-card' : 'ti-shield-check'" class="ti text-lg"></i>
                                <div>
                                    <div class="font-bold" x-text="usageData.uses_company_card ? 'Company Meta Card Active — Message Charges Will Be Billed' : 'Client Own Meta Card Active — ₹0.00 Meta Message Charges'"></div>
                                    <div class="opacity-80" x-text="usageData.uses_company_card
                                        ? 'This client uses your company Meta payment card. Single + Campaign messages are billed at configured Meta rates.'
                                        : 'This client pays Meta directly on their own card. Usage summary shows ₹0.00 billable charges (editable below if needed).'">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="p-3 rounded-lg bg-base-200/60">
                                    <div class="text-xs opacity-60">Campaign Messages</div>
                                    <div class="text-lg font-bold" x-text="usageData.counts.campaign_messages"></div>
                                </div>
                                <div class="p-3 rounded-lg bg-base-200/60">
                                    <div class="text-xs opacity-60">Single / Direct Messages</div>
                                    <div class="text-lg font-bold" x-text="usageData.counts.single_messages"></div>
                                </div>
                                <div class="p-3 rounded-lg bg-base-200/60">
                                    <div class="text-xs opacity-60">Total Billable Messages</div>
                                    <div class="text-lg font-bold text-primary" x-text="usageData.counts.total_billable_messages"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Editable Usage Line Items --}}
                    <div x-show="usageItems.length > 0" class="space-y-2">
                        <div class="text-xs font-bold opacity-70">Line Items (Editable before generating)</div>
                        <template x-for="(item, idx) in usageItems" :key="idx">
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <input type="text" :name="'items[' + idx + '][description]'" x-model="item.description" :disabled="type !== 'monthly_usage'" class="input input-bordered input-xs col-span-6" required>
                                <input type="number" step="1" min="0" :name="'items[' + idx + '][quantity]'" x-model="item.quantity" :disabled="type !== 'monthly_usage'" class="input input-bordered input-xs col-span-2 text-right font-mono" required>
                                <input type="number" step="0.01" min="0" :name="'items[' + idx + '][unit_price]'" x-model="item.unit_price" :disabled="type !== 'monthly_usage'" class="input input-bordered input-xs col-span-2 text-right font-mono" required>
                                <div class="col-span-2 text-right text-xs font-bold font-mono" x-text="'₹' + (Number(item.quantity || 0) * Number(item.unit_price || 0)).toFixed(2)"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- TAB 3: CUSTOM / PAST WORK BILL --}}
            <div x-show="type === 'custom'" class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Custom Line Items (Past Work / Services)</h3>
                            <p class="text-xs opacity-60">Add any services, past campaign work, setup fees, or custom deliverables.</p>
                        </div>
                        <button type="button" @click="addCustomItem()" class="btn btn-xs btn-outline btn-primary gap-1">
                            <i class="ti ti-plus"></i> Add Item
                        </button>
                    </div>

                    <div class="space-y-2">
                        <div class="grid grid-cols-12 gap-2 text-[0.7rem] font-bold opacity-60 px-1">
                            <div class="col-span-6">Description *</div>
                            <div class="col-span-2 text-right">Qty *</div>
                            <div class="col-span-2 text-right">Unit Price (₹) *</div>
                            <div class="col-span-2 text-right">Amount</div>
                        </div>

                        <template x-for="(item, idx) in customItems" :key="idx">
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <input type="text" :name="'items[' + idx + '][description]'" x-model="item.description" :disabled="type !== 'custom'" placeholder="e.g. WABA Setup & Past Campaign Management" class="input input-bordered input-sm col-span-6 text-xs" required>
                                <input type="number" step="0.01" min="0.01" :name="'items[' + idx + '][quantity]'" x-model="item.quantity" :disabled="type !== 'custom'" class="input input-bordered input-sm col-span-2 text-right font-mono text-xs" required>
                                <input type="number" step="0.01" min="0" :name="'items[' + idx + '][unit_price]'" x-model="item.unit_price" :disabled="type !== 'custom'" class="input input-bordered input-sm col-span-2 text-right font-mono text-xs" required>
                                <div class="col-span-2 flex items-center justify-end gap-1">
                                    <span class="text-xs font-bold font-mono" x-text="'₹' + (Number(item.quantity || 0) * Number(item.unit_price || 0)).toFixed(2)"></span>
                                    <button type="button" @click="removeCustomItem(idx)" x-show="customItems.length > 1" class="btn btn-ghost btn-xs text-error px-1">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Invoice Metadata, Tax, Status & Email Option --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-5 space-y-4">
                    <h3 class="font-bold text-sm uppercase tracking-wide opacity-70">Totals, Status &amp; Delivery</h3>

                    <div class="grid sm:grid-cols-5 gap-4">
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Issue Date *</span></label>
                            <input type="date" name="issue_date" value="{{ now('Asia/Kolkata')->toDateString() }}" class="input input-bordered input-sm font-mono" required>
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Due Date</span></label>
                            <input type="date" name="due_date" value="{{ now('Asia/Kolkata')->addDays(7)->toDateString() }}" class="input input-bordered input-sm font-mono">
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">GST / Tax (%)</span></label>
                            <input type="number" step="0.01" min="0" max="100" name="tax_percent" x-model="taxPercent" class="input input-bordered input-sm font-mono">
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Discount (₹)</span></label>
                            <input type="number" step="0.01" min="0" name="discount" x-model="discount" class="input input-bordered input-sm font-mono">
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Payment Status *</span></label>
                            <select name="status" x-model="status" class="select select-bordered select-sm">
                                <option value="unpaid">Unpaid (Invoice)</option>
                                <option value="paid">Paid (Receipt)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Payment Reference / UTR (if paid)</span></label>
                            <input type="text" name="payment_reference" class="input input-bordered input-sm font-mono" placeholder="e.g. UTR4290182931">
                        </div>
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Notes (printed on bill)</span></label>
                            <input type="text" name="notes" class="input input-bordered input-sm" placeholder="Thank you for your business!">
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2 border-t border-base-200">
                        <label class="label cursor-pointer justify-start gap-2.5">
                            <input type="checkbox" name="send_email" value="1" checked class="checkbox checkbox-primary checkbox-sm">
                            <span class="label-text text-xs font-semibold">Email PDF directly to client after generating</span>
                        </label>

                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <div class="text-[0.7rem] opacity-60">Estimated Grand Total</div>
                                <div class="text-xl font-extrabold text-primary" x-text="'₹' + grandTotal().toFixed(2)"></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                                <i class="ti ti-file-check"></i> Generate Bill
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        @verbatim
        window.invoiceBuilder = function (cfg) {
            return {
                organizations: cfg.organizations || [],
                plans: cfg.plans || {},
                type: cfg.defaultType || 'subscription',
                organizationId: '',
                clientName: '',
                clientEmail: '',
                clientAddress: '',
                clientGst: '',
                plan: 'monthly',
                planAmount: (cfg.plans && cfg.plans.monthly) ? cfg.plans.monthly.price : 649,
                validFrom: new Date().toISOString().slice(0, 10),
                validUntil: '',
                billingMonth: cfg.defaultMonth || new Date().toISOString().slice(0, 7),
                usageLoading: false,
                usageData: null,
                usageItems: [],
                customItems: [{ description: '', quantity: 1, unit_price: 0 }],
                taxPercent: 0,
                discount: 0,
                status: 'unpaid',

                init() {
                    this.onPlanChange();
                },

                setType(t) {
                    this.type = t;
                    if (t === 'monthly_usage' && this.organizationId) {
                        this.fetchUsage();
                    }
                },

                onOrgChange() {
                    const org = this.organizations.find(o => String(o.id) === String(this.organizationId));
                    if (org) {
                        this.clientName = org.name || '';
                        this.clientEmail = org.email || '';
                        this.clientAddress = org.address || '';
                        this.clientGst = org.gst || '';
                        if (org.plan && this.plans[org.plan]) {
                            this.plan = org.plan;
                            this.onPlanChange();
                        }
                        if (this.type === 'monthly_usage') {
                            this.fetchUsage();
                        }
                    }
                },

                onPlanChange() {
                    const p = this.plans[this.plan];
                    if (!p) return;
                    this.planAmount = p.price;
                    if (p.days && this.validFrom) {
                        const d = new Date(this.validFrom + 'T00:00:00');
                        d.setDate(d.getDate() + Number(p.days));
                        this.validUntil = d.toISOString().slice(0, 10);
                    } else {
                        this.validUntil = '';
                    }
                },

                async fetchUsage() {
                    if (!this.organizationId || !this.billingMonth) return;
                    this.usageLoading = true;
                    try {
                        const url = `${cfg.previewUrl}?organization_id=${encodeURIComponent(this.organizationId)}&billing_month=${encodeURIComponent(this.billingMonth)}`;
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        this.usageData = data;
                        this.usageItems = (data.items || []).map(i => ({ ...i }));
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.usageLoading = false;
                    }
                },

                addCustomItem() {
                    this.customItems.push({ description: '', quantity: 1, unit_price: 0 });
                },

                removeCustomItem(idx) {
                    this.customItems.splice(idx, 1);
                },

                subtotal() {
                    if (this.type === 'subscription') {
                        return Number(this.planAmount || 0);
                    }
                    const list = this.type === 'monthly_usage' ? this.usageItems : this.customItems;
                    return list.reduce((sum, i) => sum + (Number(i.quantity || 0) * Number(i.unit_price || 0)), 0);
                },

                grandTotal() {
                    const sub = this.subtotal();
                    const taxable = Math.max(0, sub - Number(this.discount || 0));
                    const tax = taxable * (Number(this.taxPercent || 0) / 100);
                    return taxable + tax;
                }
            };
        };
        @endverbatim
    </script>
</x-app-layout>
