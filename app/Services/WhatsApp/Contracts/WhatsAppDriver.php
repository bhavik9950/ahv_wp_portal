<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Contracts;

use App\Services\WhatsApp\Data\ConnectionCheck;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\SendResult;
use App\Services\WhatsApp\Data\WabaCredentials;

/**
 * The only surface through which the application talks to WhatsApp. Two
 * implementations: MockWhatsAppDriver (offline) and MetaCloudApiDriver (real).
 */
interface WhatsAppDriver
{
    public function name(): string;

    public function send(WabaCredentials $creds, OutboundMessage $message): SendResult;

    /**
     * Fetch message templates for a WABA.
     *
     * @return array<int, array<string, mixed>> raw Meta template objects
     */
    public function fetchTemplates(WabaCredentials $creds): array;

    /**
     * Submit a new template to Meta for review.
     *
     * @param  array<string, mixed>  $definition  {name, language, category, components}
     * @return array<string, mixed> raw Meta response ({id, status, category})
     */
    public function createTemplate(WabaCredentials $creds, array $definition): array;

    public function deleteTemplate(WabaCredentials $creds, string $name): void;

    /**
     * @return array<string, mixed> raw Meta phone number object
     */
    public function getPhoneNumber(WabaCredentials $creds, string $phoneNumberId): array;

    /**
     * All phone numbers registered on the WABA.
     *
     * @return array<int, array<string, mixed>> raw Meta phone number objects
     */
    public function listPhoneNumbers(WabaCredentials $creds): array;

    /**
     * Run the connection validators for the WABA settings screen.
     *
     * @return list<ConnectionCheck>
     */
    public function runConnectionChecks(WabaCredentials $creds): array;
}
