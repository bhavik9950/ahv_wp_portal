<?php

declare(strict_types=1);

use App\Models\Organization;

it('determines active, lifetime, and expired subscription states correctly', function () {
    // 1. Default (unspecified expiry) is active
    $org = new Organization(['name' => 'Org A']);
    expect($org->hasActiveSubscription())->toBeTrue()
        ->and($org->isSubscriptionExpired())->toBeFalse();

    // 2. Lifetime subscription
    $org->is_lifetime = true;
    expect($org->hasActiveSubscription())->toBeTrue()
        ->and($org->subscriptionStatus())->toBe('lifetime');

    // 3. Expired subscription
    $org->is_lifetime = false;
    $org->subscription_expires_at = now()->subDay();
    expect($org->hasActiveSubscription())->toBeFalse()
        ->and($org->isSubscriptionExpired())->toBeTrue()
        ->and($org->subscriptionStatus())->toBe('expired')
        ->and($org->subscriptionDaysRemaining())->toBe(0);

    // 4. Future active subscription
    $org->subscription_expires_at = now()->addDays(20);
    expect($org->hasActiveSubscription())->toBeTrue()
        ->and($org->isSubscriptionExpired())->toBeFalse()
        ->and($org->subscriptionStatus())->toBe('active')
        ->and($org->subscriptionDaysRemaining())->toBeGreaterThanOrEqual(19);
});

it('correctly checks allowed module access', function () {
    $org = new Organization(['name' => 'Org A']);

    // null means all modules allowed
    expect($org->hasModule('campaigns'))->toBeTrue()
        ->and($org->hasModule('voice_campaigns'))->toBeTrue();

    // Restricted array
    $org->allowed_modules = ['campaigns', 'chats'];
    expect($org->hasModule('campaigns'))->toBeTrue()
        ->and($org->hasModule('chats'))->toBeTrue()
        ->and($org->hasModule('voice_campaigns'))->toBeFalse();
});

