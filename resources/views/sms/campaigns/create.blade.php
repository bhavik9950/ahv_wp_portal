<x-app-layout>
    <x-slot name="title">New SMS Campaign</x-slot>
    <x-slot name="header">Launch SMS Campaign</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold">New Bulk Broadcast</h2>
                <p class="text-xs opacity-70">Broadcast an approved DLT message template to your contact groups.</p>
            </div>
            <div class="px-3 py-1.5 rounded-xl bg-base-100 border border-base-300 text-right">
                <div class="text-[0.65rem] opacity-60">Wallet Balance</div>
                <div class="text-sm font-bold text-success font-mono">₹{{ number_format($organization->sms_wallet_balance ?? 0, 2) }}</div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
            <form method="POST" action="{{ route('sms.campaigns.store') }}" class="space-y-4">
                @csrf

                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs font-bold">Campaign Name</span></label>
                    <input type="text" name="name" placeholder="e.g. Diwali Mega Sale Announcement" required
                           value="{{ old('name') }}"
                           class="input input-sm input-bordered text-xs">
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
                        <label class="label py-1"><span class="label-text text-xs font-bold">Approved DLT Template</span></label>
                        <select name="sms_template_id" id="campaign-template-select" required onchange="updateTemplatePreview()" class="select select-sm select-bordered text-xs">
                            <option value="">Select an approved template...</option>
                            @foreach ($templates as $tmpl)
                                <option value="{{ $tmpl->id }}" data-body="{{ $tmpl->body }}">
                                    {{ $tmpl->name }} ({{ $tmpl->dlt_template_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Template Body Preview --}}
                <div class="p-3.5 rounded-xl bg-base-200/60 border border-base-300 text-xs font-mono">
                    <div class="opacity-60 text-[0.7rem] uppercase font-bold mb-1">Approved Message Body</div>
                    <div id="template-body-preview" class="opacity-90 italic">Select a template above to preview text...</div>
                </div>

                {{-- Audience Selector --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-bold">Target Contact Group</span></label>
                        <select name="contact_group_id" class="select select-sm select-bordered text-xs">
                            <option value="">Choose a contact group...</option>
                            @foreach ($groups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->contacts_count }} contacts)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-bold">Additional Numbers (Optional)</span></label>
                        <input type="text" name="manual_numbers" placeholder="e.g. 919982182325, 918344000010"
                               class="input input-sm input-bordered font-mono text-xs">
                        <span class="text-[0.65rem] opacity-50 mt-0.5">Comma-separated extra numbers</span>
                    </div>
                </div>

                <div class="alert alert-info text-xs py-2 shadow-none border border-info/20">
                    <i class="ti ti-info-circle text-base"></i>
                    <span>
                        Cost estimate: ₹{{ number_format($organization->sms_rate_per_message ?? 0.25, 4) }} per message. 
                        Balance will be deducted automatically from your prepaid wallet.
                    </span>
                </div>

                <div class="pt-2 flex items-center justify-between">
                    <a href="{{ route('sms.campaigns.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm gap-2">
                        <i class="ti ti-rocket"></i> Launch Campaign Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateTemplatePreview() {
            const select = document.getElementById('campaign-template-select');
            const preview = document.getElementById('template-body-preview');
            const selectedOpt = select.options[select.selectedIndex];
            const body = selectedOpt.getAttribute('data-body');
            preview.innerText = body ? body : 'Select a template above to preview text...';
        }
    </script>
</x-app-layout>
