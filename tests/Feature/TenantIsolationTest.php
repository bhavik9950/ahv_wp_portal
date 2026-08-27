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
