<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Whatsapp\UpdateWabaSettingsRequest;
use App\Jobs\SyncTemplatesJob;
use App\Models\WhatsappBusinessAccount;
use App\Services\WhatsApp\WabaConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WabaSettingsController extends Controller
{
    public function __construct(private readonly WabaConfigurationService $service) {}

    public function edit(Request $request): View
    {
        $account = $this->currentAccount();

        $this->authorize($account ? 'view' : 'viewAny', $account ?? WhatsappBusinessAccount::class);

        $boot = (array) config('services.whatsapp.bootstrap');

        return view('whatsapp.settings.edit', [
            'account' => $account,
            'checks' => $request->session()->get('connection_checks', []),
            // When nothing is configured yet, prefill the form from the .env
            // bootstrap values so the admin can review and save them.
            'prefill' => $account !== null ? null : [
                'waba_id' => (string) ($boot['business_account_id'] ?? ''),
                'meta_business_account_id' => (string) ($boot['meta_business_id'] ?? ''),
                'app_id' => (string) ($boot['app_id'] ?? ''),
                'has_token' => filled($boot['access_token'] ?? null),
                'has_app_secret' => filled($boot['app_secret'] ?? null),
                'has_verify_token' => filled($boot['webhook_verify_token'] ?? null),
            ],
        ]);
    }

    public function update(UpdateWabaSettingsRequest $request): RedirectResponse
    {
        $account = $this->currentAccount();
        $isNew = $account === null;
        $this->authorize($isNew ? 'create' : 'update', $account ?? WhatsappBusinessAccount::class);

        $account = $this->service->upsert($request->validated(), $account);

        $message = 'WhatsApp settings saved.';

        // First-time save: pull phone numbers + templates so the account is
        // usable immediately (same as `php artisan waba:setup`).
        if ($isNew && config('services.whatsapp.driver') === 'meta_cloud_api') {
            try {
                $n = $this->service->syncPhoneNumbers($account);
                SyncTemplatesJob::dispatchSync($account->getKey());
                $t = $account->templates()->count();
                $message = "Connected. Synced {$n} phone number(s) and {$t} template(s) from Meta.";
            } catch (\Throwable $e) {
                $message = 'Settings saved, but the initial sync failed: '.$e->getMessage();
            }
        }

        return redirect()
            ->route('whatsapp.settings.edit')
            ->with('flash_notify', ['type' => 'success', 'message' => $message]);
    }

    public function check(Request $request): RedirectResponse
    {
        $account = $this->currentAccount();

        abort_if($account === null, 404);
        $this->authorize('view', $account);

        $checks = $this->service->runConnectionChecks($account);

        $passed = collect($checks)->every(fn ($c) => $c->passed);

        return redirect()
            ->route('whatsapp.settings.edit')
            ->with('connection_checks', array_map(fn ($c) => $c->toArray(), $checks))
            ->with('flash_notify', [
                'type' => $passed ? 'success' : 'warning',
                'message' => $passed ? 'All connection checks passed.' : 'Some connection checks failed — see details below.',
            ]);
    }

    private function currentAccount(): ?WhatsappBusinessAccount
    {
        return WhatsappBusinessAccount::query()->orderBy('created_at')->first();
    }
}
