<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Models\User;
use App\Services\Organizations\OrganizationProvisioner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (! app()->environment('production')) {
            $this->seedDevelopmentData();
        }
    }

    private function seedDevelopmentData(): void
    {
        $provisioner = app(OrganizationProvisioner::class);

        $superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@ahv.test',
        ]);

        $orgAdmin = User::factory()->create([
            'name' => 'Acme Admin',
            'email' => 'owner@acme.test',
        ]);

        $organization = $provisioner->create(['name' => 'Acme Traders', 'timezone' => 'Asia/Kolkata'], $orgAdmin);
        $provisioner->addMember($organization, $superAdmin, OrganizationRole::OrgAdmin);
    }
}
