<?php

declare(strict_types=1);

use App\Enums\BotConversationMode;
use App\Enums\MessageStatus;
use App\Jobs\HandleInboundMessageJob;
use App\Models\BotConversation;
use App\Models\BotSetting;
use App\Models\Message;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Services\Bot\BotService;
use App\Services\Bot\Contracts\LlmClient;
use App\Services\Bot\Llm\FakeLlmClient;
use App\Services\Bot\PromptBuilder;
use App\Services\WhatsApp\OutboundMessageService;

const BOT_SECRET = 'bot-test-app-secret-1234';

function botWaba(): WhatsappPhoneNumber
{
    $org = makeOrganization();
    $account = WhatsappBusinessAccount::factory()->for($org)->create([
        'app_secret' => BOT_SECRET,
        'webhook_verify_token' => 'verify-me',
    ]);

    return WhatsappPhoneNumber::factory()->forAccount($account)->create(['phone_number_id' => '999000111']);
}

function enableBot(int $orgId, array $overrides = []): BotSetting
{
    config()->set('services.bot.enabled', true);

    return BotSetting::query()->withoutGlobalScopes()->forceCreate(array_merge([
        'organization_id' => $orgId,
        'enabled' => true,
        'system_prompt' => BotSetting::DEFAULT_SYSTEM_PROMPT,
        'daily_reply_cap' => 40,
        'handoff_keywords' => BotSetting::DEFAULT_HANDOFF_KEYWORDS,
        'fallback_message' => BotSetting::DEFAULT_FALLBACK,
    ], $overrides));
}

function inbound(WhatsappPhoneNumber $number, string $body, string $from = '919812300000'): Message
{
    return (new Message)->forceFill([
        'organization_id' => $number->organization_id,
        'whatsapp_phone_number_id' => $number->getKey(),
        'wamid' => 'wamid.'.uniqid(),
        'direction' => 'inbound',
        'to_phone' => $from,
        'to_phone_hash' => hash('sha256', $from),
        'type' => 'text',
        'payload' => ['text' => ['body' => $body], 'type' => 'text'],
        'idempotency_key' => 'inbound:'.uniqid(),
        'status' => MessageStatus::Delivered->value,
    ]);
}

afterEach(fn () => FakeLlmClient::$nextReply = null);

it('auto-replies to an inbound message and records the transcript', function () {
    $number = botWaba();
    enableBot($number->organization_id);
    FakeLlmClient::$nextReply = 'Sure — we build Android apps. Shall we set up a call?';

    $message = inbound($number, 'Do you make mobile apps?');
    $message->save();

    (new HandleInboundMessageJob($message->getKey()))->handle(
        app(BotService::class),
        app(PromptBuilder::class),
        app(LlmClient::class),
        app(OutboundMessageService::class),
    );

    $conversation = BotConversation::withoutGlobalScopes()->sole();
    expect($conversation->messages()->count())->toBe(2)
        ->and($conversation->messages()->where('role', 'assistant')->value('content'))->toContain('Android apps')
        ->and($conversation->replies_today)->toBe(1);

    // The reply went out as a real outbound WhatsApp message.
    $outbound = Message::withoutGlobalScopes()->where('direction', 'outbound')->sole();
    expect($outbound->to_phone)->toBe('919812300000')
        ->and(data_get($outbound->payload, 'text.body'))->toContain('Android apps');
});

it('stays silent when the assistant is disabled for the org', function () {
    $number = botWaba();
    enableBot($number->organization_id, ['enabled' => false]);

    $message = inbound($number, 'hello');
    $message->save();

    (new HandleInboundMessageJob($message->getKey()))->handle(
        app(BotService::class),
        app(PromptBuilder::class),
        app(LlmClient::class),
        app(OutboundMessageService::class),
    );

    expect(Message::withoutGlobalScopes()->where('direction', 'outbound')->count())->toBe(0)
        ->and(BotConversation::withoutGlobalScopes()->count())->toBe(0);
});

it('hands off to a human on a handoff keyword and does not reply', function () {
    $number = botWaba();
    enableBot($number->organization_id);

    $message = inbound($number, 'I want to talk to a human please');
    $message->save();

    (new HandleInboundMessageJob($message->getKey()))->handle(
        app(BotService::class),
        app(PromptBuilder::class),
        app(LlmClient::class),
        app(OutboundMessageService::class),
    );

    $conversation = BotConversation::withoutGlobalScopes()->sole();
    expect($conversation->mode)->toBe(BotConversationMode::Human)
        ->and($conversation->handoff_reason)->toContain('human')
        ->and(Message::withoutGlobalScopes()->where('direction', 'outbound')->count())->toBe(0);
});

it('does not reply while a human is handling the thread', function () {
    $number = botWaba();
    enableBot($number->organization_id);

    BotConversation::withoutGlobalScopes()->forceCreate([
        'organization_id' => $number->organization_id,
        'wa_phone' => '919812300000',
        'wa_phone_hash' => hash('sha256', '919812300000'),
        'mode' => BotConversationMode::Human->value,
    ]);

    $message = inbound($number, 'any update?');
    $message->save();

    (new HandleInboundMessageJob($message->getKey()))->handle(
        app(BotService::class),
        app(PromptBuilder::class),
        app(LlmClient::class),
        app(OutboundMessageService::class),
    );

    expect(Message::withoutGlobalScopes()->where('direction', 'outbound')->count())->toBe(0);
});

it('stops replying after the daily cap', function () {
    $number = botWaba();
    enableBot($number->organization_id, ['daily_reply_cap' => 1]);

    foreach (['first', 'second'] as $body) {
        $m = inbound($number, $body);
        $m->save();
        (new HandleInboundMessageJob($m->getKey()))->handle(
            app(BotService::class),
            app(PromptBuilder::class),
            app(LlmClient::class),
            app(OutboundMessageService::class),
        );
    }

    expect(Message::withoutGlobalScopes()->where('direction', 'outbound')->count())->toBe(1);
});

it('lets an admin edit settings but forbids a viewer', function () {
    $number = botWaba();
    $admin = makeMember($number->organization, 'org_admin');
    $viewer = makeMember($number->organization, 'viewer');

    $this->actingAs($viewer)->get(route('whatsapp.assistant.settings'))->assertForbidden();

    $this->actingAs($admin)->get(route('whatsapp.assistant.settings'))->assertOk();
    $this->actingAs($admin)->put(route('whatsapp.assistant.settings.update'), [
        'enabled' => '1',
        'system_prompt' => 'Be helpful.',
        'daily_reply_cap' => 20,
        'handoff_keywords' => 'agent, refund',
        'fallback_message' => 'A team member will follow up.',
    ])->assertRedirect();

    expect(BotSetting::withoutGlobalScopes()->sole()->handoff_keywords)->toBe(['agent', 'refund']);
});

it('lets a viewer take over and hand back a conversation', function () {
    $number = botWaba();
    $admin = makeMember($number->organization, 'org_admin');
    $conversation = BotConversation::withoutGlobalScopes()->forceCreate([
        'organization_id' => $number->organization_id,
        'wa_phone' => '919812300000',
        'wa_phone_hash' => hash('sha256', '919812300000'),
        'mode' => BotConversationMode::Bot->value,
    ]);

    $this->actingAs($admin)
        ->post(route('whatsapp.assistant.conversation.mode', $conversation), ['mode' => 'human'])
        ->assertRedirect();

    expect($conversation->fresh()->mode)->toBe(BotConversationMode::Human);
});

it('scopes conversations to the current organization', function () {
    config()->set('tenant.mode', 'multi');
    $orgA = makeOrganization();
    $conversation = BotConversation::withoutGlobalScopes()->forceCreate([
        'organization_id' => $orgA->getKey(),
        'wa_phone' => '910000000000',
        'wa_phone_hash' => hash('sha256', '910000000000'),
        'mode' => 'bot',
    ]);

    $orgB = makeOrganization();
    $viewer = makeMember($orgB, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_organization_id' => $orgB->getKey()])
        ->get(route('whatsapp.assistant.conversation', $conversation))
        ->assertNotFound();
});
