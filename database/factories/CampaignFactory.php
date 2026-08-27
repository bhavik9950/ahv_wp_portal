<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->catchPhrase(),
            'status' => CampaignStatus::Draft,
            'timezone' => 'UTC',
            'variable_map' => [],
        ];
    }

    public function status(CampaignStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
