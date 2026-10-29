<?php

declare(strict_types=1);

use App\Models\User;

it('renders the public landing page for guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('AHV WhatsApp Portal')
        ->assertSee('Official Meta Cloud API');
});

it('redirects authenticated users from the root path to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard', absolute: false));
});

