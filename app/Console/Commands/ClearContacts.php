<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearContacts extends Command
{
    protected $signature = 'contacts:clear 
                            {--group= : Only delete contacts in this group name} 
                            {--org= : Organization ID (defaults to first)} 
                            {--force : Bypass confirmation}';

    protected $description = 'Permanently delete contacts (all or by group) to clean up seeded or old data';

    public function handle(): int
    {
        $groupName = $this->option('group');
        $orgId = $this->option('org');
        $force = (bool) $this->option('force');

        $org = $orgId ? Organization::find($orgId) : Organization::first();
        if ($org === null) {
            $this->error('No organization found.');
            return 1;
        }

        $query = Contact::query()->withoutGlobalScopes()->where('organization_id', $org->id);

        if ($groupName) {
            $group = ContactGroup::query()
                ->withoutGlobalScopes()
                ->where('organization_id', $org->id)
                ->where('name', $groupName)
                ->first();

            if ($group === null) {
                $this->warn("Group '{$groupName}' not found in organization {$org->name}. No contacts deleted.");
                return 0;
            }

            $contactIds = DB::table('contact_group_contact')
                ->where('contact_group_id', $group->id)
                ->pluck('contact_id');

            $count = $contactIds->count();
            if ($count === 0) {
                $this->info("No contacts found in group '{$groupName}'.");
                return 0;
            }

            if (! $force && ! $this->confirm("Are you sure you want to permanently delete {$count} contact(s) from group '{$groupName}' in '{$org->name}'?")) {
                $this->info('Aborted.');
                return 0;
            }

            // Detach and delete
            DB::transaction(function () use ($contactIds, $group): void {
                DB::table('contact_group_contact')->where('contact_group_id', $group->id)->delete();
                Contact::query()->withoutGlobalScopes()->whereIn('id', $contactIds)->forceDelete();
            });

            $this->info("Successfully deleted {$count} contacts from group '{$groupName}'.");
            return 0;
        }

        $count = $query->count();
        if ($count === 0) {
            $this->info("No contacts found in organization '{$org->name}'.");
            return 0;
        }

        if (! $force && ! $this->confirm("Are you sure you want to PERMANENTLY delete ALL {$count} contacts in organization '{$org->name}'? This cannot be undone.")) {
            $this->info('Aborted.');
            return 0;
        }

        DB::transaction(function () use ($org): void {
            $contactIds = Contact::query()->withoutGlobalScopes()->where('organization_id', $org->id)->pluck('id');
            
            // Delete pivot rows first
            DB::table('contact_group_contact')->whereIn('contact_id', $contactIds)->delete();
            
            // Force delete all contacts of this org (including soft-deleted)
            Contact::withTrashed()->withoutGlobalScopes()->where('organization_id', $org->id)->forceDelete();
        });

        $this->info("Successfully purged {$count} contacts from '{$org->name}'.");

        return 0;
    }
}
