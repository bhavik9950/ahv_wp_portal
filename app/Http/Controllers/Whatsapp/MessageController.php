<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Message;
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
}
