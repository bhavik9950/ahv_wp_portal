<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContactImport;
use App\Services\Contacts\ContactImportService;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CommitContactImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public string $importId) {}

    public function handle(ContactImportService $service, TenantContext $tenant): void
    {
        $import = ContactImport::query()->withoutGlobalScopes()->find($this->importId);
        if ($import === null) {
            return;
        }

        $tenant->set($import->organization()->firstOrFail());

        try {
            $service->commit($import);
        } catch (Throwable $e) {
            $import->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
            throw $e;
        }
    }
}
