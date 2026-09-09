<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Organization;
use App\Models\VoiceCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoiceCampaign>
 */
class VoiceCampaignFactory extends Factory
{
    protected $model = VoiceCampaign::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->catchPhrase(),
            'status' => CampaignStatus::Draft,
            'delay_seconds' => 5,
            'timezone' => 'UTC',
            'consent_confirmed' => true,
        ];
    }

    public function status(CampaignStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
