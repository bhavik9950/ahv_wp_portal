<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OptInStatus;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class WeddingRetryFailedContactsSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();
        if (! $org) {
            $this->command->error('No organization found.');
            return;
        }

        $f1Path = 'C:/Users/zilac/Downloads/campaign-01m7acjg6p41d3mqwd4a62m9d1-failed-recipients.csv';
        $f2Path = 'C:/Users/zilac/Downloads/campaign-01m7asp1kgdj6knbbtm397b8x4-failed-recipients.csv';

        // Check if files exist locally; fallback to embedded data if running on server where Downloads folder differs
        $data = $this->loadFailedData($f1Path, $f2Path);

        $this->command->info("Loaded " . count($data) . " unique failed contacts.");

        // 1. Create or get Group for ALL 121 failed contacts
        $allGroup = ContactGroup::withoutGlobalScopes()->firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Wedding Invite - Retry (All 121 Failed)'],
            ['description' => '121 contacts that failed in 13-Nov campaigns (Cooldown + Undeliverable)']
        );

        // 2. Create or get Group for HIGH CHANCE (38 code 131049 + 10 code 130472 = 48 contacts)
        $highChanceGroup = ContactGroup::withoutGlobalScopes()->firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Wedding Invite - Retry (High Chance 48)'],
            ['description' => '48 contacts that failed strictly due to Meta 24h frequency cooldown & experiment limits']
        );

        $allContactIds = [];
        $highChanceIds = [];

        foreach ($data as $item) {
            $rawPhone = ltrim(trim($item['phone']), '+');
            $name = trim($item['name']);
            $code = $item['code'];

            // Find or create contact in DB
            $contact = Contact::withoutGlobalScopes()
                ->where('organization_id', $org->id)
                ->where('phone_e164', $rawPhone)
                ->first();

            if (! $contact) {
                $contact = new Contact();
                $contact->organization_id = $org->id;
                $contact->phone_e164 = $rawPhone;
            }

            $contact->name = $name;
            $contact->opt_in_status = OptInStatus::OptedIn;
            $contact->opted_in_at = Carbon::now();
            $contact->saveQuietly();

            $allContactIds[] = $contact->id;

            // 131049 (Engagement/cooldown) and 130472 (Experiment cohort)
            if (in_array($code, ['131049', '130472'], true)) {
                $highChanceIds[] = $contact->id;
            }
        }

        $allGroup->contacts()->syncWithoutDetaching($allContactIds);
        $highChanceGroup->contacts()->syncWithoutDetaching($highChanceIds);

        $this->command->info("Successfully populated groups:");
        $this->command->info("1. '{$allGroup->name}' -> " . count($allContactIds) . " contacts");
        $this->command->info("2. '{$highChanceGroup->name}' -> " . count($highChanceIds) . " contacts");
    }

    private function loadFailedData(string $f1, string $f2): array
    {
        $rows = [];
        if (file_exists($f1)) {
            $rows = array_merge($rows, $this->parseCsv($f1));
        }
        if (file_exists($f2)) {
            $rows = array_merge($rows, $this->parseCsv($f2));
        }

        // If running on remote VPS where C:\Users\zilac doesn't exist, use repository fallback CSV
        if (empty($rows)) {
            $fallback = database_path('data/wedding_failed_contacts.csv');
            if (file_exists($fallback)) {
                $rows = $this->parseCsv($fallback);
            }
        }

        $unique = [];
        foreach ($rows as $r) {
            $p = ltrim(trim($r['phone']), '+');
            if (! isset($unique[$p])) {
                $unique[$p] = [
                    'phone' => $p,
                    'name' => trim($r['contact'] ?? $r['name'] ?? ''),
                    'code' => trim($r['error_code'] ?? $r['code'] ?? ''),
                ];
            }
        }

        return array_values($unique);
    }

    private function parseCsv(string $path): array
    {
        $out = [];
        if (($h = fopen($path, 'r')) !== false) {
            $header = fgetcsv($h);
            if ($header) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
                $header = array_map('trim', $header);
                while (($row = fgetcsv($h)) !== false) {
                    if (count($header) === count($row)) {
                        $out[] = array_combine($header, $row);
                    }
                }
            }
            fclose($h);
        }
        return $out;
    }
}
