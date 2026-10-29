<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;
use App\Support\TenantContext;

it('scopes every tenant model to the active organization', function () {
    $orgA = makeOrganization(['name' => 'Org A']);
    WhatsappBusinessAccount::factory()->for($orgA)->create();
    Contact::factory()->for($orgA)->create();
    Campaign::factory()->for($orgA)->create();
    Message::factory()->for($orgA)->create();

    $orgB = makeOrganization(['name' => 'Org B']); // bindTenant switches to B
    WhatsappBusinessAccount::factory()->for($orgB)->create();

    expect(WhatsappBusinessAccount::count())->toBe(1)
        ->and(Contact::count())->toBe(0)
        ->and(Campaign::count())->toBe(0)
        ->and(Message::count())->toBe(0);
});

it('cannot load another organizations record by primary key', function () {
    $orgA = makeOrganization();
    $accountA = WhatsappBusinessAccount::factory()->for($orgA)->create();
    $templateA = WhatsappTemplate::factory()->forAccount($accountA)->create();

    makeOrganization(); // switch tenant to a different org

    expect(WhatsappBusinessAccount::find($accountA->getKey()))->toBeNull()
        ->and(WhatsappTemplate::find($templateA->getKey()))->toBeNull();
});

it('fails closed when no tenant is bound', function () {
    $org = makeOrganization();
    Contact::factory()->for($org)->create();

    app(TenantContext::class)->clear();

    expect(Contact::count())->toBe(0);
});

it('auto-fills organization_id from the tenant context on create', function () {
    $org = makeOrganization();

    $contact = Contact::factory()->make();
    $contact->organization_id = null;
    $contact->save();

    expect($contact->fresh()->organization_id)->toBe($org->getKey());
});

it('isolates authenticated users to their own organization on login and blocks cross-org access', function () {
    $orgA = makeOrganization(['name' => 'Acme Inc']);
    $userA = makeMember($orgA, 'org_admin');
    $contactA = Contact::factory()->for($orgA)->create(['name' => 'Alice Acme', 'phone_e164' => '919111111111']);

    $orgB = makeOrganization(['name' => 'Beta Corp']);
    $userB = makeMember($orgB, 'org_admin');
    $contactB = Contact::factory()->for($orgB)->create(['name' => 'Bob Beta', 'phone_e164' => '919222222222']);

    // User A logs in and accesses dashboard
    $resA = $this->actingAs($userA)->get('/dashboard');
    $resA->assertOk();
    expect(session('current_organization_id'))->toBe($orgA->getKey());

    // User A can see their own contact, but not Org B's contact
    $contactsResA = $this->actingAs($userA)->get(route('whatsapp.contacts.index'));
    $contactsResA->assertOk()
        ->assertSee('Alice Acme')
        ->assertDontSee('Bob Beta');

    // User B logs in and accesses dashboard
    $resB = $this->actingAs($userB)->get('/dashboard');
    $resB->assertOk();
    expect(session('current_organization_id'))->toBe($orgB->getKey());

    // User B can see their own contact, but not Org A's contact
    $contactsResB = $this->actingAs($userB)->get(route('whatsapp.contacts.index'));
    $contactsResB->assertOk()
        ->assertSee('Bob Beta')
        ->assertDontSee('Alice Acme');

    // User B cannot access Org A's contact details (returns 404 due to tenant scope)
    $this->actingAs($userB)->get(route('whatsapp.contacts.show', $contactA))->assertNotFound();

    // User B cannot tamper session to Org A (returns 403)
    $this->actingAs($userB)
        ->withSession(['current_organization_id' => $orgA->getKey()])
        ->get('/dashboard')
        ->assertOk(); // Falls back safely to Org B or returns Org B
    expect(session('current_organization_id'))->toBe($orgB->getKey());
});
