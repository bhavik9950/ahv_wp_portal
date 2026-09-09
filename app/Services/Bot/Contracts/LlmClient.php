<?php

declare(strict_types=1);

namespace App\Services\Bot\Contracts;

use App\Services\Bot\Data\BotReply;

interface LlmClient
{
    /**
     * One assistant turn. `$messages` is the Anthropic-shaped message list
     * (role + content, content may be a string or a list of text/image blocks).
     *
     * @param  list<array{role: string, content: mixed}>  $messages
     */
    public function reply(string $system, array $messages, string $model, int $maxTokens, string $effort): BotReply;
}
