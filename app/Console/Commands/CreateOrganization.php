<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Models\WhatsappBusinessAccount;
use App\Services\Organizations\OrganizationProvisioner;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateOrganization extends Command
{
    protected $signature = 'org:create
        {name : Company / client name for the new organization}
        {--admin-email= : Email address of the organization admin}
        {--admin-name= : Full name of the organization admin}
        {--password= : Admin login password (auto-generated if omitted)}
        {--waba-id= : Client Meta WhatsApp Business Account ID (optional)}
        {--timezone=Asia/Kolkata : Organization default timezone}';

    protected $description = 'Provision a new isolated client organization with its own admin user and dashboard';

    public function handle(OrganizationProvisioner $provisioner, TenantContext $tenant): int
    {
        $name = trim((string) $this->argument('name'));
        if ($name === '') {
            $this->error('Organization name is required.');

            return self::FAILURE;
        }

        $email = (string) ($this->option('admin-email') ?: $this->ask('Admin email address'));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A valid admin email address is required.');

            return self::FAILURE;
        }

        $adminName = (string) ($this->option('admin-name') ?: $this->ask('Admin full name', $name.' Admin'));
        $password = (string) ($this->option('password') ?: Str::password(12));

        // Create organization and provision scoped RBAC roles
        $organization = $provisioner->create([
            'name' => $name,
            'timezone' => (string) $this->option('timezone'),
        ]);

        // Find or create the user
        /** @var User $user */
        $user = User::query()->firstWhere('email', $email);
        if ($user === null) {
            $user = (new User)->forceFill([
                'name' => $adminName,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);
            $user->save();
        }

        // Assign user as OrgAdmin for this organization
        $provisioner->addMember($organization, $user, OrganizationRole::OrgAdmin);

        // If WABA ID is provided upfront, create the scoped account record
        if ($wabaId = (string) $this->option('waba-id')) {
            $tenant->set($organization);

            (new WhatsappBusinessAccount)->forceFill([
                'organization_id' => $organization->getKey(),
                'name' => "{$name} WhatsApp",
                'waba_id' => $wabaId,
                'is_active' => false,
                'default_country_code' => '91',
            ])->save();
        }

        $this->newLine();
        $this->info("✓ Organization '{$name}' provisioned successfully!");
        $this->table(
            ['Property', 'Value'],
            [
                ['Organization ID', $organization->getKey()],
                ['Organization Name', $organization->name],
                ['Admin Name', $user->name],
                ['Admin Email', $user->email],
                ['Initial Password', $password],
                ['Login URL', url('/login')],
            ]
        );

        $this->comment('The client can now log in using these credentials to access their isolated dashboard.');

        return self::SUCCESS;
    }
}
