<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Sms\SmsMessage;
use App\Models\Sms\SmsSenderId;
use App\Models\Sms\SmsTemplate;
use App\Services\Sms\Msg91SmsGateway;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class QuickSmsController extends Controller
{
    public function create(CurrentOrganization $currentOrg): View
    {
        $org = $currentOrg->resolve();

        $senders = SmsSenderId::query()->where('status', 'active')->orderByDesc('is_default')->get();
        $templates = SmsTemplate::query()->where('status', 'approved')->orderBy('name')->get();

        return view('sms.quick.create', [
            'organization' => $org,
            'senders' => $senders,
            'templates' => $templates,
        ]);
    }

    public function send(Request $request, CurrentOrganization $currentOrg, Msg91SmsGateway $gateway): RedirectResponse
    {
        $org = $currentOrg->resolve();

        $validated = $request->validate([
            'to_phone' => ['required', 'string'],
            'sms_sender_id' => ['required', 'exists:sms_sender_ids,id'],
            'sms_template_id' => ['nullable', 'exists:sms_templates,id'],
            'message_body' => ['required', 'string', 'max:1000'],
        ]);

        $ratePerSms = (float) ($org->sms_rate_per_message ?? 0.25);
        $walletBalance = (float) ($org->sms_wallet_balance ?? 0.00);

        if ($walletBalance < $ratePerSms) {
            return back()->withInput()->with('flash_notify', [
                'type' => 'error',
                'message' => "Insufficient SMS Wallet Balance (Current: ₹" . number_format($walletBalance, 2) . "). Required: ₹" . number_format($ratePerSms, 2) . ". Please recharge your wallet.",
            ]);
        }

        $sender = SmsSenderId::findOrFail($validated['sms_sender_id']);
        $template = ! empty($validated['sms_template_id']) ? SmsTemplate::find($validated['sms_template_id']) : null;
        $dltTemplateId = $template?->dlt_template_id ?? 'DLT_DIRECT';

        $phoneNumbers = array_filter(array_map('trim', explode(',', $validated['to_phone'])));
        $totalCost = count($phoneNumbers) * $ratePerSms;

        if ($walletBalance < $totalCost) {
            return back()->withInput()->with('flash_notify', [
                'type' => 'error',
                'message' => "Insufficient balance for " . count($phoneNumbers) . " numbers. Total required: ₹" . number_format($totalCost, 2) . ", Balance: ₹" . number_format($walletBalance, 2),
            ]);
        }

        $sentCount = 0;
        $failedCount = 0;

        DB::transaction(function () use ($phoneNumbers, $sender, $dltTemplateId, $validated, $ratePerSms, $org, $gateway, &$sentCount, &$failedCount) {
            foreach ($phoneNumbers as $phone) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                if (empty($cleanPhone)) continue;

                $res = $gateway->send($cleanPhone, $sender->sender_id, $dltTemplateId, $validated['message_body']);

                $contact = Contact::query()->withoutGlobalScopes()
                    ->where('organization_id', $org->id)
                    ->where(function ($q) use ($cleanPhone) {
                        $q->where('phone_e164', '+' . $cleanPhone)
                            ->orWhere('phone_e164', $cleanPhone);
                    })->first();

                SmsMessage::create([
                    'organization_id' => $org->id,
                    'contact_id' => $contact?->id,
                    'to_phone' => $cleanPhone,
                    'sender_id' => $sender->sender_id,
                    'dlt_template_id' => $dltTemplateId,
                    'message_body' => $validated['message_body'],
                    'cost' => $ratePerSms,
                    'provider_cost' => 0.1300,
                    'status' => $res['success'] ? 'sent' : 'failed',
                    'error_message' => $res['error'],
                    'provider_message_id' => $res['message_id'],
                    'sent_at' => $res['success'] ? now() : null,
                    'failed_at' => $res['success'] ? null : now(),
                ]);

                if ($res['success']) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }

            // Deduct wallet for sent count
            if ($sentCount > 0) {
                $deduct = $sentCount * $ratePerSms;
                $org->decrement('sms_wallet_balance', $deduct);
            }
        });

        $msg = "Dispatched {$sentCount} SMS successfully.";
        if ($failedCount > 0) {
            $msg .= " ({$failedCount} failed)";
        }

        return redirect()->route('sms.dashboard')->with('flash_notify', [
            'type' => $sentCount > 0 ? 'success' : 'error',
            'message' => $msg,
        ]);
    }
}
