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
