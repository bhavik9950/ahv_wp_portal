<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappTemplate>
 */
class WhatsappTemplateFactory extends Factory
{
    protected $model = WhatsappTemplate::class;

    public function definition(): array
    {
        return [
            'whatsapp_business_account_id' => WhatsappBusinessAccount::factory(),
            'organization_id' => fn (array $attrs) => WhatsappBusinessAccount::find($attrs['whatsapp_business_account_id'])?->organization_id,
            'name' => fake()->unique()->lexify('tmpl_????????'),
            'language' => 'en',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}, your order {{2}} has been dispatched.'],
            ],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'PENDING']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'REJECTED', 'rejection_reason' => 'INVALID_FORMAT']);
    }

    public function forAccount(WhatsappBusinessAccount $account): static
    {
        return $this->state(fn () => [
            'whatsapp_business_account_id' => $account->getKey(),
            'organization_id' => $account->organization_id,
        ]);
    }
}
