<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OptInStatus;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Organization;
use App\Services\WhatsApp\PhoneNumberNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;

class SeedWeddingContacts extends Command
{
    protected $signature = 'contacts:seed-wedding {--file=C:/Users/zilac/Downloads/whatsapp_contacts_hindi.csv} {--group=Wedding Invite} {--org=1}';
    protected $description = 'Seed Hindi contacts from CSV into specified group with opted_in status';

    public function handle(PhoneNumberNormalizer $normalizer): int
    {
        $filePath = (string) $this->option('file');
        $groupName = (string) $this->option('group');
        $orgId = (int) $this->option('org');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $org = Organization::query()->find($orgId);
        if ($org === null) {
            $org = Organization::query()->first();
        }

        if ($org === null) {
            $this->error("No organization found!");
            return 1;
        }

        $this->info("Importing contacts into organization: {$org->name} (ID: {$org->id})");

        // Find or create group
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

        $this->info("Target Group: {$group->name} (ID: {$group->id})");

        $reader = Reader::createFromPath($filePath, 'r');
        $reader->setHeaderOffset(0);

        $existing = Contact::query()->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->pluck('phone_e164')
            ->flip();

        $seenInFile = [];
        $batch = [];
        $added = 0;
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

            $existingContactId = $existing->get($e164);
            if ($existingContactId !== null) {
                // Already in database, ensure attached to group and opted in
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

        $this->info("Completed! Added/Updated {$added} contacts in '{$groupName}' (Duplicates: {$duplicates}, Invalid: {$invalid}).");

        return 0;
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
