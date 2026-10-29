<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\SubscriptionRequest;
use App\Models\User;

it('renders the subscription page and allows submitting a UTR payment reference', function () {
    $org = makeOrganization(['name' => 'Acme Corp']);
    $user = makeMember($org, 'org_admin');

    $res = $this->actingAs($user)->get(route('subscription.index'));
    $res->assertOk()
        ->assertSee('Acme Corp')
        ->assertSee('Available Subscription Plans')
        ->assertSee('Scan &amp; Pay via UPI', false);

    // Submit UTR payment proof
    $postRes = $this->actingAs($user)->post(route('subscription.store'), [
        'plan' => 'lifetime',
        'amount' => 20000,
        'payment_method' => 'upi',
        'utr_number' => '430129849201',
        'notes' => 'Paid via PhonePe from Acme Technologies account',
    ]);

    $postRes->assertRedirect();

    $request = SubscriptionRequest::where('utr_number', '430129849201')->first();
    expect($request)->not->toBeNull()
        ->and($request->organization_id)->toBe($org->getKey())
        ->and($request->user_id)->toBe($user->getKey())
        ->and($request->plan)->toBe('lifetime')
        ->and($request->amount)->toBe(20000)
        ->and($request->isPending())->toBeTrue();
});

it('blocks write actions and displays warning banner when subscription is expired', function () {
    $org = makeOrganization(['name' => 'Expired Client']);
    $org->forceFill([
        'is_lifetime' => false,
        'subscription_expires_at' => now()->subDay(),
    ])->save();

    $user = makeMember($org, 'org_admin');

    // 1. Can still log in and view dashboard, but sees subscription expired banner
    $res = $this->actingAs($user)->get('/dashboard');
    $res->assertOk()
        ->assertSee('Subscription Has Ended')
        ->assertSee('Your portal is currently in read-only mode');

    // 2. Outbound action (test send) is blocked and redirected to subscription page
    $postRes = $this->actingAs($user)->post(route('whatsapp.test-send.store'), [
        'to_phone' => '919876543210',
        'text' => 'Hello test',
    ]);

    $postRes->assertRedirect(route('subscription.index'));

    // 3. Super admin is never blocked even if the active organization subscription is expired
    $super = User::factory()->superAdmin()->create();
    $this->actingAs($super)->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Subscription Has Ended');
});

