<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsappBusinessAccount;
use App\Services\WhatsApp\Templates\TemplateSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTemplatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param string|null $accountId  null = sync every active WABA */
    public function __construct(public ?string $accountId = null) {}

    public function handle(TemplateSyncService $service): void
    {
        $accounts = WhatsappBusinessAccount::query()
            ->withoutGlobalScopes()
            ->when($this->accountId, fn ($q) => $q->whereKey($this->accountId))
            ->where('is_active', true)
            ->get();

        foreach ($accounts as $account) {
            $service->sync($account);
        }
    }
}
