<?php

declare(strict_types=1);

namespace App\Services\Contacts;

use RuntimeException;

final class DuplicateContactException extends RuntimeException
{
    public function __construct(public readonly string $phoneE164)
    {
        parent::__construct("A contact with the number ending {$this->last4()} already exists.");
    }

    private function last4(): string
    {
        return substr($this->phoneE164, -4);
    }
}
