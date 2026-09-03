<?php

declare(strict_types=1);

use App\Jobs\AnalyzeContactImportJob;
use App\Jobs\CommitContactImportJob;
use App\Models\Contact;
use App\Models\ContactImport;
use App\Services\Contacts\ContactImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeImport(string $csv): ContactImport
{
    $org = makeOrganization();
    Storage::fake('local');

    $path = 'imports/test.csv';
    Storage::disk('local')->put($path, $csv);

    return ContactImport::create([
        'original_filename' => 'test.csv',
        'disk' => 'local',
        'path' => $path,
        'column_map' => ['name' => 'name', 'phone' => 'phone'],
        'options' => ['mark_opted_in' => false],
    ]);
}

it('analyzes a CSV into valid / invalid / duplicate counts', function () {
    $org = makeOrganization();
    Contact::factory()->for($org)->create(); // no clash with the CSV below

    $import = ContactImport::create([
        'original_filename' => 'c.csv',
        'disk' => 'local',
        'path' => 'imports/c.csv',
        'column_map' => ['name' => 'name', 'phone' => 'phone'],
    ]);
    Storage::fake('local');
    Storage::disk('local')->put('imports/c.csv', <<<'CSV'
    name,phone
    Asha,9876543210
    Bhavik,9876543211
    Dup,9876543210
    Bad,not-a-number
    CSV);

    app(ContactImportService::class)->analyze($import->fresh());

    $import->refresh();
    expect($import->total_rows)->toBe(4)
        ->and($import->valid_rows)->toBe(2)
        ->and($import->duplicate_rows)->toBe(1)
        ->and($import->invalid_rows)->toBe(1)
        ->and($import->error_report_path)->not->toBeNull();
});

it('commits only the valid rows as contacts', function () {
    $import = makeImport(<<<'CSV'
    name,phone
    Asha,9876543210
    Bhavik,9876543211
    Bad,xx
    CSV);

    $service = app(ContactImportService::class);
    $service->analyze($import->fresh());
    $service->commit($import->fresh());

    expect(Contact::count())->toBe(2)
        ->and(Contact::pluck('phone_e164')->sort()->values()->all())->toBe(['919876543210', '919876543211'])
        ->and($import->fresh()->status)->toBe('completed');
});

it('requires the contact.import permission', function () {
    $org = makeOrganization();
    $agent = makeMember($org, 'support_agent'); // has ContactManage but not ContactImport

    $this->actingAs($agent)->get(route('whatsapp.contacts.import.create'))->assertForbidden();

    $manager = makeMember($org, 'campaign_manager');
    $this->actingAs($manager)->get(route('whatsapp.contacts.import.create'))->assertOk();
});

it('drives the full HTTP flow: upload → map → analyze → commit', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    Storage::fake('local');

    $csv = "name,phone,city\nRavi,9811111111,Delhi\nMeena,9822222222,Mumbai\nBad,xx,Pune\nRavi2,9811111111,Delhi\n";
    $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

    $upload = $this->actingAs($manager)->post(route('whatsapp.contacts.import.store'), ['file' => $file]);
    $import = ContactImport::sole();
    $upload->assertRedirect(route('whatsapp.contacts.import.map', ['import' => $import, 'headers' => 'name,phone,city']));

    $this->actingAs($manager)->get(route('whatsapp.contacts.import.map', $import))->assertOk();

    $this->actingAs($manager)->post(route('whatsapp.contacts.import.analyze', $import), [
        'column_map' => ['name' => 'name', 'phone' => 'phone', 'city' => ''],
    ])->assertRedirect(route('whatsapp.contacts.import.show', $import));

    $import->refresh();
    expect($import->status)->toBe('analyzed')
        ->and($import->valid_rows)->toBe(2)
        ->and($import->invalid_rows)->toBe(1)
        ->and($import->duplicate_rows)->toBe(1);

    $this->actingAs($manager)->post(route('whatsapp.contacts.import.commit', $import))->assertRedirect();

    expect($import->fresh()->status)->toBe('completed')
        ->and(Contact::count())->toBe(2);

    // "city" was unmapped → stored as a custom field
    expect(Contact::where('phone_e164', '919811111111')->value('custom_fields'))->toMatchArray(['city' => 'Delhi']);
});

it('tracks live progress: imported_rows climbs across chunks', function () {
    // 250 valid rows → CHUNK=100 → progress updates at 100, 200, then 250.
    $rows = collect(range(1, 250))
        ->map(fn ($i) => 'User'.$i.',98'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT))
        ->implode("\n");
    $import = makeImport("name,phone\n{$rows}\n");

    $service = app(ContactImportService::class);
    $service->analyze($import->fresh());
    expect($import->fresh()->valid_rows)->toBe(250);

    $service->commit($import->fresh());

    $import->refresh();
    expect($import->status)->toBe('completed')
        ->and($import->imported_rows)->toBe(250)
        ->and($import->progressPercent())->toBe(100)
        ->and(Contact::count())->toBe(250);
});

it('runs the async import pipeline end to end via jobs', function () {
    $import = makeImport("name,phone\nAsha,9998887770\n");

    AnalyzeContactImportJob::dispatchSync($import->getKey());
    expect($import->fresh()->status)->toBe('analyzed');

    CommitContactImportJob::dispatchSync($import->getKey());
    expect($import->fresh()->status)->toBe('completed')
        ->and(Contact::where('phone_e164', '919998887770')->exists())->toBeTrue();
});
