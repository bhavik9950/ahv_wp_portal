<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Organization;
use App\Services\System\SystemSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

final class InvoiceService
{
    public function __construct(
        private readonly SystemSettings $settings,
        private readonly MetaUsageCalculator $usageCalculator,
    ) {}

    /**
     * Create an invoice with line items and computed totals.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{description: string, quantity: float|int, unit_price: float|int, meta?: array<string, mixed>}>  $items
     */
    public function create(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items): Invoice {
            $subtotal = 0.0;
            $preparedItems = [];

            foreach ($items as $item) {
                $qty = round((float) ($item['quantity'] ?? 1), 2);
                $unit = round((float) ($item['unit_price'] ?? 0), 2);
                $amount = round($qty * $unit, 2);
                $subtotal += $amount;

                $preparedItems[] = [
                    'description' => (string) $item['description'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'amount' => $amount,
                    'meta' => $item['meta'] ?? null,
                ];
            }

            $discount = round((float) ($data['discount'] ?? 0), 2);
            $taxable = max(0.0, $subtotal - $discount);
            $taxPercent = round((float) ($data['tax_percent'] ?? 0), 2);
            $taxAmount = round($taxable * ($taxPercent / 100), 2);
            $total = round($taxable + $taxAmount, 2);

            $status = (string) ($data['status'] ?? Invoice::STATUS_UNPAID);

            /** @var Invoice $invoice */
            $invoice = Invoice::query()->create([
                'invoice_number' => Invoice::nextInvoiceNumber(),
                'organization_id' => $data['organization_id'] ?? null,
                'type' => (string) $data['type'],
                'client_name' => (string) $data['client_name'],
                'client_email' => (string) $data['client_email'],
                'client_address' => $data['client_address'] ?? null,
                'client_gst' => $data['client_gst'] ?? null,
                'meta_billing_mode' => $data['meta_billing_mode'] ?? null,
                'billing_month' => $data['billing_month'] ?? null,
                'plan' => $data['plan'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'subtotal' => $subtotal,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'discount' => $discount,
                'total_amount' => $total,
                'currency' => (string) ($data['currency'] ?? 'INR'),
                'status' => $status,
                'payment_reference' => $data['payment_reference'] ?? null,
                'issue_date' => $data['issue_date'] ?? now('Asia/Kolkata')->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'paid_at' => $status === Invoice::STATUS_PAID ? now() : null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($preparedItems as $item) {
                $invoice->items()->create($item);
            }

            return $invoice->load(['items', 'organization']);
        });
    }

    public function renderPdfBytes(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'organization']);

        $logoPath = public_path('images/logo-full.png');
        $logoBase64 = null;
        if (is_file($logoPath)) {
            $logoBase64 = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => $this->settings->companyProfile(),
            'upiId' => $this->settings->billingUpiId(),
            'bankDetails' => $this->settings->billingBankDetails(),
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    public function sendEmail(Invoice $invoice, ?string $overrideEmail = null): void
    {
        $recipient = trim((string) ($overrideEmail ?: $invoice->client_email));
        $pdfBytes = $this->renderPdfBytes($invoice);

        Mail::to($recipient)->send(new InvoiceMail(
            invoice: $invoice,
            company: $this->settings->companyProfile(),
            pdfBytes: $pdfBytes,
        ));

        $invoice->forceFill([
            'sent_at' => now(),
            'sent_to_email' => $recipient,
        ])->save();
    }

    public function resolveClientDetails(?Organization $org, ?string $name = null, ?string $email = null): array
    {
        return [
            'name' => trim((string) ($name ?: ($org?->name ?? ''))),
            'email' => trim((string) ($email ?: ($org?->resolveBillingEmail() ?? ''))),
            'address' => $org?->billing_address,
            'gst' => $org?->gst_number,
        ];
    }

    /**
     * Auto-generate monthly Meta usage invoices for all eligible organizations for a given YYYY-MM month.
     *
     * @return array{
     *     generated: array<int, Invoice>,
     *     skipped_existing: int,
     *     skipped_zero_usage: int,
     *     skipped_client_card: int,
     *     skipped_no_email: int,
     *     email_errors: int
     * }
     */
    public function autoGenerateMonthlyBills(
        string $billingMonth,
        bool $onlyCompanyCard = true,
        bool $sendEmail = false,
        ?int $createdBy = null,
    ): array {
        $organizations = Organization::query()
            ->withoutGlobalScopes()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $generated = [];
        $skippedExisting = 0;
        $skippedZeroUsage = 0;
        $skippedClientCard = 0;
        $skippedNoEmail = 0;
        $emailErrors = 0;

        $issueDate = now('Asia/Kolkata')->toDateString();
        $dueDate = Carbon::now('Asia/Kolkata')->addDays(7)->toDateString();

        foreach ($organizations as $org) {
            if ($onlyCompanyCard && ! $org->usesCompanyMetaCard()) {
                $skippedClientCard++;
                continue;
            }

            $alreadyExists = Invoice::query()
                ->where('organization_id', $org->getKey())
                ->where('type', Invoice::TYPE_MONTHLY_USAGE)
                ->where('billing_month', $billingMonth)
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->exists();

            if ($alreadyExists) {
                $skippedExisting++;
                continue;
            }

            $usage = $this->usageCalculator->calculateForMonth($org, $billingMonth);
            if ($usage['counts']['total_billable_messages'] === 0) {
                $skippedZeroUsage++;
                continue;
            }

            $email = $org->resolveBillingEmail();
            if (blank($email)) {
                $skippedNoEmail++;
                continue;
            }

            $invoice = $this->create([
                'organization_id' => $org->getKey(),
                'type' => Invoice::TYPE_MONTHLY_USAGE,
                'client_name' => $org->name,
                'client_email' => $email,
                'client_address' => $org->billing_address,
                'client_gst' => $org->gst_number,
                'meta_billing_mode' => $usage['meta_billing_mode'],
                'billing_month' => $billingMonth,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => Invoice::STATUS_UNPAID,
                'metadata' => [
                    'usage_counts' => $usage['counts'],
                    'rates' => $usage['rates'],
                    'auto_generated' => true,
                ],
                'created_by' => $createdBy,
            ], $usage['items']);

            if ($sendEmail) {
                try {
                    $this->sendEmail($invoice);
                } catch (\Throwable) {
                    $emailErrors++;
                }
            }

            $generated[] = $invoice;
        }

        return [
            'generated' => $generated,
            'skipped_existing' => $skippedExisting,
            'skipped_zero_usage' => $skippedZeroUsage,
            'skipped_client_card' => $skippedClientCard,
            'skipped_no_email' => $skippedNoEmail,
            'email_errors' => $emailErrors,
        ];
    }
}
