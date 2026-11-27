<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; background-color: #f3f4f6; margin: 0; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background-color: #0f172a; color: #ffffff; padding: 20px 24px;">
            <h2 style="margin: 0; font-size: 18px;">{{ $company['name'] ?? 'AH&V Software Technologies' }}</h2>
            <p style="margin: 4px 0 0; font-size: 12px; color: #cbd5e1;">
                {{ $invoice->isPaid() ? 'Payment Receipt' : 'Invoice' }} · {{ $invoice->invoice_number }}
            </p>
        </div>

        <div style="padding: 24px; font-size: 14px; line-height: 1.6;">
            <p style="margin-top: 0;">Hello <strong>{{ $invoice->client_name }}</strong>,</p>

            <p>
                Please find attached your
                <strong>{{ $invoice->isPaid() ? 'payment receipt' : 'invoice' }}</strong>
                for <strong>{{ $invoice->typeLabel() }}</strong>.
            </p>

            <table style="width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 13px; background-color: #f9fafb; border-radius: 6px;">
                <tr>
                    <td style="padding: 8px 12px; color: #6b7280;">Invoice Number</td>
                    <td style="padding: 8px 12px; font-weight: bold; text-align: right;">{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 12px; color: #6b7280;">Issue Date</td>
                    <td style="padding: 8px 12px; text-align: right;">{{ $invoice->issue_date?->format('d M Y') }}</td>
                </tr>
                @if ($invoice->valid_from || $invoice->valid_until)
                    <tr>
                        <td style="padding: 8px 12px; color: #6b7280;">Validity Period</td>
                        <td style="padding: 8px 12px; text-align: right;">
                            {{ $invoice->valid_from?->format('d M Y') ?? '—' }} to {{ $invoice->valid_until?->format('d M Y') ?? 'Lifetime' }}
                        </td>
                    </tr>
                @endif
                @if ($invoice->billing_month)
                    <tr>
                        <td style="padding: 8px 12px; color: #6b7280;">Billing Month</td>
                        <td style="padding: 8px 12px; text-align: right;">{{ $invoice->billing_month }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding: 8px 12px; color: #6b7280;">Status</td>
                    <td style="padding: 8px 12px; text-align: right; font-weight: bold; color: {{ $invoice->isPaid() ? '#059669' : '#d97706' }};">
                        {{ strtoupper($invoice->status) }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 12px; font-weight: bold; border-top: 1px solid #e5e7eb;">Total Amount</td>
                    <td style="padding: 10px 12px; font-weight: bold; font-size: 16px; text-align: right; border-top: 1px solid #e5e7eb;">
                        ₹{{ number_format((float) $invoice->total_amount, 2) }}
                    </td>
                </tr>
            </table>

            @if ($invoice->notes)
                <p style="font-size: 13px; color: #4b5563; background: #f9fafb; padding: 10px 12px; border-left: 3px solid #4f46e5;">
                    {{ $invoice->notes }}
                </p>
            @endif

            <p style="font-size: 12px; color: #6b7280; margin-bottom: 0;">
                The complete PDF invoice is attached to this email. For any billing questions, reply to
                {{ $company['email'] ?? config('mail.from.address') }}.
            </p>
        </div>
    </div>
</body>
</html>
