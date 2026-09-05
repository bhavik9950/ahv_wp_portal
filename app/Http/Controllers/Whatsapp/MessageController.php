<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Message;
use App\Services\WhatsApp\MediaLibrary;
use Illuminate\Support\Facades\DB;
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

        $messages = Message::query()
            ->where('to_phone', $phone)
            ->with(['media', 'contact', 'template'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        abort_if($messages->isEmpty(), 404);

        $mediaUrls = $messages->mapWithKeys(function (Message $m) use ($mediaLibrary): array {
            $media = $m->media;

            return $media instanceof Media ? [$m->getKey() => $mediaLibrary->temporaryUrl($media)] : [];
        });

        return view('whatsapp.conversations.show', [
            'phone' => $phone,
            'contact' => $messages->last(fn (Message $m) => $m->contact !== null)?->contact,
            'messages' => $messages,
            'mediaUrls' => $mediaUrls,
        ]);
    }
}
