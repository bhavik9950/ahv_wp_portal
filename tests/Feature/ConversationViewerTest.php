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

it('sends a direct reply when the 24-hour window is active', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    $waba = \App\Models\WhatsappBusinessAccount::factory()->for($org)->create();
    $phone = \App\Models\WhatsappPhoneNumber::factory()->forAccount($waba)->create(['is_default' => true]);

    Message::factory()->for($org)->create([
        'whatsapp_phone_number_id' => $phone->id,
        'to_phone' => '919876500555',
        'direction' => 'inbound',
        'type' => 'text',
        'payload' => ['text' => ['body' => 'Need pricing details']],
        'created_at' => now()->subMinutes(10),
    ]);

    $response = $this->actingAs($admin)
        ->from(route('whatsapp.conversations.show', '919876500555'))
        ->post(route('whatsapp.conversations.reply', '919876500555'), [
            'body' => 'Hello! Our plan starts at Rs 649/mo.',
        ]);

    $response->assertRedirect(route('whatsapp.conversations.show', '919876500555'));

    $reply = Message::query()
        ->where('to_phone', '919876500555')
        ->where('direction', 'outbound')
        ->first();

    expect($reply)->not->toBeNull()
        ->and($reply->bodyText())->toBe('Hello! Our plan starts at Rs 649/mo.');
});

it('rejects free-text reply if the 24-hour service window is expired', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    $waba = \App\Models\WhatsappBusinessAccount::factory()->for($org)->create();
    $phone = \App\Models\WhatsappPhoneNumber::factory()->forAccount($waba)->create(['is_default' => true]);

    Message::factory()->for($org)->create([
        'whatsapp_phone_number_id' => $phone->id,
        'to_phone' => '919876500666',
        'direction' => 'inbound',
        'type' => 'text',
        'payload' => ['text' => ['body' => 'Old message']],
        'created_at' => now()->subHours(25),
    ]);

    $response = $this->actingAs($admin)
        ->from(route('whatsapp.conversations.show', '919876500666'))
        ->post(route('whatsapp.conversations.reply', '919876500666'), [
            'body' => 'Checking in after a long time',
        ]);

    $response->assertRedirect(route('whatsapp.conversations.show', '919876500666'));

    $reply = Message::query()
        ->where('to_phone', '919876500666')
        ->where('direction', 'outbound')
        ->first();

    expect($reply)->toBeNull();
});

it('filters conversations by inbound customer replies and needs reply', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    // Thread 1: Customer sent message last (needs reply)
    Message::factory()->for($org)->create([
        'to_phone' => '919876500777',
        'direction' => 'inbound',
        'type' => 'text',
        'payload' => ['text' => ['body' => 'What is the price?']],
        'created_at' => now()->subMinutes(5),
    ]);

    // Thread 2: Only outbound broadcast sent, no customer reply
    Message::factory()->for($org)->sent()->create([
        'to_phone' => '919876500888',
        'direction' => 'outbound',
        'type' => 'text',
        'payload' => ['text' => ['body' => 'Big Sale']],
        'created_at' => now()->subMinutes(10),
    ]);

    // All chats
    $this->actingAs($viewer)->get(route('whatsapp.conversations.index'))
        ->assertOk()
        ->assertSee('919876500777')
        ->assertSee('919876500888');

    // Filter by needs reply
    $this->actingAs($viewer)->get(route('whatsapp.conversations.index', ['filter' => 'needs_reply']))
        ->assertOk()
        ->assertSee('919876500777')
        ->assertDontSee('919876500888');

    // Filter by inbound customer replied
    $this->actingAs($viewer)->get(route('whatsapp.conversations.index', ['filter' => 'inbound']))
        ->assertOk()
        ->assertSee('919876500777')
        ->assertDontSee('919876500888');
});
