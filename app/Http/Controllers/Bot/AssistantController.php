<?php

declare(strict_types=1);

namespace App\Http\Controllers\Bot;

use App\Enums\BotConversationMode;
use App\Http\Controllers\Controller;
use App\Models\BotConversation;
use App\Services\Bot\BotService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantController extends Controller
{
    public function __construct(private readonly BotService $bot) {}

    public function settings(TenantContext $tenant): View
    {
        $this->authorize('manage-bot');

        return view('assistant.settings', [
            'settings' => $this->bot->settingsOrCreate((int) $tenant->id()),
            'globalEnabled' => (bool) config('services.bot.enabled'),
            'driver' => (string) config('services.bot.driver'),
        ]);
    }

    public function update(Request $request, TenantContext $tenant): RedirectResponse
    {
        $this->authorize('manage-bot');

        $data = $request->validate([
            'enabled' => ['boolean'],
            'system_prompt' => ['required', 'string', 'max:8000'],
            'model' => ['nullable', 'string', 'max:60'],
            'daily_reply_cap' => ['required', 'integer', 'min:1', 'max:500'],
            'handoff_keywords' => ['nullable', 'string', 'max:2000'],
            'fallback_message' => ['required', 'string', 'max:1000'],
        ]);

        $keywords = collect(explode(',', (string) ($data['handoff_keywords'] ?? '')))
            ->map(fn ($k) => trim($k))
            ->filter()
            ->values()
            ->all();

        $this->bot->settingsOrCreate((int) $tenant->id())->forceFill([
            'enabled' => (bool) ($data['enabled'] ?? false),
            'system_prompt' => $data['system_prompt'],
            'model' => ($data['model'] ?? null) ?: null,
            'daily_reply_cap' => (int) $data['daily_reply_cap'],
            'handoff_keywords' => $keywords,
            'fallback_message' => $data['fallback_message'],
        ])->save();

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Assistant settings saved.']);
    }

    public function conversations(): View
    {
        $this->authorize('viewAny', BotConversation::class);

        return view('assistant.conversations', [
            'conversations' => BotConversation::query()
                ->with('contact')
                ->withCount('messages')
                ->latest('last_inbound_at')
                ->limit(500)
                ->get(),
        ]);
    }

    public function conversation(BotConversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $conversation->load(['contact', 'messages']);

        return view('assistant.conversation', ['conversation' => $conversation]);
    }

    public function setMode(Request $request, BotConversation $conversation): RedirectResponse
    {
        $this->authorize('manage-bot');

        $mode = BotConversationMode::tryFrom((string) $request->string('mode'));
        abort_if($mode === null, 422);

        $conversation->forceFill([
            'mode' => $mode->value,
            'handoff_reason' => $mode === BotConversationMode::Human ? ($conversation->handoff_reason ?? 'manual take-over') : null,
            'handoff_at' => $mode === BotConversationMode::Human ? ($conversation->handoff_at ?? now()) : null,
        ])->save();

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => $mode === BotConversationMode::Bot ? 'Handed back to the assistant.' : 'You are now handling this chat.',
        ]);
    }
}
