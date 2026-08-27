<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\WhatsappBusinessAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappBusinessAccount>
 */
class WhatsappBusinessAccountFactory extends Factory
{
    protected $model = WhatsappBusinessAccount::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company().' WABA',
            'meta_business_account_id' => (string) fake()->numerify('##############'),
            'waba_id' => (string) fake()->unique()->numerify('###############'),
            'app_id' => (string) fake()->numerify('###############'),
            'access_token' => 'EAA'.fake()->regexify('[A-Za-z0-9]{60}'),
            'app_secret' => fake()->sha1(),
            'webhook_verify_token' => fake()->slug(3),
            'api_version' => 'v22.0',
            'default_country_code' => '91',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
