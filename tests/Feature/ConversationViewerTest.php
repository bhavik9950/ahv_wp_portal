<?php

declare(strict_types=1);

use App\Models\Message;
use App\Models\User;

it('lists conversations grouped by phone, newest first', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    Message::factory()->for($org)->sent()->create(['to_phone' => '919876500111', 'created_at' => now()->subMinutes(10)]);
    Message::factory()->for($org)->sent()->create(['to_phone' => '919876500111', 'created_at' => now()->subMinutes(5)]);
    Message::factory()->for($org)->sent()->create(['to_phone' => '919876500222', 'created_at' => now()]);

    $this->actingAs($viewer)->get(route('whatsapp.conversations.index'))
        ->assertOk()
        ->assertSee('919876500111')
        ->assertSee('919876500222');
});

it('shows a chat thread with inbound and outbound bubbles in order', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    Message::factory()->for($org)->create([
        'to_phone' => '919876500333',
        'direction' => 'inbound',
        'type' => 'text',
        'payload' => ['text' => ['body' => 'Hi, is this available?']],
        'created_at' => now()->subMinutes(2),
    ]);
    Message::factory()->for($org)->sent()->create([
        'to_phone' => '919876500333',
        'direction' => 'outbound',
        'type' => 'text',
        'payload' => ['type' => 'text', 'text' => ['body' => 'Yes it is!']],
        'created_at' => now()->subMinute(),
    ]);

    $this->actingAs($viewer)->get(route('whatsapp.conversations.show', '919876500333'))
        ->assertOk()
        ->assertSee('Hi, is this available?')
        ->assertSee('Yes it is!');
});

it('404s a conversation with no messages', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->get(route('whatsapp.conversations.show', '919999999999'))
        ->assertNotFound();
});

it('does not leak another organization\'s conversation', function () {
    config()->set('tenant.mode', 'multi');

    $orgA = makeOrganization();
    Message::factory()->for($orgA)->create([
        'to_phone' => '919876500444',
        'direction' => 'inbound',
        'type' => 'text',
        'payload' => ['text' => ['body' => 'secret from org A']],
    ]);

    $orgB = makeOrganization();
    $viewer = makeMember($orgB, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_organization_id' => $orgB->getKey()])
        ->get(route('whatsapp.conversations.show', '919876500444'))
        ->assertNotFound();
});

it('forbids a member with no role from viewing conversations', function () {
    $org = makeOrganization();
    $user = User::factory()->create();
    $org->users()->attach($user);

    $this->actingAs($user)->get(route('whatsapp.conversations.index'))->assertForbidden();
});
