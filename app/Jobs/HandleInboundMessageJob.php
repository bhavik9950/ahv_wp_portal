<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\BindsTenant;
use App\Models\BotMessage;
use App\Models\Message;
use App\Models\WhatsappPhoneNumber;
use App\Services\Bot\BotService;
use App\Services\Bot\Contracts\LlmClient;
use App\Services\Bot\PromptBuilder;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\OutboundMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The AI assistant's turn: for one inbound customer message, decide whether to
 * reply, generate a reply with the LLM, and send it back through the normal
 * outbound pipeline (free text, inside Meta's 24h service window).
 *
 * Never runs for outbound messages; bails on every gate (bot off, human mode,
 * daily cap, blank text). One try — a failed LLM call sends the fallback line.
 */
class HandleInboundMessageJob implements ShouldBeUnique, ShouldQueue
{
    use BindsTenant, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public string $messageId) {}

    public function uniqueId(): string
    {
        return $this->messageId;
    }

    public function handle(BotService $bot, PromptBuilder $prompt, LlmClient $llm, OutboundMessageService $sender): void
    {
        if (! config('services.bot.enabled')) {
            return;
        }

        $message = Message::query()->withoutGlobalScopes()->with('phoneNumber')->find($this->messageId);
        if ($message === null || $message->direction !== 'inbound') {
            return;
        }

        $this->bindTenant($message->organization_id);

        $settings = $bot->settingsFor($message->organization_id);
        if ($settings === null || ! $settings->enabled) {
            return;
        }

        $text = trim((string) $message->bodyText());
        if ($text === '') {
            return; // MVP: text only
        }

        $conversation = $bot->conversationFor($message);
        $conversation->forceFill(['last_inbound_at' => now()])->save();

        if (! $conversation->mode->botReplies()) {
            return; // a human is handling this thread
        }

        if (($keyword = $bot->matchedHandoffKeyword($settings, $text)) !== null) {
            $bot->handoff($conversation, "keyword: {$keyword}");

            return;
        }

        $cap = min($settings->daily_reply_cap, (int) config('services.bot.daily_reply_cap', 40));
        if ($conversation->repliesUsedToday() >= $cap) {
            Log::channel((string) config('services.whatsapp.log_channel'))
                ->info('Bot daily cap reached', ['conversation' => $conversation->getKey()]);

            return;
        }

        $model = $settings->model ?: (string) config('services.bot.model', 'claude-opus-5');
        $messages = $prompt->messages($conversation, $text, (int) config('services.bot.history_turns', 12));

        try {
            $reply = $llm->reply(
                $prompt->system($settings),
                $messages,
                $model,
                (int) config('services.bot.max_output_tokens', 1024),
                (string) config('services.bot.effort', 'low'),
            );
            $replyText = $reply->isRefusal() ? '' : trim($reply->text);
        } catch (Throwable $e) {
            report($e);
            $replyText = '';
            $reply = null;
        }

        // Always record what the customer sent.
        BotMessage::query()->forceCreate([
            'organization_id' => $message->organization_id,
            'bot_conversation_id' => $conversation->getKey(),
            'message_id' => $message->getKey(),
            'role' => 'user',
            'content' => $text,
        ]);

        if ($replyText === '') {
            $replyText = $settings->fallback_message;
        }

        BotMessage::query()->forceCreate([
            'organization_id' => $message->organization_id,
            'bot_conversation_id' => $conversation->getKey(),
            'role' => 'assistant',
            'content' => $replyText,
            'model' => $reply?->model,
            'tokens_in' => $reply?->tokensIn,
            'tokens_out' => $reply?->tokensOut,
        ]);

        $phoneNumber = $message->phoneNumber;
        if (! $phoneNumber instanceof WhatsappPhoneNumber) {
            return;
        }

        $sender->send(
            $phoneNumber,
            OutboundMessage::text(new Recipient((string) $message->to_phone), $replyText),
            [
                'idempotency_key' => 'bot:'.$message->getKey(),
                'contact_id' => $conversation->contact_id,
            ],
        );

        $conversation->recordBotReply();
    }
}
