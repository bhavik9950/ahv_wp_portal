<?php

declare(strict_types=1);

namespace App\Services\Bot\Data;

final readonly class BotReply
{
    public function __construct(
        public string $text,
        public string $model,
        public int $tokensIn = 0,
        public int $tokensOut = 0,
        public string $stopReason = 'end_turn',
    ) {}

    public function isRefusal(): bool
    {
        return $this->stopReason === 'refusal';
    }
}
