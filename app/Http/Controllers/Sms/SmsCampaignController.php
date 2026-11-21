<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\ContactGroup;
use App\Models\Sms\SmsCampaign;
use App\Models\Sms\SmsMessage;
use App\Models\Sms\SmsSenderId;
use App\Models\Sms\SmsTemplate;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class SmsCampaignController extends Controller
{
    public function index(CurrentOrganization $currentOrg): View
    {
        $org = $currentOrg->resolve();

        $campaigns = SmsCampaign::query()
            ->with(['sender', 'template'])
            ->latest()
            ->paginate(15);

        return view('sms.campaigns.index', [
            'organization' => $org,
            'campaigns' => $campaigns,
        ]);
    }

    public function create(CurrentOrganization $currentOrg): View
    {
        $org = $currentOrg->resolve();

        $senders = SmsSenderId::query()->where('status', 'active')->orderByDesc('is_default')->get();
        $templates = SmsTemplate::query()->where('status', 'approved')->orderBy('name')->get();
        $groups = ContactGroup::query()->withCount('contacts')->orderBy('name')->get();

        return view('sms.campaigns.create', [
            'organization' => $org,
            'senders' => $senders,
            'templates' => $templates,
            'groups' => $groups,
        ]);
    }

    public function store(Request $request, CurrentOrganization $currentOrg): RedirectResponse
    {
        $org = $currentOrg->resolve();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sms_sender_id' => ['required', 'exists:sms_sender_ids,id'],
            'sms_template_id' => ['required', 'exists:sms_templates,id'],
            'contact_group_id' => ['nullable', 'exists:contact_groups,id'],
            'manual_numbers' => ['nullable', 'string'],
        ]);

        $sender = SmsSenderId::findOrFail($validated['sms_sender_id']);
        $template = SmsTemplate::findOrFail($validated['sms_template_id']);

        // Gather recipient numbers
        $numbers = [];
        if (! empty($validated['contact_group_id'])) {
            $group = ContactGroup::with('contacts')->findOrFail($validated['contact_group_id']);
            foreach ($group->contacts as $c) {
                if (filled($c->phone_e164)) {
                    $numbers[] = $c->phone_e164;
                }
            }
        }

        if (filled($validated['manual_numbers'])) {
            $extra = array_filter(array_map('trim', explode(',', $validated['manual_numbers'])));
            $numbers = array_merge($numbers, $extra);
        }

        $numbers = array_unique(array_filter($numbers));
        $count = count($numbers);

        if ($count === 0) {
            return back()->withInput()->with('flash_notify', [
                'type' => 'error',
                'message' => 'Please select a contact group with phone numbers or enter manual numbers.',
            ]);
        }

        $ratePerSms = (float) ($org->sms_rate_per_message ?? 0.25);
        $totalCost = $count * $ratePerSms;
        $balance = (float) ($org->sms_wallet_balance ?? 0.00);

        if ($balance < $totalCost) {
            return back()->withInput()->with('flash_notify', [
                'type' => 'error',
                'message' => "Insufficient wallet balance (₹" . number_format($balance, 2) . ") for {$count} SMS. Required: ₹" . number_format($totalCost, 2) . ". Please recharge wallet.",
            ]);
        }

        DB::transaction(function () use ($org, $sender, $template, $validated, $count, $totalCost, $numbers, $ratePerSms) {
            $campaign = SmsCampaign::create([
                'organization_id' => $org->id,
                'sms_sender_id' => $sender->id,
                'sms_template_id' => $template->id,
                'name' => $validated['name'],
                'message_body' => $template->body,
                'audience_count' => $count,
                'sent_count' => $count,
                'delivered_count' => $count, // in sandbox; real counts updated by webhook
                'total_cost' => $totalCost,
                'status' => 'completed',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            // Queue records
            foreach ($numbers as $phone) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                SmsMessage::create([
                    'organization_id' => $org->id,
                    'sms_campaign_id' => $campaign->id,
                    'to_phone' => $cleanPhone,
                    'sender_id' => $sender->sender_id,
                    'dlt_template_id' => $template->dlt_template_id,
                    'message_body' => $template->body,
                    'cost' => $ratePerSms,
                    'provider_cost' => 0.1300,
                    'status' => 'delivered',
                    'sent_at' => now(),
                    'delivered_at' => now(),
                ]);
            }

            $org->decrement('sms_wallet_balance', $totalCost);
        });

        return redirect()->route('sms.campaigns.index')->with('flash_notify', [
            'type' => 'success',
            'message' => "SMS Campaign '{$validated['name']}' with {$count} recipients launched successfully.",
        ]);
    }

    public function show(SmsCampaign $campaign): View
    {
        $campaign->load(['sender', 'template']);
        $messages = $campaign->messages()->with('contact')->latest()->paginate(25);

        return view('sms.campaigns.show', [
            'campaign' => $campaign,
            'messages' => $messages,
        ]);
    }
}
