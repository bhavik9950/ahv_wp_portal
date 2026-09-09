<?php

declare(strict_types=1);

namespace App\Services\Bot\Llm;

use App\Services\Bot\Contracts\LlmClient;
use App\Services\Bot\Data\BotReply;

/**
 * Deterministic, offline. Echoes a canned reply that references the last user
 * message so tests can assert the round-trip without a network call. Forced in
 * phpunit.xml via BOT_LLM_DRIVER=fake.
 */
final class FakeLlmClient implements LlmClient
{
    public static ?string $nextReply = null;

    public function reply(string $system, array $messages, string $model, int $maxTokens, string $effort): BotReply
    {
        $last = '';
        foreach (array_reverse($messages) as $m) {
            if ($m['role'] === 'user') {
                $last = is_string($m['content']) ? $m['content'] : 'attachment';
                break;
            }
        }

        $text = self::$nextReply ?? 'Thanks for your message: "'.mb_strimwidth($last, 0, 60, '…').'". Our team can help — shall we set up a quick call?';
        self::$nextReply = null;

        return new BotReply(text: $text, model: $model, tokensIn: 42, tokensOut: 18);
    }
}
