<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Support;

final readonly class DownloadedFile
{
    public function __construct(
        public string $contents,
        public string $mimeType,
        public int $sizeBytes,
    ) {}
}
