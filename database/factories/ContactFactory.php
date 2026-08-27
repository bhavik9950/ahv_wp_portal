<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        $phone = '91'.fake()->unique()->numerify('9########');

        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'country_code' => '91',
            'phone_e164' => $phone,
            'phone_hash' => hash('sha256', $phone),
            'email' => fake()->optional()->safeEmail(),
            'custom_fields' => [],
            'opt_in_status' => 'opted_in',
            'opted_in_at' => now(),
            'opt_in_source' => 'seed',
        ];
    }

    public function optedOut(): static
    {
        return $this->state(fn () => [
            'opt_in_status' => 'opted_out',
            'opted_out_at' => now(),
        ]);
    }
}
