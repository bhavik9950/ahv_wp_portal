<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Enums\TemplateStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Whatsapp\SendTestMessageRequest;
use App\Models\Message;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\OutboundMessageService;
use App\Services\WhatsApp\RateLimitedException;
use App\Services\WhatsApp\WhatsAppSendingDisabledException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TestSendController extends Controller
{
    public function __construct(
        private readonly OutboundMessageService $sender,
    ) {}

    public function create(): View
    {
        $this->authorize('create', Message::class);

        return view('whatsapp.test-send.create', [
            'numbers' => WhatsappPhoneNumber::query()->orderByDesc('is_default')->get(),
            'templates' => WhatsappTemplate::query()->where('status', TemplateStatus::Approved->value)->orderBy('name')->get(),
        ]);
    }

    public function store(SendTestMessageRequest $request): RedirectResponse
    {
        $this->authorize('create', Message::class);

        /** @var WhatsappPhoneNumber $number */
        $number = WhatsappPhoneNumber::query()->findOrFail($request->validated('whatsapp_phone_number_id'));

        $template = $request->filled('template_id')
            ? WhatsappTemplate::query()->findOrFail($request->string('template_id'))
            : null;

        if ($template !== null && ! $template->isSendable()) {
            return back()->withErrors(['template_id' => 'Only APPROVED templates can be sent.'])->withInput();
        }

        $results = [];

        foreach ($request->validated('recipients') as $phone) {
            $recipient = new Recipient($phone);

            $outbound = $template !== null
                ? OutboundMessage::templateWithParams(
                    $recipient,
                    $template->name,
                    $template->language,
                    array_values($request->validated('variables', [])),
                )
                : OutboundMessage::text($recipient, (string) $request->validated('body'));

            try {
                $message = $this->sender->send($number, $outbound, [
                    'idempotency_key' => 'test:'.Str::ulid(),
                    'template_id' => $template?->getKey(),
                    'is_test' => true,
                ]);
                $results[] = ['phone' => $phone, 'status' => $message->status->value, 'id' => $message->getKey()];
            } catch (WhatsAppSendingDisabledException) {
                $results[] = ['phone' => $phone, 'status' => 'blocked', 'error' => 'Sending is disabled (kill switch).'];
            } catch (RateLimitedException $e) {
                $results[] = ['phone' => $phone, 'status' => 'rate_limited', 'error' => "Retry after {$e->retryAfterSeconds}s."];
            } catch (\Throwable $e) {
                $results[] = ['phone' => $phone, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        return back()->with('test_send_results', $results)->with('flash_notify', [
            'type' => 'info',
            'message' => 'Test send processed for '.count($results).' number(s).',
        ]);
    }
}
