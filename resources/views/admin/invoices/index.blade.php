<x-app-layout>
    <x-slot name="title">Invoices &amp; Billing</x-slot>

    <div class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="ti ti-file-invoice text-primary"></i>
                    Invoices &amp; Client Billing
                </h2>
                <p class="text-xs opacity-60">Generate subscription receipts, monthly Meta message usage bills, and custom service invoices.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.invoices.settings') }}" class="btn btn-outline btn-sm gap-1.5">
                    <i class="ti ti-settings"></i> Company &amp; Meta Rates
                </a>
                <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary btn-sm gap-1.5">
                    <i class="ti ti-plus"></i> Generate Invoice
                </a>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="card bg-base-100 border border-base-300 shadow-xs">
                <div class="card-body p-4">
                    <div class="text-xs opacity-60">Total Invoices</div>
                    <div class="text-2xl font-bold">{{ $totals['count'] }}</div>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-300 shadow-xs">
                <div class="card-body p-4">
                    <div class="text-xs opacity-60">Total Billed</div>
                    <div class="text-2xl font-bold">₹{{ number_format($totals['total_billed'], 2) }}</div>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-300 shadow-xs">
                <div class="card-body p-4">
                    <div class="text-xs text-success font-medium">Collected (Paid)</div>
                    <div class="text-2xl font-bold text-success">₹{{ number_format($totals['paid_amount'], 2) }}</div>
                </div>
            </div>
            <div class="card bg-base-100 border border-base-300 shadow-xs">
                <div class="card-body p-4">
                    <div class="text-xs text-warning font-medium">Pending (Unpaid)</div>
                    <div class="text-2xl font-bold text-warning">₹{{ number_format($totals['unpaid_amount'], 2) }}</div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.invoices.index') }}" class="card bg-base-100 border border-base-300 shadow-xs">
            <div class="card-body p-3 flex flex-col sm:flex-row flex-wrap items-end gap-3">
                <div class="form-control w-full sm:w-52">
                    <label class="label py-0.5"><span class="label-text text-xs">Search</span></label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Invoice #, client, email..." class="input input-bordered input-sm">
                </div>
                <div class="form-control w-full sm:w-44">
                    <label class="label py-0.5"><span class="label-text text-xs">Bill Type</span></label>
                    <select name="type" class="select select-bordered select-sm">
                        <option value="">All Types</option>
                        <option value="subscription" {{ $filters['type'] === 'subscription' ? 'selected' : '' }}>Subscription</option>
                        <option value="monthly_usage" {{ $filters['type'] === 'monthly_usage' ? 'selected' : '' }}>Monthly Usage</option>
                        <option value="custom" {{ $filters['type'] === 'custom' ? 'selected' : '' }}>Custom Bill</option>
                    </select>
                </div>
                <div class="form-control w-full sm:w-36">
                    <label class="label py-0.5"><span class="label-text text-xs">Status</span></label>
                    <select name="status" class="select select-bordered select-sm">
                        <option value="">All Status</option>
                        <option value="unpaid" {{ $filters['status'] === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="paid" {{ $filters['status'] === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="form-control w-full sm:w-48">
                    <label class="label py-0.5"><span class="label-text text-xs">Organization</span></label>
                    <select name="organization_id" class="select select-bordered select-sm">
                        <option value="">All Clients</option>
                        @foreach ($organizations as $org)
                            <option value="{{ $org->id }}" {{ (string) $filters['organization_id'] === (string) $org->id ? 'selected' : '' }}>{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-ghost">Reset</a>
                </div>
            </div>
        </form>

        {{-- Invoices Table --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Client / Organization</th>
                        <th>Type</th>
                        <th>Period / Validity</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th>Email Sent</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $inv)
                        <tr>
                            <td class="font-mono font-bold text-xs">
                                <a href="{{ route('admin.invoices.show', $inv) }}" class="link link-primary">{{ $inv->invoice_number }}</a>
                                <div class="text-[0.68rem] opacity-60 font-normal">{{ $inv->issue_date?->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div class="font-semibold text-xs">{{ $inv->client_name }}</div>
                                <div class="text-[0.7rem] opacity-60">{{ $inv->client_email }}</div>
                            </td>
                            <td>
                                <span class="badge badge-xs badge-outline">{{ $inv->typeLabel() }}</span>
                                @if ($inv->meta_billing_mode)
                                    <div class="text-[0.65rem] opacity-60 mt-0.5">
                                        {{ $inv->meta_billing_mode === 'company_card' ? 'Company Meta Card' : 'Client Meta Card' }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-xs">
                                @if ($inv->billing_month)
                                    <span class="font-mono">{{ $inv->billing_month }}</span>
                                @elseif ($inv->valid_from || $inv->valid_until)
                                    {{ $inv->valid_from?->format('d M Y') }} → {{ $inv->valid_until?->format('d M Y') ?? 'Lifetime' }}
                                @else
                                    <span class="opacity-50">—</span>
                                @endif
                            </td>
                            <td class="text-right font-bold">₹{{ number_format((float) $inv->total_amount, 2) }}</td>
                            <td>
                                @if ($inv->isPaid())
                                    <span class="badge badge-success badge-xs">Paid</span>
                                @else
                                    <span class="badge badge-warning badge-xs">Unpaid</span>
                                @endif
                            </td>
                            <td class="text-xs">
                                @if ($inv->sent_at)
                                    <span class="text-success" title="Sent to {{ $inv->sent_to_email }}">
                                        <i class="ti ti-mail-check"></i> {{ $inv->sent_at->timezone('Asia/Kolkata')->format('d M') }}
                                    </span>
                                @else
                                    <span class="opacity-40">Not sent</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.invoices.show', $inv) }}" class="btn btn-xs btn-ghost" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.invoices.pdf', $inv) }}" class="btn btn-xs btn-ghost text-primary" title="Download PDF">
                                        <i class="ti ti-file-type-pdf"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.invoices.send', $inv) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-ghost text-info" title="Send to {{ $inv->client_email }}">
                                            <i class="ti ti-send"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 opacity-60 text-sm">No invoices generated yet. Click "Generate Invoice" to create your first bill.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $invoices->links() }}</div>
    </div>
</x-app-layout>
