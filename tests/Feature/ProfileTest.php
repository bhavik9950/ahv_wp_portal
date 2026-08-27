<?php

declare(strict_types=1);

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch('/profile', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/profile');

    $user->refresh();

    expect($user->name)->toBe('Test User')
        ->and($user->email)->toBe('test@example.com');
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

    $response->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});
