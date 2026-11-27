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

it('syncs updated plan prices and percentage discounts to the public pricing page', function () {
    $admin = makeSuperAdmin();

    $this->actingAs($admin)
        ->post(route('admin.subscriptions.plans.save'), [
            'plan_key' => 'yearly',
            'label' => '1 Year Pro',
            'price' => 5000,
            'discount_percent' => 20,
            'days' => 365,
            'description' => 'Discounted annual plan.',
        ])
        ->assertRedirect();

    $this->get(route('public.pricing'))
        ->assertOk()
        ->assertSee('1 Year Pro')
        ->assertSee('20% OFF')
        ->assertSee('4,000');
});

it('auto-generates monthly bills for a selected month and prevents duplicates', function () {
    $admin = makeSuperAdmin();

    $org = app(OrganizationProvisioner::class)->create(['name' => 'Auto Bill Org']);
    $org->forceFill([
        'billing_email' => 'autobill@test.local',
        'meta_billing_mode' => 'company_card',
    ])->save();

    $waba = new WhatsappBusinessAccount(['name' => 'Auto WABA', 'waba_id' => 'waba_auto_1']);
    $waba->forceFill(['organization_id' => $org->getKey()])->save();

    $template = new WhatsappTemplate([
        'name' => 'auto_tpl',
        'language' => 'en',
        'category' => 'UTILITY',
    ]);
    $template->forceFill([
        'organization_id' => $org->getKey(),
        'whatsapp_business_account_id' => $waba->getKey(),
        'status' => 'APPROVED',
    ])->save();

    $msg = new Message([
        'template_id' => $template->getKey(),
        'direction' => 'outbound',
        'to_phone' => '919999911111',
        'to_phone_hash' => hash('sha256', '919999911111'),
        'type' => MessageType::Template,
        'idempotency_key' => 'auto-bill-1',
    ]);
    $msg->forceFill([
        'organization_id' => $org->getKey(),
        'status' => MessageStatus::Delivered,
    ])->save();

    $month = now('Asia/Kolkata')->format('Y-m');

    // First run generates 1 invoice (1 * 0.15 = 0.15)
    $this->actingAs($admin)
        ->post(route('admin.invoices.auto-generate'), [
            'billing_month' => $month,
            'only_company_card' => '1',
        ])
        ->assertRedirect(route('admin.invoices.index'));

    expect(Invoice::query()->where('organization_id', $org->getKey())->count())->toBe(1)
        ->and((float) Invoice::query()->where('organization_id', $org->getKey())->value('total_amount'))->toBe(0.15);

    // Second run for same month skips existing (still 1 invoice)
    $this->actingAs($admin)
        ->post(route('admin.invoices.auto-generate'), [
            'billing_month' => $month,
            'only_company_card' => '1',
        ])
        ->assertRedirect(route('admin.invoices.index'));

    expect(Invoice::query()->where('organization_id', $org->getKey())->count())->toBe(1);
});

it('handles 24h inbound free window, out-of-window charges, and deleted campaign template messages', function () {
    $admin = makeSuperAdmin();

    // Set service rate = 0.20 for testing out-of-window charge
    app(\App\Services\System\SystemSettings::class)->set('billing.meta_rates', [
        'marketing' => 0.88,
        'utility' => 0.15,
        'authentication' => 0.15,
        'service' => 0.20,
    ]);

    $org = app(OrganizationProvisioner::class)->create(['name' => 'Window Test Org']);
    $org->forceFill([
        'billing_email' => 'window@test.local',
        'meta_billing_mode' => 'company_card',
    ])->save();

    $waba = new WhatsappBusinessAccount(['name' => 'Win WABA', 'waba_id' => 'waba_win_1']);
    $waba->forceFill(['organization_id' => $org->getKey()])->save();

    $template = new WhatsappTemplate([
        'name' => 'deleted_camp_tpl',
        'language' => 'en',
        'category' => 'MARKETING',
    ]);
    $template->forceFill([
        'organization_id' => $org->getKey(),
        'whatsapp_business_account_id' => $waba->getKey(),
        'status' => 'APPROVED',
    ])->save();

    $phoneHash = hash('sha256', '919999922222');

    // Customer inbound message 30 hours ago
    $inbound = new Message([
        'direction' => 'inbound',
        'to_phone' => '919999922222',
        'to_phone_hash' => $phoneHash,
        'type' => MessageType::Text,
        'idempotency_key' => 'in-1',
    ]);
    $inbound->forceFill([
        'organization_id' => $org->getKey(),
        'status' => MessageStatus::Delivered,
        'created_at' => now()->subHours(30),
    ])->save();

    // 1. Outbound free-form reply 2 hours after inbound (inside 24h window -> FREE)
    $freeReply = new Message([
        'direction' => 'outbound',
        'to_phone' => '919999922222',
        'to_phone_hash' => $phoneHash,
        'type' => MessageType::Text,
        'idempotency_key' => 'out-free-1',
    ]);
    $freeReply->forceFill([
        'organization_id' => $org->getKey(),
        'status' => MessageStatus::Delivered,
        'created_at' => now()->subHours(28),
    ])->save();

    // 2. Outbound free-form message 26 hours after inbound (outside 24h window -> ₹0.20)
    $lateReply = new Message([
        'direction' => 'outbound',
        'to_phone' => '919999922222',
        'to_phone_hash' => $phoneHash,
        'type' => MessageType::Text,
        'idempotency_key' => 'out-late-1',
    ]);
    $lateReply->forceFill([
        'organization_id' => $org->getKey(),
        'status' => MessageStatus::Sent,
        'created_at' => now()->subHours(4),
    ])->save();

    // 3. Successful template message whose campaign was deleted (campaign_id = null, template_id set -> ₹0.88)
    $deletedCampMsg = new Message([
        'campaign_id' => null,
        'template_id' => $template->getKey(),
        'direction' => 'outbound',
        'to_phone' => '919999933333',
        'to_phone_hash' => hash('sha256', '919999933333'),
        'type' => MessageType::Template,
        'idempotency_key' => 'del-camp-1',
    ]);
    $deletedCampMsg->forceFill([
        'organization_id' => $org->getKey(),
        'status' => MessageStatus::Delivered,
    ])->save();

    // 4. Failed message (should NOT be billed)
    $failedMsg = new Message([
        'template_id' => $template->getKey(),
        'direction' => 'outbound',
        'to_phone' => '919999944444',
        'to_phone_hash' => hash('sha256', '919999944444'),
        'type' => MessageType::Template,
        'idempotency_key' => 'fail-1',
    ]);
    $failedMsg->forceFill([
        'organization_id' => $org->getKey(),
        'status' => MessageStatus::Failed,
    ])->save();

    // Total = 0.20 (out-of-window) + 0.88 (deleted campaign template) = 1.08
    $this->actingAs($admin)
        ->getJson(route('admin.invoices.preview-usage', [
            'organization_id' => $org->getKey(),
        ]))
        ->assertOk()
        ->assertJsonPath('counts.free_window_replies', 1)
        ->assertJsonPath('counts.total_billable_messages', 2)
        ->assertJsonPath('total_amount', 1.08);
});
