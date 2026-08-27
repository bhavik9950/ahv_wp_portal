<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Data;

use App\Models\WhatsappBusinessAccount;

/**
 * Resolved, decrypted credentials for one Graph API context. Kept out of logs.
 */
final readonly class WabaCredentials
{
    public function __construct(
        public string $accessToken,
        public string $wabaId,
        public ?string $phoneNumberId,
        public string $apiVersion,
        public string $baseUrl,
        public ?string $appSecret = null,
        public ?string $webhookVerifyToken = null,
    ) {}

    public static function fromModel(WhatsappBusinessAccount $account, ?string $phoneNumberId = null): self
    {
        return new self(
            accessToken: (string) $account->access_token,
            wabaId: (string) $account->waba_id,
            phoneNumberId: $phoneNumberId,
            apiVersion: $account->effectiveApiVersion(),
            baseUrl: (string) config('services.whatsapp.base_url'),
            appSecret: $account->app_secret ? (string) $account->app_secret : null,
            webhookVerifyToken: $account->webhook_verify_token ? (string) $account->webhook_verify_token : null,
        );
    }

    public static function fromBootstrapConfig(): self
    {
        $c = config('services.whatsapp');

        return new self(
            accessToken: (string) ($c['bootstrap']['access_token'] ?? ''),
            wabaId: (string) ($c['bootstrap']['business_account_id'] ?? ''),
            phoneNumberId: $c['bootstrap']['phone_number_id'] ?? null,
            apiVersion: (string) $c['api_version'],
            baseUrl: (string) $c['base_url'],
            appSecret: $c['bootstrap']['app_secret'] ?? null,
            webhookVerifyToken: $c['bootstrap']['webhook_verify_token'] ?? null,
        );
    }

    public function graphBase(): string
    {
        return rtrim($this->baseUrl, '/').'/'.trim($this->apiVersion, '/');
    }
}
