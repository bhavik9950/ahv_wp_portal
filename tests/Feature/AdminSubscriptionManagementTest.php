<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\SubscriptionRequest;
use App\Models\User;

it('allows a super admin to view and approve a UTR subscription request', function () {
    $org = makeOrganization(['name' => 'Client Org']);
    $user = makeMember($org, 'org_admin');
    $super = User::factory()->superAdmin()->create();

    // Client submitted a lifetime subscription request with UTR
    $req = SubscriptionRequest::create([
        'organization_id' => $org->getKey(),
        'user_id' => $user->getKey(),
        'plan' => 'lifetime',
        'amount' => 20000,
        'utr_number' => 'UTR1234567890',
        'payment_method' => 'upi',
        'status' => 'pending',
    ]);

    // Super admin views the subscriptions page
    $this->actingAs($super)->get(route('admin.subscriptions.index'))
        ->assertOk()
        ->assertSee('Client Org')
        ->assertSee('UTR1234567890');

    // Super admin approves the request
    $approveRes = $this->actingAs($super)->post(route('admin.subscriptions.approve', $req));
    $approveRes->assertRedirect();

    $req->refresh();
    $org->refresh();

    expect($req->status)->toBe('approved')
        ->and($req->approved_by_user_id)->toBe($super->getKey())
        ->and($org->is_lifetime)->toBeTrue()
        ->and($org->plan)->toBe('lifetime');
});

it('allows a super admin to reject a UTR subscription request', function () {
    $org = makeOrganization(['name' => 'Client Org']);
    $user = makeMember($org, 'org_admin');
    $super = User::factory()->superAdmin()->create();

    $req = SubscriptionRequest::create([
        'organization_id' => $org->getKey(),
        'user_id' => $user->getKey(),
        'plan' => 'monthly',
        'amount' => 1999,
        'utr_number' => 'INVALID_UTR',
        'payment_method' => 'upi',
        'status' => 'pending',
    ]);

    $rejectRes = $this->actingAs($super)->post(route('admin.subscriptions.reject', $req), [
        'reason' => 'Invalid UTR, no transaction found in bank statement',
    ]);
    $rejectRes->assertRedirect();

    $req->refresh();
    expect($req->status)->toBe('rejected')
        ->and($req->notes)->toContain('Invalid UTR');
});

it('allows a super admin to update bank and UPI settings', function () {
    makeOrganization();
    $super = User::factory()->superAdmin()->create();

    $res = $this->actingAs($super)->post(route('admin.subscriptions.settings'), [
        'upi_id' => 'ahvtech@icici',
        'bank_name' => 'ICICI Bank',
        'account_holder' => 'AHV Software Tech Pvt Ltd',
        'account_number' => '123405009999',
        'ifsc' => 'ICIC0001234',
    ]);

    $res->assertRedirect();

    $settings = app(\App\Services\System\SystemSettings::class);
    expect($settings->billingUpiId())->toBe('ahvtech@icici')
        ->and($settings->billingBankDetails()['bank_name'])->toBe('ICICI Bank');
});

it('allows a super admin to edit an organization plan, set lifetime, and toggle modules', function () {
    $org = makeOrganization(['name' => 'Modular Org']);
    $super = User::factory()->superAdmin()->create();

    $res = $this->actingAs($super)->put(route('admin.organizations.update', $org), [
        'name' => 'Modular Org Updated',
        'status' => 'active',
        'plan' => 'lifetime',
        'is_lifetime' => '1',
        'allowed_modules' => ['campaigns', 'chats', 'templates'],
    ]);

    $res->assertRedirect(route('admin.organizations.index'));

    $org->refresh();
    expect($org->name)->toBe('Modular Org Updated')
        ->and($org->is_lifetime)->toBeTrue()
        ->and($org->plan)->toBe('lifetime')
        ->and($org->hasModule('campaigns'))->toBeTrue()
        ->and($org->hasModule('chats'))->toBeTrue()
        ->and($org->hasModule('voice_campaigns'))->toBeFalse();
});

it('forbids non-super admins from accessing admin subscription or organization management', function () {
    $org = makeOrganization(['name' => 'Regular Org']);
    $clientAdmin = makeMember($org, 'org_admin');

    $this->actingAs($clientAdmin)->get(route('admin.subscriptions.index'))->assertForbidden();
    $this->actingAs($clientAdmin)->get(route('admin.organizations.index'))->assertForbidden();
});
