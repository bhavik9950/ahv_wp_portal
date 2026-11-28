<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Enums\MessageStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Message;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\InboundMediaFetcher;
use App\Services\WhatsApp\MediaLibrary;
use App\Services\WhatsApp\OutboundMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Message::class);

        $limit = 1000;
        $search = trim((string) $request->query('q', ''));

        $query = Message::query()
            ->with(['contact', 'template']);

        if ($search !== '') {
            $digits = preg_replace('/\D+/', '', $search) ?: '';
            $query->where(function ($q) use ($search, $digits): void {
                $q->where('to_phone', 'like', "%{$search}%")
                    ->orWhere('wamid', 'like', "%{$search}%")
                    ->orWhere('error_code', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%")
                    ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                if ($digits !== '') {
                    $q->orWhere('to_phone', 'like', "%{$digits}%");
                }
            });
        }

        $messages = $query
            ->latest()
            ->limit($limit + 1)
            ->get();

        // Search / filter / sort are handled client-side by DataTables.
        return view('whatsapp.messages.index', [
            'messages' => $messages->take($limit),
            'capped' => $messages->count() > $limit,
            'limit' => $limit,
            'search' => $search,
        ]);
    }

    public function show(Message $message): View
    {
        $this->authorize('view', $message);

        $message->load(['statusEvents', 'contact', 'template', 'phoneNumber']);

        return view('whatsapp.messages.show', [
            'message' => $message,
        ]);
    }

    /** WhatsApp-style inbox: one row per phone number, newest conversation first. */
    public function conversations(Request $request): View
    {
        $this->authorize('viewAny', Message::class);

        $filter = (string) $request->query('filter', 'all');
        $search = trim((string) $request->query('q', ''));

        // All phones with at least one customer reply (inbound)
        $inboundPhones = Message::query()
            ->where('direction', 'inbound')
            ->distinct()
            ->pluck('to_phone')
            ->all();

        // Latest inbound timestamps for 24-hr window detection
        $latestInboundDates = Message::query()
            ->where('direction', 'inbound')
            ->select('to_phone', DB::raw('MAX(created_at) as last_inbound_at'))
            ->groupBy('to_phone')
            ->pluck('last_inbound_at', 'to_phone')
            ->all();

        // Phones with active 24-hr customer service window
        $activeWindowCutoff = now()->subHours(24);
        $activeWindowPhones = array_keys(array_filter(
            $latestInboundDates,
            fn ($date) => \Illuminate\Support\Carbon::parse($date)->gte($activeWindowCutoff)
        ));

        // Get latest message ID for each phone thread
        $allLatestIds = Message::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('to_phone')
            ->pluck('id');

        // Quick snapshot of latest messages for counting and filtering
        $latestSnapshots = Message::query()
            ->whereIn('id', $allLatestIds)
            ->get(['id', 'to_phone', 'direction']);

        $needsReplyIds = $latestSnapshots->where('direction', 'inbound')->pluck('id');
        $inboundIds = $latestSnapshots->whereIn('to_phone', $inboundPhones)->pluck('id');
        $activeWindowIds = $latestSnapshots->whereIn('to_phone', $activeWindowPhones)->pluck('id');
        $outboundOnlyIds = $latestSnapshots->whereNotIn('to_phone', $inboundPhones)->pluck('id');

        // Determine which IDs to load based on active filter
        $targetIds = match ($filter) {
            'needs_reply' => $needsReplyIds,
            'inbound' => $inboundIds,
            'active_window' => $activeWindowIds,
            'outbound' => $outboundOnlyIds,
            default => $allLatestIds,
        };

        // Query threads
        $threadsQuery = Message::query()
            ->with(['contact', 'template'])
            ->whereIn('id', $targetIds);

        if ($search !== '') {
            $cleanSearch = ltrim($search, '+');
            $threadsQuery->where(function ($q) use ($cleanSearch, $search) {
                $q->where('to_phone', 'like', "%{$cleanSearch}%")
                    ->orWhereHas('contact', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('payload', 'like', "%{$search}%");
            });
        }

        $threads = $threadsQuery
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $counts = Message::query()
            ->select('to_phone', DB::raw('count(*) as message_count'))
            ->groupBy('to_phone')
            ->pluck('message_count', 'to_phone');

        // Tab count badges
        $tabCounts = [
            'all' => $allLatestIds->count(),
            'needs_reply' => $needsReplyIds->count(),
            'inbound' => $inboundIds->count(),
            'active_window' => $activeWindowIds->count(),
        ];

        return view('whatsapp.conversations.index', [
            'threads' => $threads,
            'counts' => $counts,
            'filter' => $filter,
            'search' => $search,
            'tabCounts' => $tabCounts,
            'latestInboundDates' => $latestInboundDates,
            'inboundPhones' => array_flip($inboundPhones),
            'activeWindowPhones' => array_flip($activeWindowPhones),
        ]);
    }

    /** One phone number's full history rendered as a WhatsApp-style chat thread. */
    public function conversation(string $phone, MediaLibrary $mediaLibrary, InboundMediaFetcher $mediaFetcher): View
    {
        $this->authorize('viewAny', Message::class);

        $cleanPhone = ltrim($phone, '+');

        $messages = Message::query()
            ->where(function ($q) use ($cleanPhone, $phone) {
                $q->where('to_phone', $cleanPhone)->orWhere('to_phone', $phone);
            })
            ->with([
                'media' => fn ($q) => $q->withoutGlobalScopes(),
                'contact',
                'template',
                'phoneNumber',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        abort_if($messages->isEmpty(), 404);

        $failedMediaIds = [];
        $mediaUrls = $messages->mapWithKeys(function (Message $m) use ($mediaLibrary, $mediaFetcher, &$failedMediaIds): array {
            $media = $m->media;

            if ($media === null && $m->hasDownloadableMedia()) {
                try {
                    $media = $mediaFetcher->fetch($m);
                } catch (\Throwable $e) {
                    report($e);
                }

                if (! $media instanceof Media) {
                    $failedMediaIds[$m->getKey()] = true;
                }
            }

            return $media instanceof Media ? [$m->getKey() => $mediaLibrary->temporaryUrl($media)] : [];
        });

        $latestInbound = $messages->where('direction', 'inbound')->last();
        $windowExpiresAt = $latestInbound?->created_at?->addHours(24);
        $isWindowActive = $windowExpiresAt !== null && $windowExpiresAt->isFuture();

        $phoneNumber = $messages->last()?->phoneNumber
            ?? WhatsappPhoneNumber::query()->where('is_default', true)->first()
            ?? WhatsappPhoneNumber::query()->first();

        $approvedTemplates = WhatsappTemplate::query()
            ->where('status', 'APPROVED')
            ->orderBy('name')
            ->get();

        return view('whatsapp.conversations.show', [
            'phone' => $cleanPhone,
            'contact' => $messages->last(fn (Message $m) => $m->contact !== null)?->contact,
            'messages' => $messages,
            'mediaUrls' => $mediaUrls,
            'failedMediaIds' => $failedMediaIds,
            'latestInbound' => $latestInbound,
            'windowExpiresAt' => $windowExpiresAt,
            'isWindowActive' => $isWindowActive,
            'phoneNumber' => $phoneNumber,
            'approvedTemplates' => $approvedTemplates,
            'canReply' => auth()->user()?->can(Permission::MessageSend->value) ?? false,
        ]);
    }

    /** Send a direct reply or template message from the chat window. */
    public function reply(
        Request $request,
        string $phone,
        OutboundMessageService $sender,
    ): RedirectResponse {
        $this->authorize('create', Message::class);

        $cleanPhone = ltrim($phone, '+');

        $latestMessage = Message::query()
            ->where(function ($q) use ($cleanPhone, $phone) {
                $q->where('to_phone', $cleanPhone)->orWhere('to_phone', $phone);
            })
            ->latest('id')
            ->firstOrFail();

        $phoneNumber = $latestMessage->phoneNumber
            ?? WhatsappPhoneNumber::query()->where('is_default', true)->first()
            ?? WhatsappPhoneNumber::query()->first();

        if (! $phoneNumber) {
            return back()->with('flash_notify', [
                'type' => 'error',
                'message' => 'No active WhatsApp Phone Number configured to send from.',
            ]);
        }

        $recipient = new Recipient($cleanPhone);
        $templateId = null;

        if ($request->filled('template_id')) {
            $template = WhatsappTemplate::query()
                ->where('status', 'APPROVED')
                ->findOrFail($request->string('template_id'));

            $templateId = $template->getKey();

            $outbound = OutboundMessage::templateWithParams(
                $recipient,
                $template->name,
                $template->language,
            );
        } else {
            $validated = $request->validate([
                'body' => ['required', 'string', 'max:4096'],
            ]);

            $latestInbound = Message::query()
                ->where(function ($q) use ($cleanPhone, $phone) {
                    $q->where('to_phone', $cleanPhone)->orWhere('to_phone', $phone);
                })
                ->where('direction', 'inbound')
                ->latest('created_at')
                ->first();

            $isWindowActive = $latestInbound !== null && $latestInbound->created_at->addHours(24)->isFuture();

            if (! $isWindowActive) {
                return back()->with('flash_notify', [
                    'type' => 'error',
                    'message' => '24-hour customer service window has expired. Meta rejects free text messages outside the window. Please send an approved template.',
                ])->withInput();
            }

            $outbound = OutboundMessage::text($recipient, $validated['body']);
        }

        try {
            $sent = $sender->send($phoneNumber, $outbound, [
                'idempotency_key' => 'chat-reply:'.Str::ulid(),
                'contact_id' => $latestMessage->contact_id,
                'template_id' => $templateId,
            ]);

            if ($sent->status === MessageStatus::Failed) {
                return back()->with('flash_notify', [
                    'type' => 'error',
                    'message' => 'Send failed: '.($sent->error_message ?? 'Meta rejected the message.'),
                ]);
            }

            return back()->with('flash_notify', [
                'type' => 'success',
                'message' => 'Message sent successfully!',
            ]);
        } catch (\Throwable $e) {
            return back()->with('flash_notify', [
                'type' => 'error',
                'message' => 'Failed to send message: '.$e->getMessage(),
            ]);
        }
    }
}
