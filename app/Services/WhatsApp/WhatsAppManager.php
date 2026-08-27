<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Services\WhatsApp\Contracts\WhatsAppDriver;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\SendResult;
use App\Services\WhatsApp\Data\WabaCredentials;
use App\Services\System\SystemSettings;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

/**
 * Entry point for all WhatsApp operations. Resolves the configured driver and
 * the right (decrypted) credentials for a WABA / phone number, and enforces the
 * global sending kill switch.
 */
final class WhatsAppManager
{
    public function __construct(
        private readonly Container $container,
        private readonly SystemSettings $settings,
    ) {}

    public function driver(?string $name = null): WhatsAppDriver
    {
        $name ??= (string) config('services.whatsapp.driver', 'mock');

        return match ($name) {
            'mock' => $this->container->make(Drivers\MockWhatsAppDriver::class),
            'meta_cloud_api' => $this->container->make(Drivers\MetaCloudApiDriver::class),
            default => throw new RuntimeException("Unknown WhatsApp driver [{$name}]."),
        };
    }

    /**
     * Sending is ON only when BOTH the config default and the runtime override
     * are true. Either can be flipped to pause all outbound traffic.
     */
    public function sendingEnabled(): bool
    {
        return (bool) config('services.whatsapp.sending_enabled', true)
            && $this->settings->sendingEnabledOverride();
    }

    public function credentialsFor(WhatsappBusinessAccount $account, ?WhatsappPhoneNumber $phoneNumber = null): WabaCredentials
    {
        return WabaCredentials::fromModel($account, $phoneNumber?->phone_number_id);
    }

    /**
     * Send a message through a specific phone number. Honours the kill switch.
     */
    public function send(WhatsappPhoneNumber $phoneNumber, OutboundMessage $message): SendResult
    {
        if (! $this->sendingEnabled()) {
            throw new WhatsAppSendingDisabledException;
        }

        if (! $phoneNumber->isSendable()) {
            throw new RuntimeException('This WhatsApp phone number is disabled.');
        }

        $account = $phoneNumber->businessAccount()->first();

        if (! $account instanceof WhatsappBusinessAccount || ! $account->is_active) {
            throw new RuntimeException('WhatsApp Business Account is not active.');
        }

        $organization = $account->organization()->first();

        if ($organization !== null && $organization->isSuspended()) {
            throw new RuntimeException('This organization is suspended.');
        }

        $creds = $this->credentialsFor($account, $phoneNumber);

        return $this->driver()->send($creds, $message);
    }
}
