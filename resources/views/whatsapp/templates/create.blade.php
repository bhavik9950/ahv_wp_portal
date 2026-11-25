<x-app-layout>
    <x-slot name="title">New Template</x-slot>

    @php($v1 = '{' . '{1}' . '}')
    @php($v2 = '{' . '{2}' . '}')

    <div class="max-w-7xl mx-auto space-y-4"
         x-data="templateCreator({
             name: @js(old('name', '')),
             language: @js(old('language', $language)),
             category: @js(old('category', 'UTILITY')),
             header: @js(old('header_type', 'none')),
             headerText: @js(old('header_text', '')),
             headerSample: @js(old('sample_header_variable', '')),
             body: @js(old('body', '')),
             footer: @js(old('footer', '')),
             samples: @js((object) old('sample_variables', [])),
             buttons: @js(collect(old('buttons', []))->map(fn ($b) => [
                 'type' => $b['type'] ?? 'quick_reply',
                 'text' => $b['text'] ?? '',
                 'url' => $b['url'] ?? '',
                 'phone_number' => $b['phone_number'] ?? '',
             ])->values())
         })">

        <div class="alert alert-info text-sm shadow-xs border border-info/20">
            <i class="ti ti-info-circle text-lg"></i>
            <div>
                <span class="font-semibold">Meta Template Guidelines:</span> Templates are reviewed by Meta.
                When adding variables like <code class="bg-base-100/50 px-1 rounded font-mono">{{ $v1 }}</code>, Meta requires realistic sample / dummy values for approval.
            </div>
        </div>

        <div class="grid lg:grid-cols-12 gap-6 items-start">
            {{-- Left Side: Template Form --}}
            <div class="lg:col-span-7">
                <form method="POST" action="{{ route('whatsapp.templates.store') }}" enctype="multipart/form-data"
                      data-loading data-loading-text="Submitting to Meta…"
                      class="card bg-base-100 border border-base-300 shadow-sm">
                    @csrf
                    <div class="card-body space-y-4 p-5 sm:p-6">

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label py-1"><span class="label-text text-xs font-semibold">Template Name <span class="text-error">*</span></span></label>
                                <input name="name" x-model="name" class="input input-bordered input-sm w-full font-mono text-xs" placeholder="e.g. order_update_v1" required>
                                <span class="text-[0.7rem] opacity-50">Lowercase letters, numbers, and underscores only.</span>
                                @error('name')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="label py-1"><span class="label-text text-xs font-semibold">Language <span class="text-error">*</span></span></label>
                                <input name="language" x-model="language" class="input input-bordered input-sm w-full font-mono text-xs" required placeholder="en or hi">
                                <span class="text-[0.7rem] opacity-50">e.g. <span class="font-mono">en</span> (English), <span class="font-mono">hi</span> (Hindi)</span>
                                @error('language')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Category <span class="text-error">*</span></span></label>
                            <select name="category" x-model="category" class="select select-bordered select-sm w-full" required>
                                <option value="UTILITY">UTILITY (Transactional updates, account info, reminders)</option>
                                <option value="MARKETING">MARKETING (Promotions, offers, news, announcements)</option>
                                <option value="AUTHENTICATION">AUTHENTICATION (OTPs, verification codes)</option>
                            </select>
                        </div>

                        {{-- Header Section --}}
                        <div class="p-3 bg-base-200/50 rounded-lg border border-base-300 space-y-3">
                            <label class="label py-0"><span class="label-text text-xs font-semibold">Header (Optional)</span></label>
                            <select name="header_type" x-model="header" class="select select-bordered select-sm w-full">
                                <option value="none">None (No header)</option>
                                <option value="text">Text (Header text)</option>
                                <option value="image">Image (Photo / Banner)</option>
                                <option value="video">Video</option>
                                <option value="document">Document (PDF)</option>
                            </select>

                            {{-- Text Header --}}
                            <div x-show="header === 'text'" class="space-y-2">
                                <input name="header_text" x-model="headerText"
                                       class="input input-bordered input-sm w-full" placeholder="Header text (max 60 chars)" maxlength="60">
                                @error('header_text')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror

                                <div x-show="headerHasVar" class="p-2.5 bg-base-100 rounded border border-base-300 space-y-1">
                                    <label class="label py-0">
                                        <span class="label-text text-[0.75rem] font-medium text-primary flex items-center gap-1">
                                            <i class="ti ti-variable"></i> Sample Value for Header {{ $v1 }}
                                        </span>
                                    </label>
                                    <input type="text" name="sample_header_variable" x-model="headerSample"
                                           class="input input-bordered input-xs w-full font-mono" placeholder="e.g. Rahul or Welcome">
                                    <span class="text-[0.68rem] opacity-60">Meta requires a sample value for the header variable.</span>
                                </div>
                            </div>

                            {{-- Media Header --}}
                            <div x-show="['image','video','document'].includes(header)" class="space-y-1.5">
                                <label class="label py-0"><span class="label-text text-[0.75rem] font-semibold">Sample Media File for Review <span class="text-error">*</span></span></label>
                                <input type="file" name="sample_media"
                                       @change="handleMediaChange($event)"
                                       class="file-input file-input-bordered file-input-sm w-full"
                                       accept="image/jpeg,image/png,video/mp4,video/3gpp,application/pdf">
                                <p class="text-[0.7rem] opacity-60">
                                    Required by Meta for template review & used as sample preview on the right. Image ≤ 5 MB, video ≤ 16 MB, PDF ≤ 100 MB.
                                </p>
                                @error('sample_media')<p class="text-error text-xs">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Body Section --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="label py-0"><span class="label-text text-xs font-semibold">Body Text <span class="text-error">*</span></span></label>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="addVariable()" class="btn btn-xs btn-primary btn-outline gap-1">
                                        <i class="ti ti-plus"></i> Add Variable
                                    </button>
                                </div>
                            </div>

                            <textarea name="body" x-ref="bodyTextarea" x-model="body" rows="5" class="textarea textarea-bordered w-full text-sm leading-relaxed" required
                                      placeholder="Hello {{ $v1 }}, your order {{ $v2 }} has been confirmed."></textarea>
                            
                            <div class="flex items-center justify-between text-[0.7rem] opacity-60">
                                <span>Use <code class="font-mono font-semibold">{{ $v1 }}</code>, <code class="font-mono font-semibold">{{ $v2 }}</code> for sequential variables. Supports <code class="font-mono">*bold*</code>, <code class="font-mono">_italic_</code>.</span>
                                <span x-text="`${(body || '').length} / 1024`"></span>
                            </div>
                            @error('body')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror

                            {{-- Dynamic Sample Values for Body Variables --}}
                            <div x-show="placeholders.length > 0" class="p-3.5 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-xl border border-emerald-200 dark:border-emerald-800/50 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-900 dark:text-emerald-300">
                                        <i class="ti ti-variable-plus text-base"></i>
                                        <span>Sample / Dummy Values for Variables</span>
                                    </div>
                                    <span class="badge badge-xs badge-success text-[0.65rem] px-2 py-1">Required by Meta</span>
                                </div>
                                <p class="text-[0.72rem] opacity-75 leading-tight">
                                    Meta requires real-world sample values to approve your template. Type dummy values below — they will appear live in the phone preview on the right and get submitted to Meta!
                                </p>

                                <div class="grid sm:grid-cols-2 gap-2.5 pt-1">
                                    <template x-for="n in placeholders" :key="n">
                                        <div class="p-2 bg-base-100 dark:bg-base-200 rounded-lg border border-base-300">
                                            <label class="label py-0 mb-1">
                                                <span class="label-text text-[0.72rem] font-bold text-primary flex items-center gap-1">
                                                    <i class="ti ti-braces"></i>
                                                    <span x-text="'Sample value for {' + '{' + n + '}' + '}'"></span>
                                                </span>
                                            </label>
                                            <input type="text"
                                                   :name="'sample_variables[' + n + ']'"
                                                   x-model="samples[n]"
                                                   class="input input-bordered input-xs w-full text-xs font-mono"
                                                   :placeholder="n === 1 ? 'e.g. Rahul' : (n === 2 ? 'e.g. Order #1024' : (n === 3 ? 'e.g. ₹500' : 'Sample ' + n))">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Section --}}
                        <div>
                            <label class="label py-1"><span class="label-text text-xs font-semibold">Footer (Optional)</span></label>
                            <input name="footer" x-model="footer" class="input input-bordered input-sm w-full" placeholder="e.g. Reply STOP to unsubscribe" maxlength="60">
                            <span class="text-[0.7rem] opacity-50">Small muted disclaimer text at the bottom.</span>
                            @error('footer')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Buttons Section --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="label py-0"><span class="label-text text-xs font-semibold">Interactive Buttons (Optional)</span></label>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-xs btn-outline gap-1" x-on:click="addButton()">
                                        <i class="ti ti-plus"></i> Add Button
                                    </button>
                                    <button type="button" class="btn btn-xs btn-ghost text-error" x-show="buttons.length > 0" x-on:click="removeButton()">
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <template x-for="(btn, i) in buttons" :key="i">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 p-2.5 bg-base-200/50 rounded-lg border border-base-300">
                                    <select x-model="btn.type" :name="`buttons[${i}][type]`" class="select select-bordered select-xs">
                                        <option value="quick_reply">Quick reply (Action)</option>
                                        <option value="url">Visit website (URL)</option>
                                        <option value="phone">Call phone number</option>
                                    </select>
                                    <input x-model="btn.text" :name="`buttons[${i}][text]`" class="input input-bordered input-xs" placeholder="Button text (max 25)" maxlength="25">
                                    <div>
                                        <template x-if="btn.type === 'url'">
                                            <input x-model="btn.url" :name="`buttons[${i}][url]`" class="input input-bordered input-xs w-full" placeholder="https://example.com">
                                        </template>
                                        <template x-if="btn.type === 'phone'">
                                            <input x-model="btn.phone_number" :name="`buttons[${i}][phone_number]`" class="input input-bordered input-xs w-full" placeholder="+917878159887">
                                        </template>
                                        <template x-if="btn.type === 'quick_reply'">
                                            <span class="text-[0.68rem] opacity-50 italic flex items-center h-full">User sends this text</span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            @error('buttons')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                            @foreach ($errors->get('buttons.*') as $field => $messages)
                                @foreach ($messages as $m)<p class="text-error text-xs mt-1">{{ $m }}</p>@endforeach
                            @endforeach
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-error text-sm">
                                <ul class="list-disc ml-4">
                                    @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex items-center gap-3 pt-2">
                            <button class="btn btn-primary btn-sm gap-1.5 px-4 shadow-sm">
                                <i class="ti ti-send"></i> Submit to Meta for Review
                            </button>
                            <a href="{{ route('whatsapp.templates.index') }}" class="btn btn-ghost btn-sm">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Right Side: Live WhatsApp Phone Preview --}}
            <div class="lg:col-span-5 lg:sticky lg:top-4">
                <div class="flex items-center justify-between mb-2 px-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/70 flex items-center gap-1.5">
                        <i class="ti ti-brand-whatsapp text-emerald-600 text-base"></i> Live WhatsApp Preview
                    </span>
                    <span class="badge badge-xs badge-success gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        Real-time
                    </span>
                </div>

                {{-- Phone Shell Mockup --}}
                <div class="bg-neutral-900 p-3 rounded-[38px] shadow-2xl border-4 border-neutral-800 max-w-[380px] mx-auto">
                    {{-- Notch / Dynamic Island --}}
                    <div class="relative bg-neutral-950 rounded-t-[28px] pt-2 pb-1.5 px-6 flex items-center justify-between text-[0.65rem] text-white/80">
                        <span>9:41</span>
                        <div class="w-20 h-4 bg-black rounded-full mx-auto"></div>
                        <div class="flex items-center gap-1">
                            <i class="ti ti-wifi"></i>
                            <i class="ti ti-battery-4"></i>
                        </div>
                    </div>

                    {{-- WhatsApp Header Bar --}}
                    <div class="bg-[#075e54] text-white px-3 py-2 flex items-center gap-2 shadow-md">
                        <i class="ti ti-arrow-left text-sm cursor-pointer opacity-90"></i>
                        <div class="w-8 h-8 rounded-full bg-emerald-700 flex items-center justify-center text-white font-bold text-xs ring-1 ring-white/30 shrink-0">
                            <i class="ti ti-brand-whatsapp text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-bold leading-tight truncate" x-text="name ? name : 'AH&V WhatsApp'"></div>
                            <div class="text-[0.62rem] text-emerald-200 flex items-center gap-0.5 leading-tight">
                                <i class="ti ti-badge-check text-emerald-300"></i>
                                <span>Official Business</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 text-white/80 text-sm">
                            <i class="ti ti-video"></i>
                            <i class="ti ti-phone"></i>
                            <i class="ti ti-dots-vertical"></i>
                        </div>
                    </div>

                    {{-- WhatsApp Chat Screen Body --}}
                    <div class="bg-[#efeae2] dark:bg-[#0b141a] p-3 min-h-[420px] max-h-[540px] overflow-y-auto flex flex-col justify-end space-y-2 rounded-b-[28px] relative text-neutral-800">
                        {{-- WhatsApp Doodle Pattern Simulation --}}
                        <div class="text-center my-1">
                            <span class="bg-white/80 dark:bg-neutral-800/80 text-neutral-600 dark:text-neutral-300 text-[0.62rem] px-2.5 py-0.5 rounded-md shadow-2xs font-medium uppercase tracking-wide">
                                Today
                            </span>
                        </div>

                        {{-- WhatsApp Message Bubble --}}
                        <div class="bg-white dark:bg-[#1f2c34] text-neutral-800 dark:text-neutral-100 rounded-xl rounded-tl-xs p-2.5 shadow-sm border border-black/5 max-w-[94%] space-y-2 relative ml-1">
                            
                            {{-- Header Preview --}}
                            <div x-show="header !== 'none'">
                                {{-- Text Header --}}
                                <template x-if="header === 'text'">
                                    <div class="font-bold text-xs text-neutral-900 dark:text-white pb-1 border-b border-black/5 leading-snug" x-html="previewHeader || 'Header text preview'"></div>
                                </template>

                                {{-- Image Header --}}
                                <template x-if="header === 'image'">
                                    <div>
                                        <template x-if="mediaPreviewUrl">
                                            <img :src="mediaPreviewUrl" class="rounded-lg w-full max-h-44 object-cover border border-black/10">
                                        </template>
                                        <template x-if="!mediaPreviewUrl">
                                            <div class="h-32 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-dashed border-emerald-300 dark:border-emerald-700 flex flex-col items-center justify-center text-emerald-700 dark:text-emerald-400 p-2 text-center">
                                                <i class="ti ti-photo text-2xl mb-1"></i>
                                                <span class="text-xs font-semibold">Image Header</span>
                                                <span class="text-[0.65rem] opacity-60">Sample image will appear here</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- Video Header --}}
                                <template x-if="header === 'video'">
                                    <div class="h-32 rounded-lg bg-neutral-900 text-white flex flex-col items-center justify-center relative overflow-hidden border border-black/10">
                                        <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur-xs flex items-center justify-center">
                                            <i class="ti ti-player-play-filled text-sm"></i>
                                        </div>
                                        <span class="text-[0.7rem] font-medium mt-1">Video Header Preview</span>
                                    </div>
                                </template>

                                {{-- Document Header --}}
                                <template x-if="header === 'document'">
                                    <div class="p-2 rounded-lg bg-neutral-100 dark:bg-neutral-800 flex items-center gap-2 border border-black/5">
                                        <div class="w-8 h-8 rounded bg-red-100 text-red-600 flex items-center justify-center text-base shrink-0">
                                            <i class="ti ti-file-type-pdf"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[0.72rem] font-semibold truncate text-neutral-800 dark:text-neutral-200" x-text="mediaName || 'Document_Sample.pdf'"></div>
                                            <div class="text-[0.6rem] text-neutral-500">PDF Document</div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Body Preview --}}
                            <div class="text-xs leading-relaxed break-words whitespace-pre-line text-neutral-800 dark:text-neutral-200"
                                 x-html="previewBodyHtml"></div>

                            {{-- Footer Preview --}}
                            <div x-show="footer" class="text-[0.68rem] text-neutral-400 dark:text-neutral-500 pt-0.5 border-t border-black/5" x-text="footer"></div>

                            {{-- Time & Checkmarks --}}
                            <div class="flex items-center justify-end gap-1 text-[0.62rem] text-neutral-400 dark:text-neutral-400 mt-0.5">
                                <span x-text="new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })"></span>
                                <i class="ti ti-checks text-sky-500 text-xs"></i>
                            </div>

                            {{-- Buttons Preview --}}
                            <div x-show="buttons.length > 0" class="pt-1 space-y-1 border-t border-black/5">
                                <template x-for="(btn, i) in buttons" :key="i">
                                    <div x-show="btn.text" class="text-center py-1.5 px-2 bg-white dark:bg-neutral-800 hover:bg-neutral-50 text-[#00a884] font-semibold text-xs rounded-md shadow-2xs border border-neutral-200 dark:border-neutral-700 flex items-center justify-center gap-1.5 cursor-pointer">
                                        <template x-if="btn.type === 'quick_reply'"><i class="ti ti-arrow-back-up text-xs"></i></template>
                                        <template x-if="btn.type === 'url'"><i class="ti ti-external-link text-xs"></i></template>
                                        <template x-if="btn.type === 'phone'"><i class="ti ti-phone text-xs"></i></template>
                                        <span x-text="btn.text || 'Button'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Helper Hint under chat bubble --}}
                        <div class="text-center text-[0.65rem] text-neutral-500 dark:text-neutral-400 pt-1">
                            <span><i class="ti ti-info-circle"></i> This is exactly how customers see it on WhatsApp</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        @verbatim
        window.templateCreator = function (initial) {
            return {
                name: initial.name || '',
                language: initial.language || 'en',
                category: initial.category || 'UTILITY',
                header: initial.header || 'none',
                headerText: initial.headerText || '',
                headerSample: initial.headerSample || '',
                body: initial.body || '',
                footer: initial.footer || '',
                mediaPreviewUrl: null,
                mediaName: '',
                mediaType: '',
                samples: initial.samples || {},
                buttons: initial.buttons || [],

                get placeholders() {
                    if (!this.body) return [];
                    const matches = [...this.body.matchAll(/\{\{\s*(\d+)\s*\}\}/g)];
                    const nums = [...new Set(matches.map(m => parseInt(m[1], 10)))];
                    return nums.sort((a, b) => a - b);
                },

                get headerHasVar() {
                    return /\{\{\s*1\s*\}\}/.test(this.headerText || '');
                },

                addVariable() {
                    const next = this.placeholders.length > 0 ? Math.max(...this.placeholders) + 1 : 1;
                    const tag = `{{${next}}}`;
                    const textarea = this.$refs.bodyTextarea;
                    if (textarea) {
                        const start = textarea.selectionStart;
                        const end = textarea.selectionEnd;
                        const val = this.body || '';
                        this.body = val.substring(0, start) + (start > 0 && !val[start - 1].match(/\s/) ? ' ' : '') + tag + (val[end] && !val[end].match(/\s/) ? ' ' : '') + val.substring(end);
                        this.$nextTick(() => {
                            textarea.focus();
                            const pos = start + tag.length + 1;
                            textarea.setSelectionRange(pos, pos);
                        });
                    } else {
                        this.body = (this.body || '') + (this.body && !this.body.endsWith(' ') ? ' ' : '') + tag;
                    }
                },

                addButton() {
                    if (this.buttons.length < 10) {
                        this.buttons.push({ type: 'quick_reply', text: '', url: '', phone_number: '' });
                    }
                },

                removeButton() {
                    this.buttons.pop();
                },

                handleMediaChange(e) {
                    const file = e.target.files && e.target.files[0];
                    if (file) {
                        this.mediaName = file.name;
                        this.mediaType = file.type;
                        if (file.type.startsWith('image/')) {
                            this.mediaPreviewUrl = URL.createObjectURL(file);
                        } else {
                            this.mediaPreviewUrl = null;
                        }
                    } else {
                        this.mediaPreviewUrl = null;
                        this.mediaName = '';
                        this.mediaType = '';
                    }
                },

                escapeHtml(text) {
                    if (!text) return '';
                    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                    return text.replace(/[&<>"']/g, m => map[m]);
                },

                get previewHeader() {
                    if (this.header !== 'text' || !this.headerText) return '';
                    let txt = this.escapeHtml(this.headerText);
                    if (this.headerHasVar) {
                        const val = this.headerSample && this.headerSample.trim() !== ''
                            ? `<span class="text-primary font-semibold underline">${this.escapeHtml(this.headerSample)}</span>`
                            : `<span class="badge badge-primary badge-xs font-mono">{{1}}</span>`;
                        txt = txt.replace(/\{\{\s*1\s*\}\}/g, val);
                    }
                    return txt;
                },

                get previewBodyHtml() {
                    if (!this.body) {
                        return '<span class="opacity-40 italic">Type your message body on the left to see live preview…</span>';
                    }
                    let txt = this.escapeHtml(this.body);

                    this.placeholders.forEach(n => {
                        const val = this.samples[n] && this.samples[n].trim() !== ''
                            ? `<span class="font-semibold text-emerald-800 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950/70 px-1 py-0.5 rounded border border-emerald-300 dark:border-emerald-800 underline decoration-emerald-500/50">${this.escapeHtml(this.samples[n])}</span>`
                            : `<span class="font-mono font-bold text-primary bg-primary/10 px-1.5 py-0.5 rounded border border-primary/20">{{${n}}}</span>`;
                        const regex = new RegExp(`\\{\\{\\s*${n}\\s*\\}\\}`, 'g');
                        txt = txt.replace(regex, val);
                    });

                    txt = txt.replace(/\*([^\*]+)\*/g, '<strong class="font-bold">$1</strong>');
                    txt = txt.replace(/_([^_]+)_/g, '<em class="italic">$1</em>');
                    txt = txt.replace(/~([^~]+)~/g, '<del class="line-through opacity-70">$1</del>');

                    return txt;
                }
            };
        };
        @endverbatim
    </script>
</x-app-layout>
