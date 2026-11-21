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
use App\Services\WhatsApp\MediaLibrary;
use App\Services\WhatsApp\OutboundMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Message::class);

        $limit = 1000;

        $messages = Message::query()
            ->with(['contact', 'template'])
            ->latest()
            ->limit($limit + 1)
            ->get();

        // Search / filter / sort are handled client-side by DataTables.
        return view('whatsapp.messages.index', [
            'messages' => $messages->take($limit),
            'capped' => $messages->count() > $limit,
            'limit' => $limit,
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
    public function conversations(): View
    {
        $this->authorize('viewAny', Message::class);

        $latestIds = Message::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('to_phone')
            ->pluck('id');

        $counts = Message::query()
            ->select('to_phone', DB::raw('count(*) as message_count'))
            ->groupBy('to_phone')
            ->pluck('message_count', 'to_phone');

        $threads = Message::query()
            ->with(['contact', 'template'])
            ->whereIn('id', $latestIds)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return view('whatsapp.conversations.index', [
            'threads' => $threads,
            'counts' => $counts,
        ]);
    }

    /** One phone number's full history rendered as a WhatsApp-style chat thread. */
    public function conversation(string $phone, MediaLibrary $mediaLibrary): View
    {
        $this->authorize('viewAny', Message::class);

        $cleanPhone = ltrim($phone, '+');

        $messages = Message::query()
            ->where(function ($q) use ($cleanPhone, $phone) {
                $q->where('to_phone', $cleanPhone)->orWhere('to_phone', $phone);
            })
            ->with(['media', 'contact', 'template', 'phoneNumber'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        abort_if($messages->isEmpty(), 404);

        $mediaUrls = $messages->mapWithKeys(function (Message $m) use ($mediaLibrary): array {
            $media = $m->media;

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
