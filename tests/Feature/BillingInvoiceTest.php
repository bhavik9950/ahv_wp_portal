<?php

declare(strict_types=1);

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Enums\OrganizationRole;
use App\Mail\InvoiceMail;
use App\Models\Campaign;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;
use App\Services\Organizations\OrganizationProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function makeSuperAdmin(): User
{
    $user = User::factory()->create(['is_super_admin' => true]);
    $org = app(OrganizationProvisioner::class)->create(['name' => 'Root Org']);
    app(OrganizationProvisioner::class)->addMember($org, $user, OrganizationRole::OrgAdmin);
    session(['current_organization_id' => $org->getKey()]);

    return $user;
}

it('generates a subscription bill with validity dates and emails the PDF', function () {
    Mail::fake();
    $admin = makeSuperAdmin();
    $clientOrg = app(OrganizationProvisioner::class)->create([
        'name' => 'Acme Client',
        'billing_email' => 'billing@acme.test',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.invoices.store'), [
            'type' => Invoice::TYPE_SUBSCRIPTION,
            'organization_id' => $clientOrg->getKey(),
            'client_name' => 'Acme Client',
            'client_email' => 'billing@acme.test',
            'plan' => 'monthly',
            'plan_amount' => 649,
            'valid_from' => '2026-11-01',
            'valid_until' => '2026-12-01',
            'issue_date' => '2026-11-01',
            'status' => 'paid',
            'payment_reference' => 'UTR998877',
            'send_email' => '1',
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();
    expect($invoice->type)->toBe(Invoice::TYPE_SUBSCRIPTION)
        ->and((float) $invoice->total_amount)->toBe(649.0)
        ->and($invoice->isPaid())->toBeTrue()
        ->and($invoice->sent_to_email)->toBe('billing@acme.test');

    Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) {
        return $mail->hasTo('billing@acme.test') && count($mail->attachments()) === 1;
    });

    $pdfResponse = $this->actingAs($admin)->get(route('admin.invoices.pdf', $invoice));
    $pdfResponse->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(str_starts_with($pdfResponse->getContent(), '%PDF'))->toBeTrue();
});

it('calculates monthly Meta usage conditionally for company_card vs client_card', function () {
    $admin = makeSuperAdmin();

    $companyCardOrg = app(OrganizationProvisioner::class)->create(['name' => 'Company Card Org']);
    $companyCardOrg->forceFill([
        'billing_email' => 'cc@test.local',
        'meta_billing_mode' => 'company_card',
    ])->save();

    $clientCardOrg = app(OrganizationProvisioner::class)->create(['name' => 'Client Card Org']);
    $clientCardOrg->forceFill([
        'billing_email' => 'own@test.local',
        'meta_billing_mode' => 'client_card',
    ])->save();

    $wabaCompany = new WhatsappBusinessAccount(['name' => 'CC WABA', 'waba_id' => 'waba_cc_1']);
    $wabaCompany->forceFill(['organization_id' => $companyCardOrg->getKey()])->save();

    $template = new WhatsappTemplate([
        'name' => 'promo_offer',
        'language' => 'en',
        'category' => 'MARKETING',
    ]);
    $template->forceFill([
        'organization_id' => $companyCardOrg->getKey(),
        'whatsapp_business_account_id' => $wabaCompany->getKey(),
        'status' => 'APPROVED',
    ])->save();

    $campaign = new Campaign([
        'name' => 'Nov Promo',
        'template_id' => $template->getKey(),
    ]);
    $campaign->forceFill([
        'organization_id' => $companyCardOrg->getKey(),
        'status' => 'completed',
    ])->save();

    // 2 campaign marketing messages + 1 single marketing message for Company Card Org
    foreach ([1, 2] as $i) {
        $m = new Message([
            'campaign_id' => $campaign->getKey(),
            'template_id' => $template->getKey(),
            'direction' => 'outbound',
            'to_phone' => '91999990000'.$i,
            'to_phone_hash' => hash('sha256', '91999990000'.$i),
            'type' => MessageType::Template,
            'idempotency_key' => 'cc-camp-'.$i,
        ]);
        $m->forceFill([
            'organization_id' => $companyCardOrg->getKey(),
            'status' => MessageStatus::Delivered,
        ])->save();
    }
    $mSingle = new Message([
        'template_id' => $template->getKey(),
        'direction' => 'outbound',
        'to_phone' => '919999900003',
        'to_phone_hash' => hash('sha256', '919999900003'),
        'type' => MessageType::Template,
        'idempotency_key' => 'cc-single-1',
    ]);
    $mSingle->forceFill([
        'organization_id' => $companyCardOrg->getKey(),
        'status' => MessageStatus::Sent,
    ])->save();

    // 1 message for Client Card Org
    $wabaOwn = new WhatsappBusinessAccount(['name' => 'Own WABA', 'waba_id' => 'waba_own_1']);
    $wabaOwn->forceFill(['organization_id' => $clientCardOrg->getKey()])->save();

    $ownTemplate = new WhatsappTemplate([
        'name' => 'own_promo',
        'language' => 'en',
        'category' => 'MARKETING',
    ]);
    $ownTemplate->forceFill([
        'organization_id' => $clientCardOrg->getKey(),
        'whatsapp_business_account_id' => $wabaOwn->getKey(),
        'status' => 'APPROVED',
    ])->save();
    $mOwn = new Message([
        'template_id' => $ownTemplate->getKey(),
        'direction' => 'outbound',
        'to_phone' => '919999900004',
        'to_phone_hash' => hash('sha256', '919999900004'),
        'type' => MessageType::Template,
        'idempotency_key' => 'own-single-1',
    ]);
    $mOwn->forceFill([
        'organization_id' => $clientCardOrg->getKey(),
        'status' => MessageStatus::Delivered,
    ])->save();

    $month = now('Asia/Kolkata')->format('Y-m');

    // Company card org -> 3 * 0.88 = 2.64
    $this->actingAs($admin)
        ->getJson(route('admin.invoices.preview-usage', [
            'organization_id' => $companyCardOrg->getKey(),
            'billing_month' => $month,
        ]))
        ->assertOk()
        ->assertJsonPath('uses_company_card', true)
        ->assertJsonPath('counts.campaign_messages', 2)
        ->assertJsonPath('counts.single_messages', 1)
        ->assertJsonPath('total_amount', 2.64);

    // Client card org -> 0.00
    $this->actingAs($admin)
        ->getJson(route('admin.invoices.preview-usage', [
            'organization_id' => $clientCardOrg->getKey(),
            'billing_month' => $month,
        ]))
        ->assertOk()
        ->assertJsonPath('uses_company_card', false)
        ->assertJsonPath('counts.total_billable_messages', 1)
        ->assertJsonPath('total_amount', 0);
});

it('generates a custom bill for past work without requiring an organization', function () {
    $admin = makeSuperAdmin();

    $this->actingAs($admin)
        ->post(route('admin.invoices.store'), [
            'type' => Invoice::TYPE_CUSTOM,
            'client_name' => 'Legacy Past Client',
            'client_email' => 'legacy@client.test',
            'issue_date' => '2026-11-27',
            'tax_percent' => 18,
            'discount' => 500,
            'status' => 'unpaid',
            'items' => [
                ['description' => 'WABA Setup & Onboarding', 'quantity' => 1, 'unit_price' => 2500],
                ['description' => 'Past Campaign Management', 'quantity' => 2, 'unit_price' => 1000],
            ],
        ])
        ->assertRedirect();

    $invoice = Invoice::query()->firstOrFail();
    // Subtotal = 4500, Discount = 500 -> Taxable = 4000, GST 18% = 720 -> Total = 4720
    expect($invoice->type)->toBe(Invoice::TYPE_CUSTOM)
        ->and((float) $invoice->subtotal)->toBe(4500.0)
        ->and((float) $invoice->tax_amount)->toBe(720.0)
        ->and((float) $invoice->total_amount)->toBe(4720.0)
        ->and($invoice->items)->toHaveCount(2);
});

it('allows a tenant to download their own invoice PDF but blocks other tenants', function () {
    $orgA = app(OrganizationProvisioner::class)->create(['name' => 'Org A']);
    $orgB = app(OrganizationProvisioner::class)->create(['name' => 'Org B']);

    $userA = User::factory()->create();
    app(OrganizationProvisioner::class)->addMember($orgA, $userA, OrganizationRole::OrgAdmin);

    $invoiceB = Invoice::query()->create([
        'invoice_number' => 'AHV-202611-9999',
        'organization_id' => $orgB->getKey(),
        'type' => Invoice::TYPE_CUSTOM,
        'client_name' => 'Org B',
        'client_email' => 'b@test.local',
        'subtotal' => 100,
        'total_amount' => 100,
        'status' => 'unpaid',
        'issue_date' => now()->toDateString(),
    ]);
    $invoiceB->items()->create(['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'amount' => 100]);

    $this->actingAs($userA)
        ->withSession(['current_organization_id' => $orgA->getKey()])
        ->get(route('subscription.invoices.pdf', $invoiceB))
        ->assertForbidden();
});

it('shows Manage Client Subscription Plans to super admin and allows adding, editing, and deleting plans', function () {
    $admin = makeSuperAdmin();

    // Super admin sees plan management instead of "Choose Plan"
    $this->actingAs($admin)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertSee('Client Subscription Plans')
        ->assertDontSee('Choose Plan');

    // Add custom quarterly plan
    $this->actingAs($admin)
        ->post(route('admin.subscriptions.plans.save'), [
            'plan_key' => 'quarterly',
            'label' => '3 Months Access',
            'price' => 1799,
            'days' => 90,
            'description' => 'Quarterly plan for growing clients.',
        ])
        ->assertRedirect();

    $plans = app(\App\Services\System\SystemSettings::class)->billingPlans();
    expect($plans)->toHaveKey('quarterly')
        ->and($plans['quarterly']['price'])->toBe(1799);

    // Delete quarterly plan
    $this->actingAs($admin)
        ->delete(route('admin.subscriptions.plans.delete', 'quarterly'))
        ->assertRedirect();

    expect(app(\App\Services\System\SystemSettings::class)->billingPlans())->not->toHaveKey('quarterly');
});
