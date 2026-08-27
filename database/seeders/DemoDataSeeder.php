<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\Organizations\OrganizationProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local / staging demo accounts. Idempotent — safe to re-run with `db:seed`.
 *
 *   Every account's password is:  password
 *
 *   admin@ahv.test        super admin (full platform)
 *   owner@acme.test       org admin
 *   manager@acme.test     campaign manager
 *   agent@acme.test       support agent
 *   viewer@acme.test      viewer (read-only)
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $provisioner = app(OrganizationProvisioner::class);

        $organization = Organization::query()->firstWhere('slug', 'like', 'acme-traders-%')
            ?? $provisioner->create(['name' => 'Acme Traders', 'timezone' => 'Asia/Kolkata']);

        $provisioner->provisionRoles($organization);

        $superAdmin = $this->user('Platform Admin', 'admin@ahv.test', isSuperAdmin: true);
        $provisioner->addMember($organization, $superAdmin, OrganizationRole::OrgAdmin);

        $roles = [
            ['Acme Owner', 'owner@acme.test', OrganizationRole::OrgAdmin],
            ['Acme Manager', 'manager@acme.test', OrganizationRole::CampaignManager],
            ['Acme Agent', 'agent@acme.test', OrganizationRole::SupportAgent],
            ['Acme Viewer', 'viewer@acme.test', OrganizationRole::Viewer],
        ];

        foreach ($roles as [$name, $email, $role]) {
            $provisioner->addMember($organization, $this->user($name, $email), $role);
        }

        $this->command?->info('Demo accounts ready — password for all: '.self::PASSWORD);
    }

    private function user(string $name, string $email, bool $isSuperAdmin = false): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'is_super_admin' => $isSuperAdmin,
        ])->save();

        return $user;
    }
}
