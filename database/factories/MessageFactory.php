<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Message;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $phone = '91'.fake()->numerify('9########');

        return [
            'organization_id' => Organization::factory(),
            'direction' => 'outbound',
            'to_phone' => $phone,
            'to_phone_hash' => hash('sha256', $phone),
            'type' => MessageType::Template,
            'payload' => [],
            'idempotency_key' => (string) Str::ulid(),
            'status' => MessageStatus::Pending,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => MessageStatus::Sent,
            'wamid' => 'wamid.'.Str::upper(Str::random(20)),
            'sent_at' => now(),
        ]);
    }
}
