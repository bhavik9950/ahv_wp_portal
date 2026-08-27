<?php

declare(strict_types=1);

use App\Models\Message;
use App\Models\MessageStatusEvent;
use App\Models\User;

it('lists messages for a viewer and links to detail', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');
    $message = Message::factory()->for($org)->sent()->create(['to_phone' => '919876500999']);

    $this->actingAs($viewer)->get(route('whatsapp.messages.index'))
        ->assertOk()
        ->assertSee('919876500999')
        ->assertSee(route('whatsapp.messages.show', $message), false);
});

it('shows a message with its status timeline', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');
    $message = Message::factory()->for($org)->sent()->create();

    foreach (['sent', 'delivered', 'read'] as $i => $status) {
        MessageStatusEvent::create([
            'organization_id' => $org->getKey(),
            'message_id' => $message->getKey(),
            'status' => $status,
            'occurred_at' => now()->addSeconds($i),
        ]);
    }

    $this->actingAs($viewer)->get(route('whatsapp.messages.show', $message))
        ->assertOk()
        ->assertSee('Status timeline')
        ->assertSee('Delivered')
        ->assertSee('Read');
});

it('404s on another organization message', function () {
    $orgA = makeOrganization();
    $message = Message::factory()->for($orgA)->create();

    $orgB = makeOrganization();
    $viewer = makeMember($orgB, 'viewer');

    $this->actingAs($viewer)->get(route('whatsapp.messages.show', $message))->assertNotFound();
});

it('forbids an organization member with no role from viewing messages', function () {
    $org = makeOrganization();
    $user = User::factory()->create();
    $org->users()->attach($user); // member, but no role assigned

    $this->actingAs($user)->get(route('whatsapp.messages.index'))->assertForbidden();
});
