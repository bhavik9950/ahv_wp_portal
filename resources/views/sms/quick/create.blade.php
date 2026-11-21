<x-app-layout>
    <x-slot name="title">Quick SMS</x-slot>
    <x-slot name="header">Quick Single / Test SMS</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">Send Instant SMS</h2>
                <p class="text-xs opacity-70">Send an instant test SMS to one or multiple comma-separated mobile numbers.</p>
            </div>
            <div class="px-3 py-1.5 rounded-xl bg-base-100 border border-base-300 text-right">
                <div class="text-[0.65rem] opacity-60">Wallet Balance</div>
                <div class="text-sm font-bold text-success font-mono">₹{{ number_format($organization->sms_wallet_balance ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
            <form method="POST" action="{{ route('sms.quick.send') }}" class="space-y-4">
                @csrf

                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs font-bold">Recipient Mobile Number(s)</span></label>
                    <input type="text" name="to_phone" placeholder="e.g. 919982182325, 918344000010" required
                           value="{{ old('to_phone') }}"
                           class="input input-sm input-bordered font-mono text-xs">
                    <span class="text-[0.7rem] opacity-50 mt-1">Separate multiple numbers with commas. 10 or 12 digits (with country code).</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-bold">Sender Header</span></label>
                        <select name="sms_sender_id" required class="select select-sm select-bordered text-xs font-mono">
                            @foreach ($senders as $s)
                                <option value="{{ $s->id }}" {{ $s->is_default ? 'selected' : '' }}>
                                    {{ $s->sender_id }} ({{ $s->description ?? 'DLT Header' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-bold">DLT Template (Optional)</span></label>
                        <select name="sms_template_id" id="quick-template-select" onchange="applyTemplate()" class="select select-sm select-bordered text-xs">
                            <option value="">Custom Text / Direct</option>
                            @foreach ($templates as $tmpl)
                                <option value="{{ $tmpl->id }}" data-body="{{ $tmpl->body }}">
                                    {{ $tmpl->name }} ({{ $tmpl->dlt_template_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-1 flex justify-between">
                        <span class="label-text text-xs font-bold">Message Content</span>
                        <span id="char-counter" class="text-[0.7rem] opacity-60">0 / 160 chars (1 segment)</span>
                    </label>
                    <textarea name="message_body" id="quick-message-body" rows="4" required
                              oninput="updateCharCount()"
                              placeholder="Type your SMS message here..."
                              class="textarea textarea-bordered text-xs font-mono"></textarea>
                    <span class="text-[0.7rem] opacity-50 mt-1">
                        Rate: ₹{{ number_format($organization->sms_rate_per_message ?? 0.25, 4) }} per message segment.
                    </span>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <a href="{{ route('sms.dashboard') }}" class="btn btn-ghost btn-sm">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm gap-2">
                        <i class="ti ti-send"></i> Send Instant SMS
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function applyTemplate() {
            const select = document.getElementById('quick-template-select');
            const textarea = document.getElementById('quick-message-body');
            const selectedOpt = select.options[select.selectedIndex];
            const body = selectedOpt.getAttribute('data-body');
            if (body) {
                textarea.value = body;
                updateCharCount();
            }
        }

        function updateCharCount() {
            const textarea = document.getElementById('quick-message-body');
            const counter = document.getElementById('char-counter');
            const len = textarea.value.length;
            const segments = Math.ceil(len / 160) || 1;
            counter.innerText = `${len} / 160 chars (${segments} segment${segments > 1 ? 's' : ''})`;
        }
    </script>
</x-app-layout>
