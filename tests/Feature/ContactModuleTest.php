<?php

declare(strict_types=1);

use App\Enums\OptInStatus;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\OptInRecord;

it('creates a contact with a normalized E.164 number', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');

    $this->actingAs($manager)->post(route('whatsapp.contacts.store'), [
        'name' => 'Asha',
        'phone' => '098765 43210',
        'country_code' => '91',
        'email' => 'asha@example.com',
    ])->assertRedirect();

    $contact = Contact::sole();
    expect($contact->phone_e164)->toBe('919876543210')
        ->and($contact->phone_hash)->toBe(hash('sha256', '919876543210'))
        ->and($contact->opt_in_status)->toBe(OptInStatus::Unknown);
});

it('renders the contacts index as a DataTable with labelled filters', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    Contact::factory()->for($org)->create(['name' => 'Asha', 'phone_e164' => '919876500123']);

    $this->actingAs($manager)->get(route('whatsapp.contacts.index'))
        ->assertOk()
        ->assertSee('id="contacts-table"', false)
        ->assertSee('data-dt-filter', false)
        ->assertSee('Asha')
        ->assertSee('Opt-in')
        ->assertSee('Group');
});

it('rejects a duplicate phone number', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    Contact::factory()->for($org)->create(['name' => 'Existing']);
    $existing = Contact::first();

    $this->actingAs($manager)
        ->from(route('whatsapp.contacts.create'))
        ->post(route('whatsapp.contacts.store'), ['phone' => '+'.$existing->phone_e164])
        ->assertSessionHasErrors('phone');

    expect(Contact::count())->toBe(1);
});

it('records opt-out in the ledger and syncs the contact status', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $contact = Contact::factory()->for($org)->create();

    $this->actingAs($manager)->post(route('whatsapp.contacts.opt-out', $contact))->assertRedirect();

    $contact->refresh();
    expect($contact->opt_in_status)->toBe(OptInStatus::OptedOut)
        ->and($contact->opted_out_at)->not->toBeNull()
        ->and(OptInRecord::where('contact_id', $contact->getKey())->where('status', 'opt_out')->count())->toBe(1);
});

it('forbids a viewer from creating contacts', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->post(route('whatsapp.contacts.store'), ['phone' => '919876543210'])
        ->assertForbidden();
});

it('bulk-assigns contacts to a group', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $group = ContactGroup::factory()->for($org)->create();
    $contacts = Contact::factory()->for($org)->count(3)->create();

    $this->actingAs($manager)->post(route('whatsapp.groups.assign'), [
        'group_id' => $group->id,
        'contact_ids' => $contacts->pluck('id')->all(),
        'action' => 'add',
    ])->assertRedirect();

    expect($group->contacts()->count())->toBe(3);
});

it('bulk-assigns contacts to a brand-new group by name', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $contacts = Contact::factory()->for($org)->count(4)->create();

    $this->actingAs($manager)->post(route('whatsapp.groups.assign'), [
        'new_group_name' => 'Parshad — Sri Ganganagar',
        'contact_ids' => $contacts->pluck('id')->all(),
        'action' => 'add',
    ])->assertRedirect();

    $group = ContactGroup::where('name', 'Parshad — Sri Ganganagar')->sole();
    expect($group->organization_id)->toBe($org->getKey())
        ->and($group->contacts()->count())->toBe(4);
});

it('bulk-records opt-in for many contacts', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $contacts = Contact::factory()->for($org)->count(5)->create(['opt_in_status' => OptInStatus::Unknown]);

    $this->actingAs($manager)->post(route('whatsapp.contacts.bulk-opt-in'), [
        'contact_ids' => $contacts->pluck('id')->all(),
        'action' => 'opt_in',
        'source' => 'offline_consent_form',
    ])->assertRedirect();

    expect(Contact::where('opt_in_status', OptInStatus::OptedIn->value)->count())->toBe(5)
        ->and(OptInRecord::where('status', 'opt_in')->count())->toBe(5)
        ->and(OptInRecord::first()->source)->toBe('offline_consent_form');
});

it('bulk assign needs either an existing group or a new name', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $contacts = Contact::factory()->for($org)->count(2)->create();

    $this->actingAs($manager)
        ->from(route('whatsapp.contacts.index'))
        ->post(route('whatsapp.groups.assign'), [
            'contact_ids' => $contacts->pluck('id')->all(),
            'action' => 'add',
        ])
        ->assertSessionHasErrors('group_id');
});

it('exports contacts as CSV for an authorized user', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    Contact::factory()->for($org)->count(2)->create();

    $response = $this->actingAs($manager)->get(route('whatsapp.contacts.export'));

    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('phone_e164');
});
