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

class WeddingContactsSeeder extends Seeder
{
    public function run(PhoneNumberNormalizer $normalizer): void
    {
        $possiblePaths = [
            base_path('database/data/whatsapp_contacts_hindi.csv'),
            'C:/Users/zilac/Downloads/whatsapp_contacts_hindi.csv',
            '/home/ahvsoftware-wap/htdocs/wap.ahvsoftware.com/portal/database/data/whatsapp_contacts_hindi.csv',
        ];

        $filePath = null;
        foreach ($possiblePaths as $p) {
            if (file_exists($p)) {
                $filePath = $p;
                break;
            }
        }

        if ($filePath === null) {
            $this->command->error("CSV file not found in database/data/whatsapp_contacts_hindi.csv");
            return;
        }

        $org = Organization::query()->first();
        if ($org === null) {
            $this->command->error("No organization found!");
            return;
        }

        $groupName = 'Wedding Invite';
        $group = ContactGroup::query()->withoutGlobalScopes()->where([
            'organization_id' => $org->id,
            'name' => $groupName,
        ])->first();

        if ($group === null) {
            $group = new ContactGroup();
            $group->forceFill([
                'organization_id' => $org->id,
                'name' => $groupName,
                'description' => 'Wedding Invitation Guest List',
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

        foreach ($reader->getRecords() as $record) {
            $name = trim((string) ($record['name'] ?? ''));
            $rawPhone = trim((string) ($record['phone'] ?? ''));

            $parsed = $normalizer->parse($rawPhone, '91');
            if ($parsed === null) {
                continue;
            }

            $e164 = $parsed['e164'];

            if (isset($seenInFile[$e164])) {
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
                        'opt_in_source' => 'wedding_csv_seed',
                    ])->save();

                    DB::table('contact_group_contact')->insertOrIgnore([
                        'contact_id' => $existingContact->id,
                        'contact_group_id' => $group->id,
                    ]);
                    $added++;
                }
                continue;
            }

            $batch[] = [
                'id' => (string) Str::ulid(),
                'organization_id' => $org->id,
                'name' => $name,
                'email' => null,
                'country_code' => $parsed['country_code'],
                'phone_e164' => $e164,
                'phone_hash' => PhoneNumberNormalizer::hash($e164),
                'custom_fields' => json_encode([]),
                'opt_in_status' => OptInStatus::OptedIn->value,
                'opted_in_at' => now(),
                'opt_in_source' => 'wedding_csv_seed',
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

        $this->command->info("Seeded {$added} contacts into '{$groupName}' group for '{$org->name}'.");
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
        });
    }
}
