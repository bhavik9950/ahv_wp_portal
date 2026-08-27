<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Roles assignable within an organization (spatie teams). `super_admin` is a
 * separate, platform-wide flag on the user (`is_super_admin`), not a team role.
 */
enum OrganizationRole: string
{
    case OrgAdmin = 'org_admin';
    case CampaignManager = 'campaign_manager';
    case SupportAgent = 'support_agent';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::OrgAdmin => 'Organization Admin',
            self::CampaignManager => 'Campaign Manager',
            self::SupportAgent => 'Support Agent',
            self::Viewer => 'Viewer',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
