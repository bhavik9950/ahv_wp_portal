<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Templates;

use App\Enums\TemplateStatus;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Support\Facades\DB;

/**
 * Pulls message templates from Meta into local records. Meta's status string is
 * stored verbatim in `raw_meta` and normalised into `status`; unknown states are
 * tolerated (surfaced as UNKNOWN).
 */
final class TemplateSyncService
{
    public function __construct(
        private readonly WhatsAppManager $manager,
        private readonly AuditLogger $audit,
    ) {}

    public function sync(WhatsappBusinessAccount $account): int
    {
        $creds = $this->manager->credentialsFor($account);
        $remote = $this->manager->driver()->fetchTemplates($creds);

        $count = DB::transaction(function () use ($account, $remote): int {
            $n = 0;

            foreach ($remote as $raw) {
                $name = $raw['name'] ?? null;
                $language = $raw['language'] ?? ($raw['language']['code'] ?? null);

                if ($name === null || $language === null) {
                    continue;
                }

                $template = WhatsappTemplate::query()->withoutGlobalScopes()->firstOrNew([
                    'whatsapp_business_account_id' => $account->getKey(),
                    'name' => $name,
                    'language' => $language,
                ]);

                $template->forceFill([
                    'organization_id' => $account->organization_id,
                    'category' => $raw['category'] ?? $template->category,
                    'status' => TemplateStatus::fromMeta($raw['status'] ?? null)->value,
                    'meta_template_id' => $raw['id'] ?? $template->meta_template_id,
                    'components' => $raw['components'] ?? $template->components,
                    'raw_meta' => $raw,
                    'rejection_reason' => $raw['rejected_reason'] ?? $raw['rejection_reason'] ?? $template->rejection_reason,
                    'quality_score' => data_get($raw, 'quality_score.score', $template->quality_score),
                    'last_synced_at' => now(),
                ])->save();

                $n++;
            }

            return $n;
        });

        $this->audit->log('template.synced', $account, ['count' => $count]);

        return $count;
    }
}
