<x-app-layout>
    <x-slot name="title">Invoice {{ $invoice->invoice_number }}</x-slot>

    <div class="space-y-5 max-w-5xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-ghost btn-sm btn-square">
                    <i class="ti ti-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        {{ $invoice->invoice_number }}
                        <span class="badge {{ $invoice->isPaid() ? 'badge-success' : 'badge-warning' }} badge-sm">{{ strtoupper($invoice->status) }}</span>
                    </h2>
                    <p class="text-xs opacity-60">{{ $invoice->typeLabel() }} · Issued {{ $invoice->issue_date?->format('d M Y') }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-outline btn-sm gap-1.5">
                    <i class="ti ti-download"></i> Download PDF
                </a>

                @if (! $invoice->isPaid())
                    <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="flex items-center gap-1.5">
                        @csrf
                        <input type="text" name="payment_reference" placeholder="UTR / Ref (optional)" class="input input-bordered input-xs w-36">
                        <button type="submit" class="btn btn-success btn-sm gap-1">
                            <i class="ti ti-check"></i> Mark Paid
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Send Email Card --}}
        <div class="card bg-base-100 border border-base-300 shadow-xs">
            <div class="card-body p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="text-xs">
                    <span class="font-bold">Email Invoice PDF to Client:</span>
                    @if ($invoice->sent_at)
                        <span class="text-success ml-2">
                            <i class="ti ti-mail-check"></i> Last sent to {{ $invoice->sent_to_email }} on {{ $invoice->sent_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                        </span>
                    @else
                        <span class="opacity-60 ml-2">Not emailed yet.</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.invoices.send', $invoice) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="email" name="email" value="{{ $invoice->client_email }}" required class="input input-bordered input-sm w-60 text-xs">
                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                        <i class="ti ti-send"></i> {{ $invoice->sent_at ? 'Resend Email' : 'Send Email' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- HTML Invoice Preview --}}
        <div class="card bg-white text-neutral-800 border border-base-300 shadow-md">
            <div class="card-body p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row justify-between gap-4 border-b-2 border-neutral-900 pb-4">
                    <div>
                        <img src="{{ asset('images/logo-full.png') }}" alt="Company Logo" class="h-11 mb-2">
                        <div class="font-bold text-base">{{ $company['name'] ?? 'AH&V Software Technologies' }}</div>
                        @if (! empty($company['address']))<div class="text-xs text-neutral-500">{{ $company['address'] }}</div>@endif
                        <div class="text-xs text-neutral-500">
                            {{ $company['email'] ?? '' }}
                            @if (! empty($company['phone'])) · {{ $company['phone'] }} @endif
                        </div>
                        @if (! empty($company['gstin']))<div class="text-xs text-neutral-500">GSTIN: {{ $company['gstin'] }}</div>@endif
                    </div>
                    <div class="sm:text-right">
                        <div class="text-2xl font-extrabold tracking-tight">{{ $invoice->isPaid() ? 'PAYMENT RECEIPT' : 'INVOICE' }}</div>
                        <div class="font-mono font-bold text-sm mt-1"># {{ $invoice->invoice_number }}</div>
                        <div class="text-xs text-neutral-500 mt-1">Issue Date: {{ $invoice->issue_date?->format('d M Y') }}</div>
                        @if ($invoice->due_date && ! $invoice->isPaid())
                            <div class="text-xs text-neutral-500">Due Date: {{ $invoice->due_date->format('d M Y') }}</div>
                        @endif
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="p-3.5 rounded-lg bg-neutral-50 border border-neutral-200 text-xs space-y-1">
                        <div class="text-[0.68rem] font-bold uppercase text-neutral-500">Billed To</div>
                        <div class="font-bold text-sm text-neutral-900">{{ $invoice->client_name }}</div>
                        <div class="text-neutral-600">{{ $invoice->client_email }}</div>
                        @if ($invoice->client_address)<div class="text-neutral-600">{{ $invoice->client_address }}</div>@endif
                        @if ($invoice->client_gst)<div class="text-neutral-600">GSTIN: {{ $invoice->client_gst }}</div>@endif
                    </div>

                    <div class="p-3.5 rounded-lg bg-neutral-50 border border-neutral-200 text-xs space-y-1">
                        <div class="text-[0.68rem] font-bold uppercase text-neutral-500">Billing Details</div>
                        <div><strong>Type:</strong> {{ $invoice->typeLabel() }}</div>
                        @if ($invoice->plan)<div><strong>Plan:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->plan)) }}</div>@endif
                        @if ($invoice->valid_from || $invoice->valid_until)
                            <div><strong>Validity:</strong> {{ $invoice->valid_from?->format('d M Y') }} to {{ $invoice->valid_until?->format('d M Y') ?? 'Lifetime' }}</div>
                        @endif
                        @if ($invoice->billing_month)<div><strong>Billing Month:</strong> {{ $invoice->billing_month }}</div>@endif
                        @if ($invoice->meta_billing_mode)
                            <div>
                                <strong>Meta Card:</strong>
                                {{ $invoice->meta_billing_mode === 'company_card' ? 'Company Meta Card (Billed)' : 'Client Own Meta Card (₹0 Meta Charge)' }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="bg-neutral-900 text-white">
                                <th>Description</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="text-xs">{{ $item->description }}</td>
                                    <td class="text-right text-xs">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                    <td class="text-right text-xs">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="text-right text-xs font-semibold">₹{{ number_format((float) $item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <div class="w-full sm:w-72 space-y-1.5 text-xs">
                        <div class="flex justify-between"><span class="text-neutral-500">Subtotal</span><span class="font-semibold">₹{{ number_format((float) $invoice->subtotal, 2) }}</span></div>
                        @if ((float) $invoice->discount > 0)
                            <div class="flex justify-between"><span class="text-neutral-500">Discount</span><span class="font-semibold">- ₹{{ number_format((float) $invoice->discount, 2) }}</span></div>
                        @endif
                        @if ((float) $invoice->tax_percent > 0)
                            <div class="flex justify-between"><span class="text-neutral-500">GST / Tax ({{ rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.') }}%)</span><span class="font-semibold">₹{{ number_format((float) $invoice->tax_amount, 2) }}</span></div>
                        @endif
                        <div class="flex justify-between text-base font-bold border-t-2 border-neutral-900 pt-2">
                            <span>Total ({{ $invoice->currency }})</span>
                            <span>₹{{ number_format((float) $invoice->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if ($invoice->notes)
                    <div class="text-xs text-neutral-600 bg-neutral-50 p-3 rounded border border-neutral-200">
                        <strong>Notes:</strong> {{ $invoice->notes }}
                    </div>
                @endif
            </div>
        </div>

        <div class="flex justify-end">
            <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}" onsubmit="return confirm('Delete this invoice permanently?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-xs text-error">
                    <i class="ti ti-trash"></i> Delete Invoice
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
