<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case Template = 'template';
    case Image = 'image';
    case Video = 'video';
    case Document = 'document';
    case Audio = 'audio';
    case Location = 'location';
    case Interactive = 'interactive';
    case Contacts = 'contacts';
    case Sticker = 'sticker';
    case Reaction = 'reaction';
    case Button = 'button';
    case Order = 'order';
    case System = 'system';
    case Unsupported = 'unsupported';
    case Unknown = 'unknown';

    public function isMedia(): bool
    {
        return in_array($this, [self::Image, self::Video, self::Document, self::Audio, self::Sticker], true);
    }
}
