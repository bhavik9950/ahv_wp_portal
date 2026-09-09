<x-app-layout>
    <x-slot name="title">Assistant Settings</x-slot>

    <div class="max-w-2xl space-y-4">
        <p class="text-sm opacity-70">The assistant replies to inbound WhatsApp messages automatically, inside Meta's 24-hour service window. It never starts conversations and never sends marketing.</p>

        @unless ($globalEnabled)
            <div class="alert alert-warning text-sm">
                <i class="ti ti-alert-triangle"></i>
                <span>The assistant is <strong>off globally</strong>. Set <code>BOT_ENABLED=true</code> and
                    <code>ANTHROPIC_API_KEY</code> in the server <code>.env</code>, then enabling it here takes effect.</span>
            </div>
        @endunless
        @if ($driver === 'fake')
            <div class="alert text-sm"><i class="ti ti-flask"></i><span>Running the <strong>fake</strong> LLM driver — replies are canned, not real.</span></div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error text-sm"><ul class="list-disc ml-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('whatsapp.assistant.settings.update') }}" data-loading data-loading-text="Saving…"
              class="card bg-base-100 border border-base-300">
            @csrf @method('PUT')
            <div class="card-body space-y-4">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="enabled" value="1" class="toggle toggle-primary" @checked($settings->enabled)>
                    <span class="font-medium">Auto-reply enabled</span>
                </label>

                <div>
                    <label class="label"><span class="label-text">Persona / system prompt</span></label>
                    <textarea name="system_prompt" rows="12" class="textarea textarea-bordered w-full font-mono text-xs" required>{{ old('system_prompt', $settings->system_prompt) }}</textarea>
                    <p class="text-xs opacity-60 mt-1">Guardrails (no prices, no timelines, no invented facts, plain text) are always appended automatically.</p>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label"><span class="label-text">Model (optional)</span></label>
                        <input name="model" value="{{ old('model', $settings->model) }}" class="input input-bordered w-full" placeholder="{{ config('services.bot.model') }}">
                        <p class="text-xs opacity-60 mt-1">Blank = server default. e.g. <code>claude-sonnet-5</code> for lower cost.</p>
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Daily reply cap per contact</span></label>
                        <input type="number" name="daily_reply_cap" value="{{ old('daily_reply_cap', $settings->daily_reply_cap) }}" min="1" max="500" class="input input-bordered w-full">
                    </div>
                </div>

                <div>
                    <label class="label"><span class="label-text">Hand-off keywords</span></label>
                    <input name="handoff_keywords" value="{{ old('handoff_keywords', implode(', ', $settings->handoff_keywords ?? [])) }}" class="input input-bordered w-full">
                    <p class="text-xs opacity-60 mt-1">Comma-separated. If a customer message contains one, the bot goes silent and the chat is flagged for a human.</p>
                </div>

                <div>
                    <label class="label"><span class="label-text">Fallback message</span></label>
                    <input name="fallback_message" value="{{ old('fallback_message', $settings->fallback_message) }}" class="input input-bordered w-full" required>
                    <p class="text-xs opacity-60 mt-1">Sent when the model errors out or declines.</p>
                </div>

                <div><button class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save</button></div>
            </div>
        </form>
    </div>
</x-app-layout>
