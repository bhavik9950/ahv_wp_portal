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

        $templates = WhatsappTemplate::query()
            ->where('status', TemplateStatus::Approved->value)
            ->orderBy('name')
            ->get();

        return view('whatsapp.test-send.create', [
            'numbers' => WhatsappPhoneNumber::query()->orderByDesc('is_default')->get(),
            'templates' => $templates,
            // Structure + example data the preview panel reads (CSP-safe JSON blob).
            'templateData' => $templates->mapWithKeys(function (WhatsappTemplate $t): array {
                $examples = $t->bodyVariableExamples();

                return [$t->getKey() => [
                    'name' => $t->name,
                    'language' => $t->language,
                    'category' => $t->category,
                    'header' => $t->headerFormat() === null ? null : [
                        'format' => $t->headerFormat(),
                        'text' => $t->headerText(),
                    ],
                    'body' => $t->bodyText(),
                    'footer' => $t->footerText(),
                    'buttons' => $t->buttonLabels(),
                    'variables' => array_map(fn (int $n): array => [
                        'index' => $n,
                        'example' => $examples[$n] ?? null,
                    ], $t->variablePlaceholders()),
                ]];
            }),
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
                    array_map(
                        static fn ($v): string => (string) $v,
                        array_values((array) $request->validated('variables', [])),
                    ),
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

        $failed = collect($results)->reject(
            fn ($r) => in_array($r['status'], ['sent', 'queued', 'delivered', 'read'], true),
        )->count();

        return back()->with('test_send_results', $results)->with('flash_notify', [
            'type' => $failed === 0 ? 'success' : ($failed === count($results) ? 'error' : 'warning'),
            'message' => $failed === 0
                ? 'Test message sent to '.count($results).' number(s).'
                : "{$failed} of ".count($results).' test send(s) failed — see results below.',
        ]);
    }
}
