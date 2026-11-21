<?php

declare(strict_types=1);

use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
 | Meta WhatsApp Cloud API webhook. No session, no CSRF, no auth — authenticity
 | is established by the verify token (GET) and the X-Hub-Signature-256 HMAC
 | (POST). A generous rate limit guards against floods.
 */
Route::prefix('webhooks/whatsapp')->middleware('throttle:whatsapp-webhook')->group(function () {
    Route::get('/', [WhatsAppWebhookController::class, 'verify']);
    Route::post('/', [WhatsAppWebhookController::class, 'receive']);
});

/*
 | Wholesale SMS DLR Webhook (e.g. MSG91, Route Mobile).
 | Updates SMS delivery status (delivered/failed) asynchronously.
 */
Route::post('webhooks/sms/dlr', function (\Illuminate\Http\Request $request) {
    $requestId = $request->input('requestId') ?? $request->input('request_id');
    $status = strtolower((string) ($request->input('status') ?? ''));

    if (filled($requestId)) {
        $mappedStatus = match ($status) {
            'delivered', 'success', '1' => 'delivered',
            'failed', 'rejected', 'undelivered', '2', '16' => 'failed',
            default => 'sent',
        };

        \App\Models\Sms\SmsMessage::query()
            ->withoutGlobalScopes()
            ->where('provider_message_id', (string) $requestId)
            ->update([
                'status' => $mappedStatus,
                'delivered_at' => $mappedStatus === 'delivered' ? now() : null,
                'failed_at' => $mappedStatus === 'failed' ? now() : null,
            ]);
    }

    return response()->json(['status' => 'ok']);
});
