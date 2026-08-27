<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use RuntimeException;

/**
 * A temporary Meta failure. The send job should let this bubble so Laravel
 * retries it with backoff.
 */
final class TransientSendException extends RuntimeException {}
