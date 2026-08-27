<?php

declare(strict_types=1);

use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;

function wabaAccount(): WhatsappBusinessAccount
{
    $org = makeOrganization();

    return WhatsappBusinessAccount::factory()->for($org)->create(['access_token' => 'EAAx000000000000']);
}

it('syncs templates from the mock driver', function () {
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');

    $this->actingAs($admin)->post(route('whatsapp.templates.sync'))->assertRedirect();

    expect(WhatsappTemplate::count())->toBe(2)
        ->and(WhatsappTemplate::where('name', 'order_dispatched_update')->value('status'))->toBe('APPROVED');
});

it('submits a valid template and creates a PENDING local record', function () {
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');

    $this->actingAs($admin)->post(route('whatsapp.templates.store'), [
        'name' => 'welcome_message',
        'language' => 'en',
        'category' => 'UTILITY',
        'header_type' => 'none',
        'body' => 'Hi {{1}}, welcome to {{2}}.',
        'footer' => 'Reply STOP to opt out',
    ])->assertRedirect();

    $template = WhatsappTemplate::where('name', 'welcome_message')->sole();
    expect($template->status)->toBe('PENDING')
        ->and($template->components)->toHaveCount(2) // BODY + FOOTER
        ->and(collect($template->components)->pluck('type')->all())->toBe(['BODY', 'FOOTER']);
});

it('rejects a template whose variables are not sequential', function () {
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');

    $this->actingAs($admin)
        ->from(route('whatsapp.templates.create'))
        ->post(route('whatsapp.templates.store'), [
            'name' => 'bad_vars',
            'language' => 'en',
            'category' => 'UTILITY',
            'body' => 'Hello {{1}} and {{3}}',
        ])
        ->assertSessionHasErrors('body');

    expect(WhatsappTemplate::where('name', 'bad_vars')->exists())->toBeFalse();
});

it('forbids a support agent from submitting templates', function () {
    $account = wabaAccount();
    $agent = makeMember($account->organization, 'support_agent');

    $this->actingAs($agent)->post(route('whatsapp.templates.store'), [
        'name' => 'x', 'language' => 'en', 'category' => 'UTILITY', 'body' => 'hi',
    ])->assertForbidden();
});
