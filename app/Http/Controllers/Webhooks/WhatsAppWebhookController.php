<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\WhatsappBusinessAccount;
use App\Services\WhatsApp\Webhooks\WebhookSignature;
use App\Services\WhatsApp\Webhooks\WhatsAppWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(private readonly WhatsAppWebhookService $service) {}

    /**
     * GET — Meta subscription handshake. Echo hub.challenge iff the verify
     * token matches (constant-time).
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = (string) $request->query('hub_challenge', '');

        $expected = (string) ($this->resolveVerifyToken() ?? '');

        if ($mode === 'subscribe' && WebhookSignature::verifyTokenMatches($token, $expected)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('forbidden', 403);
    }

    /**
     * POST — receive events. Verifies signature, stores once, queues processing,
     * returns fast.
     */
    public function receive(Request $request): Response
    {
        $result = $this->service->ingest(
            $request->getContent(),
            $this->normalizeHeaders($request),
        );

        return response($result['body'], $result['status']);
    }

    /**
     * @return array<string, string>
     */
    private function normalizeHeaders(Request $request): array
    {
        $out = [];
        foreach ($request->headers->all() as $key => $values) {
            $out[strtolower($key)] = (string) ($values[0] ?? '');
        }

        return $out;
    }

    private function resolveVerifyToken(): ?string
    {
        $account = WhatsappBusinessAccount::query()->withoutGlobalScopes()
            ->whereNotNull('webhook_verify_token')
            ->first();

        if ($account !== null && filled($account->webhook_verify_token)) {
            return $account->webhook_verify_token;
        }

        return config('services.whatsapp.bootstrap.webhook_verify_token');
    }
}
