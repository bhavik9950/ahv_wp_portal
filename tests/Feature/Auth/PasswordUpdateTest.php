<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'Str0ng-Passw0rd-9x',
            'password_confirmation' => 'Str0ng-Passw0rd-9x',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect('/profile');

    expect(Hash::check('Str0ng-Passw0rd-9x', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'Str0ng-Passw0rd-9x',
            'password_confirmation' => 'Str0ng-Passw0rd-9x',
        ]);

    $response->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});
