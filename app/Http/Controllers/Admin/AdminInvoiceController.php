<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Organization;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\MetaUsageCalculator;
use App\Services\System\SystemSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminInvoiceController extends Controller
{
    public function __construct(private readonly SystemSettings $settings) {}

    public function index(Request $request): View
    {
        $type = $request->query('type');
        $status = $request->query('status');
        $orgId = $request->query('organization_id');
        $search = trim((string) $request->query('search', ''));

        $query = Invoice::query()->with('organization')->latest('id');

        if ($type && in_array($type, [Invoice::TYPE_SUBSCRIPTION, Invoice::TYPE_MONTHLY_USAGE, Invoice::TYPE_CUSTOM], true)) {
            $query->where('type', $type);
        }
        if ($status && in_array($status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED], true)) {
            $query->where('status', $status);
        }
        if ($orgId) {
            $query->where('organization_id', (int) $orgId);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%");
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        $totals = [
            'total_billed' => (float) Invoice::query()->where('status', '!=', Invoice::STATUS_CANCELLED)->sum('total_amount'),
            'paid_amount' => (float) Invoice::query()->where('status', Invoice::STATUS_PAID)->sum('total_amount'),
            'unpaid_amount' => (float) Invoice::query()->where('status', Invoice::STATUS_UNPAID)->sum('total_amount'),
            'count' => Invoice::query()->count(),
        ];

        $organizations = Organization::query()->withoutGlobalScopes()->orderBy('name')->get(['id', 'name']);

        $monthOptions = collect(range(0, 11))->map(function (int $offset): array {
            $dt = Carbon::now('Asia/Kolkata')->startOfMonth()->subMonths($offset);

            return [
                'value' => $dt->format('Y-m'),
                'label' => $dt->format('F Y').' ('.$dt->format('Y-m').')',
            ];
        })->all();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'totals' => $totals,
            'organizations' => $organizations,
            'monthOptions' => $monthOptions,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'organization_id' => $orgId,
                'search' => $search,
            ],
        ]);
    }

    public function autoGenerateMonthly(Request $request, InvoiceService $service): RedirectResponse
    {
        $validated = $request->validate([
            'billing_month' => ['required', 'date_format:Y-m'],
            'only_company_card' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $result = $service->autoGenerateMonthlyBills(
            billingMonth: $validated['billing_month'],
            onlyCompanyCard: $request->boolean('only_company_card', true),
            sendEmail: $request->boolean('send_email', false),
            createdBy: $request->user()?->getKey(),
        );

        $count = count($result['generated']);
        $parts = ["Generated {$count} monthly bill(s) for {$validated['billing_month']}."];
        if ($result['skipped_existing'] > 0) {
            $parts[] = "Skipped {$result['skipped_existing']} already billed.";
        }
        if ($result['skipped_zero_usage'] > 0) {
            $parts[] = "Skipped {$result['skipped_zero_usage']} with 0 messages.";
        }
        if ($result['skipped_client_card'] > 0) {
            $parts[] = "Skipped {$result['skipped_client_card']} client-card orgs.";
        }
        if ($result['email_errors'] > 0) {
            $parts[] = "{$result['email_errors']} email(s) failed.";
        }

        return redirect()->route('admin.invoices.index')->with('flash_notify', [
            'type' => $count > 0 ? 'success' : 'info',
            'message' => implode(' ', $parts),
        ]);
    }

    public function create(Request $request): View
    {
        $organizations = Organization::query()
            ->withoutGlobalScopes()
            ->with(['users' => fn ($q) => $q->orderBy('organization_user.id')])
            ->orderBy('name')
            ->get()
            ->map(function (Organization $org): array {
                return [
                    'id' => $org->getKey(),
                    'name' => $org->name,
                    'email' => $org->resolveBillingEmail() ?? '',
                    'address' => $org->billing_address ?? '',
                    'gst' => $org->gst_number ?? '',
                    'plan' => $org->plan ?? 'monthly',
                    'is_lifetime' => (bool) $org->is_lifetime,
                    'expires_at' => $org->subscription_expires_at?->format('Y-m-d'),
                    'meta_billing_mode' => $org->meta_billing_mode ?? 'client_card',
                ];
            });

        return view('admin.invoices.create', [
            'organizations' => $organizations,
            'plans' => $this->settings->billingPlans(),
            'company' => $this->settings->companyProfile(),
            'metaRates' => $this->settings->metaMessageRates(),
            'defaultType' => $request->query('type', Invoice::TYPE_SUBSCRIPTION),
            'defaultMonth' => now('Asia/Kolkata')->format('Y-m'),
        ]);
    }

    public function previewUsage(Request $request, MetaUsageCalculator $calculator): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'billing_month' => ['nullable', 'date_format:Y-m'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'meta_billing_mode' => ['nullable', 'in:company_card,client_card'],
        ]);

        $org = Organization::query()->withoutGlobalScopes()->findOrFail((int) $validated['organization_id']);
        $forceCompanyCard = isset($validated['meta_billing_mode'])
            ? $validated['meta_billing_mode'] === 'company_card'
            : null;

        if (! empty($validated['billing_month'])) {
            $usage = $calculator->calculateForMonth($org, $validated['billing_month'], $forceCompanyCard);
        } else {
            $start = ! empty($validated['from_date'])
                ? Carbon::parse($validated['from_date'], 'Asia/Kolkata')->startOfDay()->utc()
                : null;
            $end = ! empty($validated['to_date'])
                ? Carbon::parse($validated['to_date'], 'Asia/Kolkata')->endOfDay()->utc()
                : null;
            $label = ($start || $end)
                ? ($validated['from_date'] ?? 'Start').' to '.($validated['to_date'] ?? 'Now')
                : 'All-Time History';

            $usage = $calculator->calculateForPeriod($org, $start, $end, null, $label, $forceCompanyCard);
        }

        return response()->json($usage);
    }

    public function store(Request $request, InvoiceService $service, MetaUsageCalculator $calculator): RedirectResponse
    {
        $type = (string) $request->input('type', Invoice::TYPE_CUSTOM);

        $baseRules = [
            'type' => ['required', 'in:subscription,monthly_usage,custom'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'client_name' => ['required', 'string', 'max:150'],
            'client_email' => ['required', 'email', 'max:150'],
            'client_address' => ['nullable', 'string', 'max:500'],
            'client_gst' => ['nullable', 'string', 'max:30'],
            'meta_billing_mode' => ['nullable', 'in:company_card,client_card'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:unpaid,paid'],
            'payment_reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'send_email' => ['nullable', 'boolean'],
        ];

        if ($type === Invoice::TYPE_SUBSCRIPTION) {
            $validated = $request->validate(array_merge($baseRules, [
                'organization_id' => ['required', 'integer', 'exists:organizations,id'],
                'plan' => ['required', 'string', 'max:50'],
                'plan_amount' => ['required', 'numeric', 'min:0'],
                'valid_from' => ['required', 'date'],
                'valid_until' => ['nullable', 'date'],
            ]));

            $plans = $this->settings->billingPlans();
            $planLabel = $plans[$validated['plan']]['label'] ?? ucfirst(str_replace('_', ' ', $validated['plan']));
            $validityText = $validated['valid_until']
                ? Carbon::parse($validated['valid_from'])->format('d M Y').' to '.Carbon::parse($validated['valid_until'])->format('d M Y')
                : Carbon::parse($validated['valid_from'])->format('d M Y').' (Lifetime)';

            $items = [[
                'description' => "Portal Subscription — {$planLabel} (Validity: {$validityText})",
                'quantity' => 1,
                'unit_price' => (float) $validated['plan_amount'],
            ]];

            $invoiceData = array_merge($validated, [
                'created_by' => $request->user()?->getKey(),
            ]);
        } elseif ($type === Invoice::TYPE_MONTHLY_USAGE) {
            $validated = $request->validate(array_merge($baseRules, [
                'organization_id' => ['required', 'integer', 'exists:organizations,id'],
                'billing_month' => ['required', 'date_format:Y-m'],
                'items' => ['nullable', 'array'],
                'items.*.description' => ['required_with:items', 'string', 'max:255'],
                'items.*.quantity' => ['required_with:items', 'numeric', 'min:0'],
                'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            ]));

            $org = Organization::query()->withoutGlobalScopes()->findOrFail((int) $validated['organization_id']);
            $forceCompanyCard = isset($validated['meta_billing_mode'])
                ? $validated['meta_billing_mode'] === 'company_card'
                : null;
            $usage = $calculator->calculateForMonth($org, $validated['billing_month'], $forceCompanyCard);

            $items = ! empty($validated['items']) ? $validated['items'] : $usage['items'];
            if (empty($items)) {
                $items = [[
                    'description' => "WhatsApp Messaging Summary — {$usage['period_label']} (0 billable outbound messages)",
                    'quantity' => 1,
                    'unit_price' => 0,
                ]];
            }

            $invoiceData = array_merge($validated, [
                'meta_billing_mode' => $usage['meta_billing_mode'],
                'metadata' => [
                    'usage_counts' => $usage['counts'],
                    'rates' => $usage['rates'],
                ],
                'created_by' => $request->user()?->getKey(),
            ]);
        } else {
            $validated = $request->validate(array_merge($baseRules, [
                'items' => ['nullable', 'array'],
                'items.*.description' => ['nullable', 'string', 'max:255'],
                'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
                'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            ]));

            $items = collect($validated['items'] ?? [])
                ->filter(fn ($item) => filled($item['description'] ?? null))
                ->values()
                ->all();

            if (empty($items) && ! empty($validated['organization_id'])) {
                $org = Organization::query()->withoutGlobalScopes()->find((int) $validated['organization_id']);
                if ($org) {
                    $forceCompanyCard = isset($validated['meta_billing_mode'])
                        ? $validated['meta_billing_mode'] === 'company_card'
                        : null;
                    $usage = $calculator->calculateForPeriod($org, null, null, null, null, $forceCompanyCard);
                    $items = $usage['items'];
                }
            }

            if (empty($items)) {
                $items = [[
                    'description' => 'WhatsApp Portal / Past Services',
                    'quantity' => 1,
                    'unit_price' => 0,
                ]];
            }

            $invoiceData = array_merge($validated, [
                'created_by' => $request->user()?->getKey(),
            ]);
        }

        $invoice = $service->create($invoiceData, $items);

        $msg = "Invoice {$invoice->invoice_number} generated successfully.";

        if ($request->boolean('send_email')) {
            try {
                $service->sendEmail($invoice);
                $msg .= " Sent to {$invoice->client_email}.";
            } catch (\Throwable $e) {
                return redirect()->route('admin.invoices.show', $invoice)->with('flash_notify', [
                    'type' => 'warning',
                    'message' => "Invoice {$invoice->invoice_number} created, but email failed: {$e->getMessage()}",
                ]);
            }
        }

        return redirect()->route('admin.invoices.show', $invoice)->with('flash_notify', [
            'type' => 'success',
            'message' => $msg,
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['items', 'organization']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'company' => $this->settings->companyProfile(),
            'upiId' => $this->settings->billingUpiId(),
            'bankDetails' => $this->settings->billingBankDetails(),
        ]);
    }

    public function pdf(Invoice $invoice, InvoiceService $service): Response
    {
        $bytes = $service->renderPdfBytes($invoice);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$invoice->invoice_number}.pdf\"",
        ]);
    }

    public function send(Invoice $invoice, Request $request, InvoiceService $service): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        try {
            $service->sendEmail($invoice, $validated['email'] ?? null);

            return back()->with('flash_notify', [
                'type' => 'success',
                'message' => "Invoice {$invoice->invoice_number} emailed to ".($invoice->sent_to_email ?? $invoice->client_email).'.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('flash_notify', [
                'type' => 'error',
                'message' => 'Failed to send email: '.$e->getMessage(),
            ]);
        }
    }

    public function markPaid(Invoice $invoice, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_reference' => ['nullable', 'string', 'max:150'],
        ]);

        $invoice->forceFill([
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
            'payment_reference' => $validated['payment_reference'] ?: $invoice->payment_reference,
        ])->save();

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => "Invoice {$invoice->invoice_number} marked as PAID.",
        ]);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $number = $invoice->invoice_number;
        $invoice->delete();

        return redirect()->route('admin.invoices.index')->with('flash_notify', [
            'type' => 'success',
            'message' => "Invoice {$number} deleted.",
        ]);
    }

    public function settings(): View
    {
        return view('admin.invoices.settings', [
            'company' => $this->settings->companyProfile(),
            'metaRates' => $this->settings->metaMessageRates(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_email' => ['required', 'email', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_gstin' => ['nullable', 'string', 'max:30'],
            'company_website' => ['nullable', 'string', 'max:150'],
            'rate_marketing' => ['required', 'numeric', 'min:0', 'max:100'],
            'rate_utility' => ['required', 'numeric', 'min:0', 'max:100'],
            'rate_authentication' => ['required', 'numeric', 'min:0', 'max:100'],
            'rate_service' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $this->settings->set('billing.company_profile', [
            'name' => $validated['company_name'],
            'email' => $validated['company_email'],
            'phone' => (string) ($validated['company_phone'] ?? ''),
            'address' => (string) ($validated['company_address'] ?? ''),
            'gstin' => (string) ($validated['company_gstin'] ?? ''),
            'website' => (string) ($validated['company_website'] ?? ''),
        ]);

        $this->settings->set('billing.meta_rates', [
            'marketing' => (float) $validated['rate_marketing'],
            'utility' => (float) $validated['rate_utility'],
            'authentication' => (float) $validated['rate_authentication'],
            'service' => (float) $validated['rate_service'],
        ]);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => 'Company billing profile & Meta message rates updated.',
        ]);
    }
}
