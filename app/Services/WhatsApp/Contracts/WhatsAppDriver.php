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
     * Upload media to Meta for use as a template/message header.
     * POST /{phone_number_id}/media
     *
     * @return string the Meta media id
     */
    public function uploadMedia(WabaCredentials $creds, string $contents, string $mimeType, string $filename): string;

    /**
     * Upload a sample header file via the resumable upload API and return the
     * opaque file handle Meta needs in a media-header template's
     * `example.header_handle` when submitting it for review.
     * POST /{app_id}/uploads  →  POST /{upload_session_id}
     */
    public function uploadTemplateSample(WabaCredentials $creds, string $appId, string $contents, string $mimeType, string $filename): string;

    /**
     * Download an inbound media attachment: GET /{media-id} resolves a
     * short-lived, authenticated CDN URL, which is then fetched with the same
     * bearer token.
     *
     * @return array{contents: string, mime_type: string, sha256: ?string}
     */
    public function downloadMedia(WabaCredentials $creds, string $mediaId): array;

    /**
     * @return array<string, mixed> raw Meta phone number object
     */
    public function getPhoneNumber(WabaCredentials $creds, string $phoneNumberId): array;

    /**
     * The WhatsApp Business Calling settings for a number.
     * GET /{phone-number-id}/settings?fields=calling
     *
     * Returns the `calling` object ({status, call_icon_visibility, call_hours, …})
     * or [] when calling has never been provisioned for the number. Throws when
     * the account is not eligible for calling at all.
     *
     * @return array<string, mixed>
     */
    public function getCallingSettings(WabaCredentials $creds, string $phoneNumberId): array;

    /**
     * Turn WhatsApp Business Calling on or off for a number.
     * POST /{phone-number-id}/settings  { calling: { status: ENABLED|DISABLED } }
     */
    public function updateCallingStatus(WabaCredentials $creds, string $phoneNumberId, string $status): void;

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
