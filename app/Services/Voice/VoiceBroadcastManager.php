<?php

declare(strict_types=1);

namespace App\Services\Voice;

use App\Services\Voice\Contracts\VoiceBroadcastDriver;
use App\Services\Voice\Data\VoiceCredentials;
use App\Services\Voice\Drivers\MockVoiceBroadcastDriver;
use App\Services\Voice\Drivers\SmsGatewayCenterVoiceDriver;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

final class VoiceBroadcastManager
{
    public function __construct(private readonly Container $container) {}

    public function driver(?string $name = null): VoiceBroadcastDriver
    {
        $name ??= (string) config('services.voice.driver', 'mock');

        return match ($name) {
            'mock' => $this->container->make(MockVoiceBroadcastDriver::class),
            'smsgatewaycenter' => $this->container->make(SmsGatewayCenterVoiceDriver::class),
            default => throw new RuntimeException("Unknown voice driver [{$name}]."),
        };
    }

    public function credentials(): VoiceCredentials
    {
        return VoiceCredentials::fromConfig();
    }

    public function isConfigured(): bool
    {
        return config('services.voice.driver') === 'mock' || $this->credentials()->isComplete();
    }
}
