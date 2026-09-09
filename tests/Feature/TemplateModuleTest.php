<?php

declare(strict_types=1);

use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('renders the templates index with the DataTable + labelled filters', function () {
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');
    WhatsappTemplate::factory()->forAccount($account)->create(['name' => 'order_ready', 'category' => 'UTILITY']);

    $this->actingAs($admin)->get(route('whatsapp.templates.index'))
        ->assertOk()
        ->assertSee('id="templates-table"', false)
        ->assertSee('data-datatable', false)
        ->assertSee('data-dt-filter', false)
        ->assertSee('order_ready')
        ->assertSee('Category'); // the visible filter label
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

it('submits a template with a call (phone number) button', function () {
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');

    $this->actingAs($admin)->post(route('whatsapp.templates.store'), [
        'name' => 'call_us_back',
        'language' => 'en',
        'category' => 'UTILITY',
        'header_type' => 'none',
        'body' => 'Hi {{1}}, tap below to call us.',
        'buttons' => [
            ['type' => 'phone', 'text' => 'Call AH&V', 'phone_number' => '+917878159887'],
            ['type' => 'quick_reply', 'text' => 'No thanks'],
        ],
    ])->assertRedirect();

    $buttons = collect(WhatsappTemplate::where('name', 'call_us_back')->sole()->components)
        ->firstWhere('type', 'BUTTONS')['buttons'];

    expect($buttons)->toHaveCount(2)
        ->and($buttons[0])->toMatchArray(['type' => 'PHONE_NUMBER', 'text' => 'Call AH&V', 'phone_number' => '+917878159887'])
        ->and($buttons[1])->toMatchArray(['type' => 'QUICK_REPLY', 'text' => 'No thanks']);
});

it('rejects a call button with no phone number', function () {
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');

    $this->actingAs($admin)->post(route('whatsapp.templates.store'), [
        'name' => 'bad_call_btn',
        'language' => 'en',
        'category' => 'UTILITY',
        'header_type' => 'none',
        'body' => 'Hi there.',
        'buttons' => [['type' => 'phone', 'text' => 'Call us']],
    ])->assertSessionHasErrors('buttons.0.phone_number');

    expect(WhatsappTemplate::where('name', 'bad_call_btn')->exists())->toBeFalse();
});

it('submits an image-header template with an uploaded sample and stores the handle + a local copy', function () {
    Storage::fake('local');
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');

    $this->actingAs($admin)->post(route('whatsapp.templates.store'), [
        'name' => 'ahv_election_promo_service',
        'language' => 'hi',
        'category' => 'MARKETING',
        'header_type' => 'image',
        'body' => 'चुनावी प्रचार के लिए पोस्ट और रील्स बनवाने हेतु संपर्क करें!',
        'sample_media' => UploadedFile::fake()->image('promo.jpg', 800, 1000),
    ])->assertRedirect();

    $template = WhatsappTemplate::where('name', 'ahv_election_promo_service')->sole();
    $header = collect($template->components)->firstWhere('type', 'HEADER');

    expect($header['format'])->toBe('IMAGE')
        ->and($header['example']['header_handle'][0] ?? null)->toStartWith('4::')
        ->and($template->header_sample_media_id)->not->toBeNull()
        ->and($template->headerSampleMedia->category())->toBe('image');
});

it('attaches a header sample to an already-approved media template', function () {
    Storage::fake('local');
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');
    $template = WhatsappTemplate::factory()->forAccount($account)->create([
        'name' => 'approved_img_tpl',
        'components' => [['type' => 'HEADER', 'format' => 'IMAGE'], ['type' => 'BODY', 'text' => 'hi']],
    ]);

    $this->actingAs($admin)->post(route('whatsapp.templates.header-sample', $template), [
        'sample_media' => UploadedFile::fake()->image('banner.png', 900, 900),
    ])->assertRedirect();

    expect($template->fresh()->header_sample_media_id)->not->toBeNull();
});

it('rejects an image-header template with no sample file', function () {
    $account = wabaAccount();
    $admin = makeMember($account->organization, 'org_admin');

    $this->actingAs($admin)
        ->from(route('whatsapp.templates.create'))
        ->post(route('whatsapp.templates.store'), [
            'name' => 'promo_no_sample',
            'language' => 'hi',
            'category' => 'MARKETING',
            'header_type' => 'image',
            'body' => 'Hello',
        ])
        ->assertSessionHasErrors('sample_media');

    expect(WhatsappTemplate::count())->toBe(0);
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
