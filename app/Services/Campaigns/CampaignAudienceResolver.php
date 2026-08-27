<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Enums\OptInStatus;
use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns a campaign's `audience_filter` into a Contact query.
 *
 * audience_filter shape:
 *   {
 *     "type": "all" | "groups" | "contacts",
 *     "group_ids": ["..."],
 *     "contact_ids": ["..."],
 *     "exclude_group_ids": ["..."],
 *     "opt_in": "opted_in" | "any"     // MARKETING templates always force opted_in
 *   }
 */
final class CampaignAudienceResolver
{
    /**
     * The eligible recipients — the raw selection with the consent filter applied.
     *
     * @return Builder<Contact>
     */
    public function query(Campaign $campaign): Builder
    {
        $filter = $campaign->audience_filter ?? ['type' => 'all'];
        $query = $this->rawSelectionQuery($campaign);

        // Consent: MARKETING is opt-in only, no exceptions.
        if ($this->requiresOptIn($campaign) || ($filter['opt_in'] ?? 'opted_in') === 'opted_in') {
            $query->where('opt_in_status', OptInStatus::OptedIn->value);
        } else {
            $query->where('opt_in_status', '!=', OptInStatus::OptedOut->value);
        }

        return $query;
    }

    /**
     * The raw selection (groups / explicit contacts / exclusions) WITHOUT the
     * consent filter. Materialisation uses this so opted-out contacts still
     * appear in the report as skipped, rather than silently vanishing.
     *
     * @return Builder<Contact>
     */
    public function rawSelectionQuery(Campaign $campaign): Builder
    {
        $filter = $campaign->audience_filter ?? ['type' => 'all'];
        $type = $filter['type'] ?? 'all';

        $query = Contact::query();

        if ($type === 'groups' && ! empty($filter['group_ids'])) {
            $query->whereHas('groups', fn ($g) => $g->whereKey($filter['group_ids']));
        } elseif ($type === 'contacts' && ! empty($filter['contact_ids'])) {
            $query->whereKey($filter['contact_ids']);
        }

        if (! empty($filter['exclude_group_ids'])) {
            $query->whereDoesntHave('groups', fn ($g) => $g->whereKey($filter['exclude_group_ids']));
        }

        return $query;
    }

    public function count(Campaign $campaign): int
    {
        return $this->query($campaign)->count();
    }

    /**
     * @return array{total: int, opted_in: int, excluded_opted_out: int}
     */
    public function summary(Campaign $campaign): array
    {
        $matched = $this->count($campaign);
        $rawSelection = (clone $this->rawSelectionQuery($campaign))->count();
        $optedOutInSelection = (clone $this->rawSelectionQuery($campaign))
            ->where('opt_in_status', OptInStatus::OptedOut->value)
            ->count();

        return [
            'total' => $matched,
            'opted_in' => $matched,
            'excluded_opted_out' => max(0, $this->requiresOptIn($campaign) ? $optedOutInSelection : ($rawSelection - $matched)),
        ];
    }

    public function requiresOptIn(Campaign $campaign): bool
    {
        $category = $campaign->template()->first()?->category;

        return strtoupper((string) $category) === 'MARKETING';
    }
}
