<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Organization;

/**
 * Resolves the active organization.
 *
 * - single mode (default): the configured org, or the sole/oldest one.
 * - multi mode: falls back to a caller-supplied id (e.g. from the session).
 */
final class CurrentOrganization
{
    private ?Organization $cached = null;

    public function isSingleTenant(): bool
    {
        return config('tenant.mode', 'multi') === 'single';
    }

    public function resolve(int|string|null $preferredId = null): ?Organization
    {
        if ($this->cached !== null && ($preferredId === null || (string) $this->cached->getKey() === (string) $preferredId)) {
            return $this->cached;
        }

        $query = Organization::query()->withoutGlobalScopes();

        if ($this->isSingleTenant()) {
            $configured = config('tenant.organization_id');

            $org = $configured
                ? (clone $query)->whereKey($configured)->first()
                : (clone $query)->orderBy('id')->first();
        } else {
            $org = $preferredId
                ? (clone $query)->whereKey($preferredId)->first()
                : (clone $query)->orderBy('id')->first();
        }

        return $this->cached = $org;
    }

    public function forget(): void
    {
        $this->cached = null;
    }
}
