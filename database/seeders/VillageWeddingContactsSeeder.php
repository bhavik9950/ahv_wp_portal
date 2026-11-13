<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OptInStatus;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Organization;
use App\Services\WhatsApp\PhoneNumberNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;

class VillageWeddingContactsSeeder extends Seeder
{
    public function run(PhoneNumberNormalizer $normalizer): void
    {
        $possiblePaths = [
            base_path('database/data/village_wa.csv'),
            base_path('database/data/Village_WA.csv'),
            'C:/Users/zilac/Downloads/Village_WA.csv',
            '/home/ahvsoftware-wap/htdocs/wap.ahvsoftware.com/portal/database/data/village_wa.csv',
        ];

        $filePath = null;
        foreach ($possiblePaths as $p) {
            if (file_exists($p)) {
                $filePath = $p;
                break;
            }
        }

        if ($filePath === null) {
            $this->command->error("CSV file not found in database/data/village_wa.csv");
            return;
        }

        $org = Organization::query()->first();
        if ($org === null) {
            $this->command->error("No organization found!");
            return;
        }

        $groupName = 'Wedding Invite - Village';
        $group = ContactGroup::query()->withoutGlobalScopes()->where([
            'organization_id' => $org->id,
            'name' => $groupName,
        ])->first();

        if ($group === null) {
            $group = new ContactGroup();
            $group->forceFill([
                'organization_id' => $org->id,
                'name' => $groupName,
                'description' => 'Wedding Invitation - Village Guest List',
            ])->save();
        }

        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setHeaderOffset(0);

        $existing = Contact::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->pluck('phone_e164')
            ->flip();

        $seenInFile = [];
        $batch = [];
        $added = 0;
        $updated = 0;
        $duplicates = 0;
        $invalid = 0;

        foreach ($reader->getRecords() as $record) {
            $name = trim((string) ($record['Name'] ?? $record['name'] ?? ''));
            $rawPhone = trim((string) ($record['Phone'] ?? $record['phone'] ?? ''));

            $parsed = $normalizer->parse($rawPhone, '91');
            if ($parsed === null) {
                $invalid++;
                continue;
            }

            $e164 = $parsed['e164'];

            if (isset($seenInFile[$e164])) {
                $duplicates++;
                continue;
            }
            $seenInFile[$e164] = true;

            if ($existing->has($e164)) {
                $existingContact = Contact::query()->withoutGlobalScopes()
                    ->where('organization_id', $org->id)
                    ->where('phone_e164', $e164)
                    ->first();

                if ($existingContact !== null) {
                    $existingContact->forceFill([
                        'name' => $name !== '' ? $name : $existingContact->name,
                        'opt_in_status' => OptInStatus::OptedIn->value,
                        'opted_in_at' => now(),
                        'opt_in_source' => 'village_wedding_csv_seed',
                    ])->save();

                    DB::table('contact_group_contact')->insertOrIgnore([
                        'contact_id' => $existingContact->id,
                        'contact_group_id' => $group->id,
                    ]);

                    DB::table('opt_in_records')->insert([
                        'id' => (string) Str::ulid(),
                        'organization_id' => $org->id,
                        'contact_id' => $existingContact->id,
                        'phone_e164' => $e164,
                        'status' => 'opt_in',
                        'source' => 'village_wedding_csv_seed',
                        'note' => 'Opted in via Village Wedding guest list seed',
                        'created_at' => now(),
                    ]);
                    $updated++;
                }
                continue;
            }

            $contactId = (string) Str::ulid();
            $batch[] = [
                'id' => $contactId,
                'organization_id' => $org->id,
                'name' => $name,
                'email' => null,
                'country_code' => $parsed['country_code'],
                'phone_e164' => $e164,
                'phone_hash' => PhoneNumberNormalizer::hash($e164),
                'custom_fields' => json_encode([]),
                'opt_in_status' => OptInStatus::OptedIn->value,
                'opted_in_at' => now(),
                'opt_in_source' => 'village_wedding_csv_seed',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 100) {
                $this->insertBatch($batch, $group->id);
                $added += count($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->insertBatch($batch, $group->id);
            $added += count($batch);
        }

        $this->command->info("Seeded {$added} new and {$updated} existing contacts into '{$groupName}' (Duplicates: {$duplicates}, Invalid: {$invalid}).");
    }

    private function insertBatch(array $batch, string $groupId): void
    {
        DB::transaction(function () use ($batch, $groupId): void {
            Contact::query()->withoutGlobalScopes()->insert($batch);

            $pivot = array_map(fn ($row) => [
                'contact_id' => $row['id'],
                'contact_group_id' => $groupId,
            ], $batch);

            DB::table('contact_group_contact')->insertOrIgnore($pivot);

            $optInRows = array_map(fn ($row) => [
                'id' => (string) Str::ulid(),
                'organization_id' => $row['organization_id'],
                'contact_id' => $row['id'],
                'phone_e164' => $row['phone_e164'],
                'status' => 'opt_in',
                'source' => 'village_wedding_csv_seed',
                'note' => 'Opted in via Village Wedding guest list seed',
                'created_at' => now(),
            ], $batch);

            DB::table('opt_in_records')->insert($optInRows);
        });
    }
}
