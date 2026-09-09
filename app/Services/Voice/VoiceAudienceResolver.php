<?php

declare(strict_types=1);

namespace App\Services\Voice;

use App\Enums\OptInStatus;
use App\Models\Contact;
use App\Models\VoiceCampaign;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns a voice campaign's `audience_filter` into a Contact query.
 *
 * Shape: { type: "all"|"groups"|"contacts", group_ids, contact_ids, exclude_group_ids }
 *
 * Opted-out contacts are always excluded — an opt-out covers every channel.
 */
final class VoiceAudienceResolver
{
    /** @return Builder<Contact> */
    public function query(VoiceCampaign $campaign): Builder
    {
        $filter = $campaign->audience_filter ?? ['type' => 'all'];
        $type = $filter['type'] ?? 'all';

        $query = Contact::query()
            ->whereNotNull('phone_e164')
            ->where('opt_in_status', '!=', OptInStatus::OptedOut->value);

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

    public function count(VoiceCampaign $campaign): int
    {
        return $this->query($campaign)->count();
    }
}
