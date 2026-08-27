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

    public function isMedia(): bool
    {
        return in_array($this, [self::Image, self::Video, self::Document, self::Audio], true);
    }
}
