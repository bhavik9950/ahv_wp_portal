<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Organization;

/**
 * Holds the active organization for the current request / job.
 *
 * Set by EnsureTenantContext middleware (web) or explicitly in queued jobs.
 * The BelongsToOrganization global scope reads from here.
 */
final class TenantContext
{
    private ?Organization $organization = null;

    private bool $bypassed = false;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
        $this->bypassed = false;
    }

    public function clear(): void
    {
        $this->organization = null;
        $this->bypassed = false;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function id(): ?int
    {
        return $this->organization?->getKey();
    }

    public function hasOrganization(): bool
    {
        return $this->organization !== null;
    }

    /**
     * Run a callback with the tenant scope disabled (super-admin / console only).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function withoutScope(callable $callback): mixed
    {
        $previous = $this->bypassed;
        $this->bypassed = true;

        try {
            return $callback();
        } finally {
            $this->bypassed = $previous;
        }
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }
}
