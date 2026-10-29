<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Models\WhatsappBusinessAccount;
use Illuminate\Support\Facades\Hash;

it('provisions a new organization with an admin user and scoped WABA', function () {
    $this->artisan('org:create', [
        'name' => 'Acme Technologies',
        '--admin-email' => 'admin@acme.test',
        '--admin-name' => 'Acme Admin',
        '--password' => 'Secret123456!',
        '--waba-id' => 'waba_123456789',
        '--timezone' => 'Asia/Kolkata',
    ])->assertSuccessful();

    $org = Organization::where('name', 'Acme Technologies')->first();
    expect($org)->not->toBeNull()
        ->and($org->timezone)->toBe('Asia/Kolkata');

    $user = User::where('email', 'admin@acme.test')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Acme Admin')
        ->and(Hash::check('Secret123456!', $user->password))->toBeTrue()
        ->and($user->belongsToOrganization($org))->toBeTrue();

    // Verify user has OrgAdmin role in this organization
    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($org->getKey());
    expect($user->hasRole(OrganizationRole::OrgAdmin->value))->toBeTrue();

    // Verify scoped WABA placeholder was created
    $waba = WhatsappBusinessAccount::withoutGlobalScopes()->where('organization_id', $org->getKey())->first();
    expect($waba)->not->toBeNull()
        ->and($waba->name)->toBe('Acme Technologies WhatsApp')
        ->and($waba->is_active)->toBeFalse();
});

it('provisions a lifetime organization via artisan command', function () {
    $this->artisan('org:create', [
        'name' => 'Lifetime Client',
        '--admin-email' => 'lifetime@client.test',
        '--admin-name' => 'Lifetime Admin',
        '--password' => 'Secret123456!',
        '--plan' => 'lifetime',
    ])->assertSuccessful();

    $org = Organization::where('name', 'Lifetime Client')->first();
    expect($org)->not->toBeNull()
        ->and($org->is_lifetime)->toBeTrue()
        ->and($org->plan)->toBe('lifetime')
        ->and($org->hasActiveSubscription())->toBeTrue();
});
