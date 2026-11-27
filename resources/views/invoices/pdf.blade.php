<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 0;
            padding: 24px 28px;
            line-height: 1.45;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #0f172a; padding-bottom: 14px; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 48px; max-width: 190px; margin-bottom: 6px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f172a; }
        .muted { color: #6b7280; font-size: 10.5px; }
        .doc-title { font-size: 22px; font-weight: bold; color: #0f172a; text-align: right; letter-spacing: 0.5px; }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 6px;
        }
        .status-paid { background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .status-unpaid { background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .meta-table td { vertical-align: top; width: 50%; }
        .box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
        }
        .box-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 4px; letter-spacing: 0.4px; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 14px; }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            text-align: left;
            padding: 8px 10px;
            font-size: 10.5px;
            text-transform: uppercase;
        }
        .items-table th.right, .items-table td.right { text-align: right; }
        .items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11.5px;
        }
        .items-table tr:nth-child(even) td { background-color: #f9fafb; }

        .totals-table { width: 42%; margin-left: 58%; border-collapse: collapse; margin-bottom: 18px; }
        .totals-table td { padding: 5px 8px; font-size: 11.5px; }
        .totals-table td.label { color: #6b7280; text-align: left; }
        .totals-table td.val { text-align: right; font-weight: bold; }
        .totals-table tr.grand td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            font-size: 13.5px;
            color: #0f172a;
            padding: 8px;
            background-color: #f1f5f9;
        }

        .payment-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 12px;
            font-size: 10.5px;
        }
        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 58%;">
                @if (! empty($logoBase64))
                    <img src="{{ $logoBase64 }}" alt="Company Logo" class="logo"><br>
                @endif
                <div class="company-name">{{ $company['name'] ?? 'AH&V Software Technologies' }}</div>
                @if (! empty($company['address']))
                    <div class="muted">{{ $company['address'] }}</div>
                @endif
                <div class="muted">
                    @if (! empty($company['email'])) Email: {{ $company['email'] }} @endif
                    @if (! empty($company['phone'])) &nbsp;|&nbsp; Phone: {{ $company['phone'] }} @endif
                </div>
                @if (! empty($company['gstin']))
                    <div class="muted">GSTIN: {{ $company['gstin'] }}</div>
                @endif
            </td>
            <td style="width: 42%; text-align: right;">
                <div class="doc-title">{{ $invoice->isPaid() ? 'PAYMENT RECEIPT' : 'INVOICE' }}</div>
                <div style="font-size: 12px; font-weight: bold; margin-top: 4px;"># {{ $invoice->invoice_number }}</div>
                <div class="muted" style="margin-top: 3px;">Issue Date: {{ $invoice->issue_date?->format('d M Y') }}</div>
                @if ($invoice->due_date && ! $invoice->isPaid())
                    <div class="muted">Due Date: {{ $invoice->due_date->format('d M Y') }}</div>
                @endif
                <div>
                    <span class="status-badge {{ $invoice->isPaid() ? 'status-paid' : ($invoice->status === 'cancelled' ? 'status-cancelled' : 'status-unpaid') }}">
                        {{ strtoupper($invoice->status) }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="padding-right: 10px;">
                <div class="box">
                    <div class="box-title">Billed To</div>
                    <div style="font-weight: bold; font-size: 12.5px;">{{ $invoice->client_name }}</div>
                    <div class="muted">{{ $invoice->client_email }}</div>
                    @if ($invoice->client_address)
                        <div class="muted" style="margin-top: 2px;">{{ $invoice->client_address }}</div>
                    @endif
                    @if ($invoice->client_gst)
                        <div class="muted" style="margin-top: 2px;">GSTIN: {{ $invoice->client_gst }}</div>
                    @endif
                </div>
            </td>
            <td style="padding-left: 10px;">
                <div class="box">
                    <div class="box-title">Billing Details</div>
                    <div><strong>Type:</strong> {{ $invoice->typeLabel() }}</div>
                    @if ($invoice->plan)
                        <div><strong>Plan:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->plan)) }}</div>
                    @endif
                    @if ($invoice->valid_from || $invoice->valid_until)
                        <div>
                            <strong>Validity:</strong>
                            {{ $invoice->valid_from?->format('d M Y') ?? '—' }}
                            to
                            {{ $invoice->valid_until?->format('d M Y') ?? 'Lifetime' }}
                        </div>
                    @endif
                    @if ($invoice->billing_month)
                        <div><strong>Billing Month:</strong> {{ $invoice->billing_month }}</div>
                    @endif
                    @if ($invoice->meta_billing_mode)
                        <div>
                            <strong>Meta Card:</strong>
                            {{ $invoice->meta_billing_mode === 'company_card' ? 'Company Meta Card (Billed)' : 'Client Own Meta Card (₹0 Meta Charge)' }}
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 52%;">Description</th>
                <th class="right" style="width: 14%;">Qty</th>
                <th class="right" style="width: 17%;">Unit Price</th>
                <th class="right" style="width: 17%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="right">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">₹{{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="val">₹{{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        @if ((float) $invoice->discount > 0)
            <tr>
                <td class="label">Discount</td>
                <td class="val">- ₹{{ number_format((float) $invoice->discount, 2) }}</td>
            </tr>
        @endif
        @if ((float) $invoice->tax_percent > 0)
            <tr>
                <td class="label">GST / Tax ({{ rtrim(rtrim(number_format((float) $invoice->tax_percent, 2), '0'), '.') }}%)</td>
                <td class="val">₹{{ number_format((float) $invoice->tax_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td class="label" style="color: #0f172a; font-weight: bold;">Total ({{ $invoice->currency }})</td>
            <td class="val">₹{{ number_format((float) $invoice->total_amount, 2) }}</td>
        </tr>
    </table>

    @if ($invoice->isPaid())
        <div class="payment-box" style="background-color: #ecfdf5; border-color: #a7f3d0;">
            <strong style="color: #065f46;">Payment Received</strong>
            @if ($invoice->paid_at)
                &nbsp;·&nbsp; Date: {{ $invoice->paid_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
            @endif
            @if ($invoice->payment_reference)
                &nbsp;·&nbsp; Ref / UTR: <strong>{{ $invoice->payment_reference }}</strong>
            @endif
        </div>
    @else
        <div class="payment-box">
            <div style="font-weight: bold; margin-bottom: 4px; color: #0f172a;">Payment Instructions</div>
            <div><strong>UPI ID:</strong> {{ $upiId }}</div>
            <div>
                <strong>Bank Transfer:</strong>
                {{ $bankDetails['account_holder'] ?? '' }} ·
                {{ $bankDetails['bank_name'] ?? '' }} ·
                A/C: {{ $bankDetails['account_number'] ?? '' }} ·
                IFSC: {{ $bankDetails['ifsc'] ?? '' }}
            </div>
        </div>
    @endif

    @if ($invoice->notes)
        <div style="margin-top: 12px; font-size: 11px; color: #4b5563;">
            <strong>Notes:</strong> {{ $invoice->notes }}
        </div>
    @endif

    <div class="footer">
        This is a computer-generated {{ $invoice->isPaid() ? 'receipt' : 'invoice' }} from {{ $company['name'] ?? 'AH&V Software Technologies' }}.
        @if (! empty($company['website'])) · {{ $company['website'] }} @endif
    </div>
</body>
</html>
