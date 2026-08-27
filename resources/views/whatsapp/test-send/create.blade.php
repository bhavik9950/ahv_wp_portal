<x-app-layout>
    <x-slot name="title">Send Test Message</x-slot>

    <div class="max-w-2xl space-y-4">
        @if (session('test_send_results'))
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body">
                    <h2 class="card-title text-base">Results</h2>
                    <ul class="text-sm space-y-1">
                        @foreach (session('test_send_results') as $r)
                            <li class="flex items-center gap-2">
                                @php($ok = in_array($r['status'], ['sent','queued','delivered','read']))
                                <i class="ti {{ $ok ? 'ti-circle-check text-success' : 'ti-alert-triangle text-warning' }}"></i>
                                <span class="font-mono">{{ $r['phone'] }}</span>
                                <span class="badge badge-sm badge-ghost">{{ $r['status'] }}</span>
                                @isset($r['error'])<span class="opacity-60">{{ $r['error'] }}</span>@endisset
                                @isset($r['id'])<a class="link text-xs" href="{{ route('whatsapp.messages.show', $r['id']) }}">view</a>@endisset
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('whatsapp.test-send.store') }}"
              x-data="{ mode: '{{ old('mode', 'text') }}' }"
              data-loading data-loading-text="Sending…"
              class="card bg-base-100 border border-base-300">
            @csrf
            <div class="card-body space-y-4">
                <div>
                    <label class="label"><span class="label-text">From number</span></label>
                    <select name="whatsapp_phone_number_id" class="select select-bordered w-full" required>
                        @forelse ($numbers as $n)
                            <option value="{{ $n->id }}">{{ $n->display_phone_number ?? $n->phone_number_id }} {{ $n->is_default ? '(default)' : '' }}</option>
                        @empty
                            <option disabled>No phone numbers — sync them first</option>
                        @endforelse
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Recipients (up to 5, digits only, comma-separated)</span></label>
                    <input name="recipients" value="{{ old('recipients') ? implode(', ', (array) old('recipients')) : '' }}"
                           class="input input-bordered w-full" placeholder="919876543210, 919812345678" required>
                    @error('recipients')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    @error('recipients.0')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="tabs tabs-boxed w-fit">
                    <button type="button" class="tab" :class="mode === 'text' && 'tab-active'" x-on:click="mode = 'text'">Free text</button>
                    <button type="button" class="tab" :class="mode === 'template' && 'tab-active'" x-on:click="mode = 'template'">Template</button>
                </div>
                <input type="hidden" name="mode" x-model="mode">

                <div x-show="mode === 'text'">
                    <label class="label"><span class="label-text">Message</span></label>
                    <textarea name="body" rows="3" class="textarea textarea-bordered w-full">{{ old('body') }}</textarea>
                    <p class="text-xs opacity-60 mt-1">Free-text messages only reach users inside the 24-hour customer service window.</p>
                </div>

                <div x-show="mode === 'template'" x-data="testSendTemplate" class="space-y-4">
                    @php($oldVarsJson = json_encode(array_values((array) old('variables', [])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP))
                    <script type="application/json" id="test-send-templates"
                            data-selected="{{ old('template_id') }}"
                            data-old-values="{{ $oldVarsJson }}">{!! json_encode($templateData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>

                    <div>
                        <label class="label"><span class="label-text">Approved template</span></label>
                        <select name="template_id" class="select select-bordered w-full"
                                x-model="templateId" x-on:change="onSelect()">
                            <option value="">— select —</option>
                            @foreach ($templates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->language }})</option>
                            @endforeach
                        </select>
                        @error('template_id')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <template x-if="tpl">
                        <div class="space-y-4">
                            {{-- What the template says --}}
                            <div class="rounded-box border border-base-300 bg-base-200/50 p-3 space-y-2">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="badge badge-sm badge-ghost" x-text="tpl.category || 'TEMPLATE'"></span>
                                    <span class="opacity-60" x-text="tpl.language"></span>
                                </div>
                                <div x-show="tpl.header && tpl.header.format === 'TEXT'" class="text-sm font-semibold" x-text="tpl.header?.text"></div>
                                <div x-show="mediaHeader" class="flex items-center gap-1 text-xs opacity-70">
                                    <i class="ti ti-photo"></i><span x-text="mediaHeader + ' header — attach the media in Meta; test send uses the sample'"></span>
                                </div>
                                <div class="text-sm leading-relaxed whitespace-pre-wrap break-words">
                                    <template x-for="(c, i) in chunks(tpl.body)" :key="i"><span
                                        :class="c.isVar ? 'badge badge-sm badge-primary badge-outline mx-0.5 align-middle' : ''"
                                        x-text="c.text"></span></template>
                                </div>
                                <div x-show="tpl.footer" class="text-xs opacity-60" x-text="tpl.footer"></div>
                                <div x-show="tpl.buttons.length" class="flex flex-wrap gap-1 pt-1">
                                    <template x-for="b in tpl.buttons" :key="b">
                                        <span class="badge badge-sm badge-info badge-outline gap-1"><i class="ti ti-chevron-right"></i><span x-text="b"></span></span>
                                    </template>
                                </div>
                            </div>

                            {{-- Fill the variables --}}
                            <template x-if="tpl.variables.length">
                                <div class="space-y-2">
                                    @php($nPlaceholder = '{{n}}')
                                    <p class="text-xs opacity-60">Each <span class="badge badge-xs badge-primary badge-outline">{{ $nPlaceholder }}</span> is a placeholder the template fills in per recipient. Enter the value to use for this test.</p>
                                    <template x-for="v in tpl.variables" :key="v.index">
                                        <div>
                                            <label class="label py-1">
                                                <span class="label-text flex items-center gap-1">
                                                    Variable <span class="badge badge-sm badge-primary badge-outline" x-text="varLabel(v.index)"></span>
                                                </span>
                                                <span class="label-text-alt opacity-60" x-show="v.example">sample: <span x-text="v.example"></span></span>
                                            </label>
                                            <input :name="'variables[' + (v.index - 1) + ']'" x-model="values[v.index]"
                                                   class="input input-bordered input-sm w-full"
                                                   :placeholder="v.example || ('Value for ' + varLabel(v.index))">
                                        </div>
                                    </template>
                                    @foreach ($errors->get('variables.*') as $messages)
                                        @foreach ($messages as $message)
                                            <p class="text-error text-xs mt-1">{{ $message }}</p>
                                        @endforeach
                                    @endforeach
                                </div>
                            </template>

                            {{-- Live preview --}}
                            <div>
                                <label class="label py-1"><span class="label-text">Preview</span></label>
                                <div class="rounded-box bg-base-200 p-3">
                                    <div class="chat chat-start">
                                        <div class="chat-bubble bg-base-100 text-base-content max-w-full">
                                            <div x-show="tpl.header && tpl.header.format === 'TEXT'" class="font-semibold whitespace-pre-wrap break-words" x-text="render(tpl.header?.text)"></div>
                                            <div x-show="mediaHeader" class="mb-1 flex h-24 items-center justify-center rounded bg-base-300 text-xs opacity-60">
                                                <i class="ti ti-photo text-lg"></i>
                                            </div>
                                            <div class="whitespace-pre-wrap break-words" x-text="render(tpl.body)"></div>
                                            <div x-show="tpl.footer" class="mt-1 text-xs opacity-50" x-text="tpl.footer"></div>
                                        </div>
                                    </div>
                                    <template x-if="tpl.buttons.length">
                                        <div class="mt-1 space-y-1">
                                            <template x-for="b in tpl.buttons" :key="b">
                                                <div class="rounded-box border border-base-300 bg-base-100 py-1.5 text-center text-sm font-medium text-info" x-text="b"></div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <p class="text-xs opacity-50 mt-1">Approximate — actual rendering, media and button behaviour are controlled by WhatsApp.</p>
                            </div>
                        </div>
                    </template>
                </div>

                <button class="btn btn-primary"><i class="ti ti-send"></i> Send test</button>
            </div>
        </form>
    </div>
</x-app-layout>
