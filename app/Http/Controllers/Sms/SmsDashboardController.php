<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsCampaign;
use App\Models\Sms\SmsMessage;
use App\Models\Sms\SmsSenderId;
use App\Models\Sms\SmsTemplate;
use App\Support\CurrentOrganization;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SmsDashboardController extends Controller
{
    public function index(Request $request, CurrentOrganization $currentOrg): View
    {
        $org = $currentOrg->resolve();

        $stats = [
            'wallet_balance' => (float) ($org?->sms_wallet_balance ?? 0.00),
            'rate_per_sms' => (float) ($org?->sms_rate_per_message ?? 0.25),
            'total_messages' => SmsMessage::query()->count(),
            'delivered' => SmsMessage::query()->where('status', 'delivered')->count(),
            'failed' => SmsMessage::query()->where('status', 'failed')->count(),
            'active_senders' => SmsSenderId::query()->where('status', 'active')->count(),
            'templates_count' => SmsTemplate::query()->where('status', 'approved')->count(),
            'campaigns_count' => SmsCampaign::query()->count(),
        ];

        $recentCampaigns = SmsCampaign::query()
            ->with(['sender', 'template'])
            ->latest()
            ->limit(5)
            ->get();

        $recentMessages = SmsMessage::query()
            ->with('contact')
            ->latest()
            ->limit(10)
            ->get();

        return view('sms.dashboard', [
            'organization' => $org,
            'stats' => $stats,
            'recentCampaigns' => $recentCampaigns,
            'recentMessages' => $recentMessages,
        ]);
    }
}
