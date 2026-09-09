<?php

declare(strict_types=1);

namespace App\Services\Bot;

use App\Enums\BotConversationMode;
use App\Models\BotConversation;
use App\Models\BotSetting;
use App\Models\Contact;
use App\Models\Message;
use App\Services\Audit\AuditLogger;

final class BotService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function settingsFor(int $organizationId): ?BotSetting
    {
        return BotSetting::query()->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->first();
    }

    /** The org's row, created with defaults if it doesn't exist yet. */
    public function settingsOrCreate(int $organizationId): BotSetting
    {
        return $this->settingsFor($organizationId) ?? BotSetting::query()->withoutGlobalScopes()->forceCreate([
            'organization_id' => $organizationId,
            'enabled' => false,
            'system_prompt' => BotSetting::DEFAULT_SYSTEM_PROMPT,
            'daily_reply_cap' => 40,
            'handoff_keywords' => BotSetting::DEFAULT_HANDOFF_KEYWORDS,
            'fallback_message' => BotSetting::DEFAULT_FALLBACK,
        ]);
    }

    public function conversationFor(Message $message): BotConversation
    {
        $phone = (string) $message->to_phone;

        $contact = Contact::query()->withoutGlobalScopes()
            ->where('organization_id', $message->organization_id)
            ->where('phone_e164', $phone)
            ->first();

        $conversation = BotConversation::query()->withoutGlobalScopes()
            ->where('organization_id', $message->organization_id)
            ->where('wa_phone', $phone)
            ->first()
            ?? BotConversation::query()->withoutGlobalScopes()->forceCreate([
                'organization_id' => $message->organization_id,
                'wa_phone' => $phone,
                'contact_id' => $contact?->getKey(),
                'wa_phone_hash' => hash('sha256', $phone),
                'mode' => BotConversationMode::Bot->value,
            ]);

        if ($contact !== null && $conversation->contact_id === null) {
            $conversation->forceFill(['contact_id' => $contact->getKey()])->save();
        }

        return $conversation;
    }

    /** The first handoff keyword found in the text, or null. */
    public function matchedHandoffKeyword(BotSetting $settings, string $text): ?string
    {
        $haystack = mb_strtolower($text);

        foreach ($settings->handoff_keywords ?? BotSetting::DEFAULT_HANDOFF_KEYWORDS as $keyword) {
            $needle = mb_strtolower(trim((string) $keyword));
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return $needle;
            }
        }

        return null;
    }

    public function handoff(BotConversation $conversation, string $reason): void
    {
        $conversation->forceFill([
            'mode' => BotConversationMode::Human->value,
            'handoff_reason' => mb_substr($reason, 0, 190),
            'handoff_at' => now(),
        ])->save();

        $this->audit->log('bot.handoff', $conversation, ['reason' => $reason]);
    }
}
