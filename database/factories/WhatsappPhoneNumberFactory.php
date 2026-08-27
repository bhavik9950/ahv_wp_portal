<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappPhoneNumber>
 */
class WhatsappPhoneNumberFactory extends Factory
{
    protected $model = WhatsappPhoneNumber::class;

    public function definition(): array
    {
        $account = WhatsappBusinessAccount::factory();

        return [
            'whatsapp_business_account_id' => $account,
            'organization_id' => fn (array $attrs) => WhatsappBusinessAccount::find($attrs['whatsapp_business_account_id'])?->organization_id,
            'phone_number_id' => (string) fake()->unique()->numerify('###############'),
            'display_phone_number' => '+'.fake()->numerify('91#########'),
            'verified_name' => fake()->company(),
            'quality_rating' => 'GREEN',
            'messaging_limit_tier' => 'TIER_1K',
            'status' => 'available',
            'is_default' => true,
        ];
    }

    public function forAccount(WhatsappBusinessAccount $account): static
    {
        return $this->state(fn () => [
            'whatsapp_business_account_id' => $account->getKey(),
            'organization_id' => $account->organization_id,
        ]);
    }
}
