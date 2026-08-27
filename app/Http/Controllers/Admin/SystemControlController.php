<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\WhatsappBusinessAccount;
use App\Services\Audit\AuditLogger;
use App\Services\System\SystemSettings;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemControlController extends Controller
{
    public function __construct(
        private readonly SystemSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): View
    {
        return view('admin.controls', [
            'sendingEnabled' => $this->settings->sendingEnabledOverride(),
            'configالسSendingEnabled' => (bool) config('services.whatsapp.sending_enabled', true),
            'organization' => app(CurrentOrganization::class)->resolve(),
            'activeCampaigns' => Campaign::query()->where('status', CampaignStatus::Processing)->count(),
            'wabaAccounts' => WhatsappBusinessAccount::query()->get(),
        ]);
    }

    public function toggleSending(Request $request): RedirectResponse
    {
        $enable = $request->boolean('enable');
        $this->settings->setSendingEnabled($enable);

        $this->audit->log('system.sending_toggled', null, ['enabled' => $enable]);

        return back()->with('flash_notify', [
            'type' => $enable ? 'success' : 'warning',
            'message' => $enable ? 'Outbound WhatsApp sending re-enabled.' : 'Outbound WhatsApp sending disabled (kill switch).',
        ]);
    }

    public function pauseAllCampaigns(): RedirectResponse
    {
        $paused = Campaign::query()
            ->where('status', CampaignStatus::Processing)
            ->update(['status' => CampaignStatus::Paused]);

        $this->audit->log('system.campaigns_paused_all', null, ['count' => $paused]);

        return back()->with('flash_notify', [
            'type' => 'warning',
            'message' => "Paused {$paused} running campaign(s).",
        ]);
    }

    public function revokeIntegration(WhatsappBusinessAccount $account): RedirectResponse
    {
        $account->forceFill(['is_active' => false])->save();

        $this->audit->log('system.integration_revoked', $account, ['waba_id' => $account->waba_id]);

        return back()->with('flash_notify', [
            'type' => 'warning',
            'message' => "Integration for “{$account->name}” revoked. No messages will be sent through it.",
        ]);
    }
}
