<?php

declare(strict_types=1);

namespace App\Enums;

enum BotConversationMode: string
{
    case Bot = 'bot';       // the assistant replies automatically
    case Human = 'human';   // a person took over — bot stays silent
    case Paused = 'paused'; // temporarily muted for this contact

    public function botReplies(): bool
    {
        return $this === self::Bot;
    }
}
