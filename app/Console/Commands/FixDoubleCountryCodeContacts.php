<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Organization;
use App\Services\WhatsApp\PhoneNumberNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDoubleCountryCodeContacts extends Command
{
    protected $signature = 'contacts:repair-numbers 
        {--org= : Scope to specific organization ID, name or slug (optional)}
        {--group= : Also re-link repaired contacts to this group name or ID}';

    protected $description = 'Fix contacts where country code was doubled (e.g. 9191... -> 91...)';

    public function handle(): int
    {
        $orgFilter = $this->option('org');
        $groupOption = $this->option('group');

        $query = Contact::withoutGlobalScopes()
            ->where('phone_e164', 'like', '9191%')
            ->whereRaw('CHAR_LENGTH(phone_e164) = 14');

        if ($orgFilter) {
            $org = Organization::withoutGlobalScopes()
                ->where('id', $orgFilter)
                ->orWhere('name', 'like', "%{$orgFilter}%")
                ->orWhere('slug', $orgFilter)
                ->first();

            if (! $org) {
                $this->error("Organization '{$orgFilter}' not found.");

                return self::FAILURE;
            }

            $query->where('organization_id', $org->id);
            $this->info("Scoped to organization: {$org->name} (ID: {$org->id})");
        }

        $broken = $query->get();

        if ($broken->isEmpty()) {
            $this->info('No broken contacts found with 9191 prefix.');

            return self::SUCCESS;
        }

        $this->info("Found {$broken->count()} contact(s) with 9191 prefix across organizations. Fixing...");

        $fixedCount = 0;
        $mergedCount = 0;
        $groupLinkedCount = 0;

        foreach ($broken as $contact) {
            $correctE164 = substr($contact->phone_e164, 2);
            $correctHash = PhoneNumberNormalizer::hash($correctE164);

            // Find group in the contact's OWN organization if --group was specified
            $targetGroup = null;
            if ($groupOption) {
                $targetGroup = ContactGroup::withoutGlobalScopes()
                    ->where('organization_id', $contact->organization_id)
                    ->where(function ($q) use ($groupOption) {
                        $q->where('name', $groupOption)->orWhere('id', $groupOption);
                    })
                    ->first();
            }

            // Check if correct contact already exists in this organization
            $existing = Contact::withoutGlobalScopes()
                ->where('organization_id', $contact->organization_id)
                ->where('phone_e164', $correctE164)
                ->where('id', '!=', $contact->id)
                ->first();

            if ($existing) {
                // Transfer existing group links from broken record to the clean record
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

                if ($targetGroup) {
                    $linked = DB::table('contact_group_contact')->insertOrIgnore([
                        'contact_group_id' => $targetGroup->id,
                        'contact_id' => $existing->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($linked) {
                        $groupLinkedCount++;
                    }
                }

                // Delete the duplicate broken contact
                DB::table('contact_group_contact')->where('contact_id', $contact->id)->delete();
                $contact->forceDelete();
                $mergedCount++;
            } else {
                $contact->phone_e164 = $correctE164;
                $contact->phone_hash = $correctHash;
                $contact->save();

                if ($targetGroup) {
                    $linked = DB::table('contact_group_contact')->insertOrIgnore([
                        'contact_group_id' => $targetGroup->id,
                        'contact_id' => $contact->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($linked) {
                        $groupLinkedCount++;
                    }
                }

                $fixedCount++;
            }
        }

        $this->info("Completed:");
        $this->line(" - {$fixedCount} contact(s) updated to clean E.164");
        $this->line(" - {$mergedCount} duplicate(s) merged into existing clean records");
        if ($groupOption) {
            $this->line(" - Contacts linked to group '{$groupOption}'");
        }

        return self::SUCCESS;
    }
}
