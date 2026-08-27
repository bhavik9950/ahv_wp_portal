<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use RuntimeException;

/**
 * Thrown when the global WHATSAPP_SENDING_ENABLED kill switch is off.
 * Send jobs catch this and requeue (or hold) rather than failing permanently.
 */
final class WhatsAppSendingDisabledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('WhatsApp sending is disabled by the global kill switch.');
    }
}
