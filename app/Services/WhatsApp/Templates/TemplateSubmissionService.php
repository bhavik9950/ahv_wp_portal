<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Templates;

use App\Enums\TemplateStatus;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\UploadedFile;
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
    public function submit(WhatsappBusinessAccount $account, array $data, ?UploadedFile $sample = null): WhatsappTemplate
    {
        $errors = $this->composer->structuralErrors((string) ($data['body'] ?? ''));
        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        // Meta rejects a create for a name+language that already exists (even in
        // PENDING) — surface that clearly instead of a generic 400.
        $existing = WhatsappTemplate::query()->withoutGlobalScopes()
            ->where('whatsapp_business_account_id', $account->getKey())
            ->where('name', $data['name'])
            ->where('language', $data['language'])
            ->first();

        if ($existing !== null && $existing->statusEnum() !== TemplateStatus::Rejected) {
            throw new RuntimeException(sprintf(
                'A template named "%s" (%s) already exists with status %s. Delete it first, then resubmit.',
                $data['name'],
                $data['language'],
                $existing->status,
            ));
        }

        $creds = $this->manager->credentialsFor($account);

        // A media header needs a sample file uploaded to Meta first; the returned
        // handle goes into the template's example.header_handle.
        if ($sample !== null && in_array($data['header_type'] ?? 'none', ['image', 'video', 'document'], true)) {
            $data['header_handle'] = $this->manager->driver()->uploadTemplateSample(
                $creds,
                (string) $account->app_id,
                (string) $sample->get(),
                $sample->getMimeType() ?: 'application/octet-stream',
                $sample->getClientOriginalName() ?: 'sample',
            );
        }

        $components = $this->composer->toComponents($data);

        $definition = [
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => strtoupper((string) $data['category']),
            'components' => $components,
        ];

        $response = $this->manager->driver()->createTemplate($creds, $definition);

        $template = WhatsappTemplate::query()->withoutGlobalScopes()->firstOrNew([
            'whatsapp_business_account_id' => $account->getKey(),
            'name' => $data['name'],
            'language' => $data['language'],
        ]);

        $template->forceFill([
            'organization_id' => $account->organization_id,
            'category' => $definition['category'],
            'status' => TemplateStatus::fromMeta($response['status'] ?? 'PENDING')->value,
            'meta_template_id' => $response['id'] ?? null,
            'components' => $components,
            'raw_meta' => $response,
            'rejection_reason' => null,
            'created_by' => Auth::id(),
            'last_synced_at' => now(),
        ])->save();

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
