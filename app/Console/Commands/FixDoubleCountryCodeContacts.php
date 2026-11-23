<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\WhatsApp\PhoneNumberNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDoubleCountryCodeContacts extends Command
{
    protected $signature = 'contacts:repair-numbers {--group= : Also re-link repaired contacts to this group name or ID}';
    protected $description = 'Fix contacts where country code was doubled (e.g. 9191... -> 91...)';

    public function handle(): int
    {
        $this->info('Searching for contacts with duplicated country code (9191...)...');

        $broken = Contact::withoutGlobalScopes()
            ->where('phone_e164', 'like', '9191%')
            ->whereRaw('CHAR_LENGTH(phone_e164) = 14')
            ->get();

        if ($broken->isEmpty()) {
            $this->info('No broken contacts found.');

            return self::SUCCESS;
        }

        $this->info("Found {$broken->count()} contact(s) with 9191 prefix. Fixing...");

        $fixedCount = 0;
        $mergedCount = 0;
        $targetGroup = null;

        if ($this->option('group')) {
            $targetGroup = ContactGroup::withoutGlobalScopes()
                ->where('name', $this->option('group'))
                ->orWhere('id', $this->option('group'))
                ->first();
        }

        foreach ($broken as $contact) {
            $correctE164 = substr($contact->phone_e164, 2);
            $correctHash = PhoneNumberNormalizer::hash($correctE164);

            // Check if correct contact already exists in this organization
            $existing = Contact::withoutGlobalScopes()
                ->where('organization_id', $contact->organization_id)
                ->where('phone_e164', $correctE164)
                ->where('id', '!=', $contact->id)
                ->first();

            if ($existing) {
                // Transfer any group links to existing contact
                $groupLinks = DB::table('contact_group_contact')
                    ->where('contact_id', $contact->id)
                    ->pluck('contact_group_id');

                foreach ($groupLinks as $gid) {
                    DB::table('contact_group_contact')->insertOrIgnore([
                        'contact_group_id' => $gid,
                        'contact_id' => $existing->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($targetGroup && $targetGroup->organization_id === $existing->organization_id) {
                    DB::table('contact_group_contact')->insertOrIgnore([
                        'contact_group_id' => $targetGroup->id,
                        'contact_id' => $existing->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Delete broken contact
                DB::table('contact_group_contact')->where('contact_id', $contact->id)->delete();
                $contact->forceDelete();
                $mergedCount++;
            } else {
                $contact->phone_e164 = $correctE164;
                $contact->phone_hash = $correctHash;
                $contact->save();

                if ($targetGroup && $targetGroup->organization_id === $contact->organization_id) {
                    DB::table('contact_group_contact')->insertOrIgnore([
                        'contact_group_id' => $targetGroup->id,
                        'contact_id' => $contact->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $fixedCount++;
            }
        }

        $this->info("Completed: {$fixedCount} contact(s) updated, {$mergedCount} duplicate(s) merged into existing clean records.");

        return self::SUCCESS;
    }
}
