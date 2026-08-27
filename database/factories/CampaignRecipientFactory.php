<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignRecipient>
 */
class CampaignRecipientFactory extends Factory
{
    protected $model = CampaignRecipient::class;

    public function definition(): array
    {
        $campaign = Campaign::factory();

        return [
            'campaign_id' => $campaign,
            'organization_id' => fn (array $attrs) => Campaign::find($attrs['campaign_id'])?->organization_id,
            'phone_e164' => '91'.fake()->unique()->numerify('9########'),
            'rendered_variables' => [],
            'status' => 'pending',
            'attempts' => 0,
        ];
    }

    public function forCampaign(Campaign $campaign): static
    {
        return $this->state(fn () => [
            'campaign_id' => $campaign->getKey(),
            'organization_id' => $campaign->organization_id,
        ]);
    }
}
