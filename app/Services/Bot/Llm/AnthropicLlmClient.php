<?php

declare(strict_types=1);

namespace App\Services\Bot\Llm;

use Anthropic\Client;
use App\Services\Bot\Contracts\LlmClient;
use App\Services\Bot\Data\BotReply;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper over the official Anthropic PHP SDK's Messages API. Adaptive
 * thinking, cached system prefix, no tools (MVP). Errors bubble as
 * RuntimeException so the job's try/catch can send the fallback message
 * instead of crashing the queue.
 */
final class AnthropicLlmClient implements LlmClient
{
    public function reply(string $system, array $messages, string $model, int $maxTokens, string $effort): BotReply
    {
        $key = (string) config('services.bot.anthropic_key');
        if ($key === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $client = new Client(apiKey: $key);

        try {
            $message = $client->messages->create(
                maxTokens: $maxTokens,
                messages: $messages,
                model: $model,
                outputConfig: ['effort' => $effort],
                system: [[
                    'type' => 'text',
                    'text' => $system,
                    'cacheControl' => ['type' => 'ephemeral'],
                ]],
                thinking: ['type' => 'adaptive'],
            );
        } catch (Throwable $e) {
            throw new RuntimeException('Anthropic request failed: '.$e->getMessage(), previous: $e);
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return new BotReply(
            text: trim($text),
            model: $message->model,
            tokensIn: $message->usage->inputTokens,
            tokensOut: $message->usage->outputTokens,
            stopReason: $message->stopReason ?? 'end_turn',
        );
    }
}
