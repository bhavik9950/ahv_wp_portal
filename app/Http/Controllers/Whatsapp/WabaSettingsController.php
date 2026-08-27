<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Whatsapp\UpdateWabaSettingsRequest;
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

        return view('whatsapp.settings.edit', [
            'account' => $account,
            'checks' => $request->session()->get('connection_checks', []),
        ]);
    }

    public function update(UpdateWabaSettingsRequest $request): RedirectResponse
    {
        $account = $this->currentAccount();
        $this->authorize($account ? 'update' : 'create', $account ?? WhatsappBusinessAccount::class);

        $this->service->upsert($request->validated(), $account);

        return redirect()
            ->route('whatsapp.settings.edit')
            ->with('flash_notify', ['type' => 'success', 'message' => 'WhatsApp settings saved.']);
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
