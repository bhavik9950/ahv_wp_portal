<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContactGroup;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactGroup>
 */
class ContactGroupFactory extends Factory
{
    protected $model = ContactGroup::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
