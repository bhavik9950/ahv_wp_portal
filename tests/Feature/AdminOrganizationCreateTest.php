<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

it('allows super admin to create a new client organization via web UI', function () {
    $superAdmin = User::factory()->create([
        'is_super_admin' => true,
    ]);

    $response = $this->actingAs($superAdmin)->post(route('admin.organizations.store'), [
        'name' => 'Test Client Corp',
        'admin_name' => 'Client Owner',
        'admin_email' => 'client.owner@example.com',
        'password' => 'Secret1234',
        'plan' => 'yearly',
        'waba_id' => '123456789012345',
    ]);

    $response->assertRedirect(route('admin.organizations.index'));
    $response->assertSessionHas('created_credentials');

    $org = Organization::withoutGlobalScopes()->where('name', 'Test Client Corp')->first();
    expect($org)->not->toBeNull()
        ->and($org->plan)->toBe('yearly')
        ->and($org->subscription_expires_at)->not->toBeNull();

    $user = User::where('email', 'client.owner@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Client Owner');

    expect($org->users()->where('user_id', $user->id)->exists())->toBeTrue();
});
