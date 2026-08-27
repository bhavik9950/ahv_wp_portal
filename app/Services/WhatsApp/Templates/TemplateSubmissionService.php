<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Templates;

use App\Enums\TemplateStatus;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

final class TemplateSubmissionService
{
    public function __construct(
        private readonly WhatsAppManager $manager,
        private readonly TemplateComposer $composer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated builder data
     */
    public function submit(WhatsappBusinessAccount $account, array $data): WhatsappTemplate
    {
        $errors = $this->composer->structuralErrors((string) ($data['body'] ?? ''));
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $components = $this->composer->toComponents($data);

        $definition = [
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => strtoupper((string) $data['category']),
            'components' => $components,
        ];

        $creds = $this->manager->credentialsFor($account);
        $response = $this->manager->driver()->createTemplate($creds, $definition);

        $template = WhatsappTemplate::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'whatsapp_business_account_id' => $account->getKey(),
                'name' => $data['name'],
                'language' => $data['language'],
            ],
            [
                'organization_id' => $account->organization_id,
                'category' => $definition['category'],
                'status' => TemplateStatus::fromMeta($response['status'] ?? 'PENDING')->value,
                'meta_template_id' => $response['id'] ?? null,
                'components' => $components,
                'raw_meta' => $response,
                'rejection_reason' => null,
                'created_by' => Auth::id(),
                'last_synced_at' => now(),
            ],
        );

        $this->audit->log('template.submitted', $template, [
            'name' => $template->name,
            'language' => $template->language,
            'category' => $template->category,
        ]);

        return $template;
    }

    public function delete(WhatsappTemplate $template): void
    {
        $account = $template->businessAccount()->first();

        if ($account instanceof WhatsappBusinessAccount) {
            $creds = $this->manager->credentialsFor($account);
            $this->manager->driver()->deleteTemplate($creds, $template->name);
        }

        $template->delete();

        $this->audit->log('template.deleted', $template, ['name' => $template->name]);
    }
}
