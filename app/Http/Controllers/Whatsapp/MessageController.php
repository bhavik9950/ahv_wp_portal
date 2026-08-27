<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Message::class);

        $messages = Message::query()
            ->with(['contact', 'template'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('direction'), fn ($q) => $q->where('direction', $request->string('direction')))
            ->when($request->filled('q'), fn ($q) => $q->where('to_phone', 'like', '%'.preg_replace('/\D/', '', (string) $request->string('q')).'%'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('whatsapp.messages.index', [
            'messages' => $messages,
            'filters' => $request->only(['status', 'direction', 'q']),
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
