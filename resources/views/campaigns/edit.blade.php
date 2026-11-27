<x-app-layout>
    <x-slot name="title">Campaign · {{ $campaign->name }}</x-slot>

    @php($vm = $campaign->variable_map ?? [])
    @php($af = $campaign->audience_filter ?? ['type' => 'all'])

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <h1 class="text-xl font-semibold">{{ $campaign->name }}</h1>

            {{-- 1. Basics --}}
            <form method="POST" action="{{ route('whatsapp.campaigns.update', $campaign) }}" class="card bg-base-100 border border-base-300">
                @csrf @method('PUT')
                <div class="card-body space-y-3">
                    <h2 class="card-title text-base">1 · Basics</h2>
                    <div>
                        <label class="label"><span class="label-text">Campaign name</span></label>
                        <input name="name" value="{{ $campaign->name }}" class="input input-bordered w-full" required>
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Send from</span></label>
                        <select name="whatsapp_phone_number_id" class="select select-bordered w-full">
                            <option value="">— select number —</option>
                            @foreach ($numbers as $n)
                                <option value="{{ $n->id }}" @selected($campaign->whatsapp_phone_number_id === $n->id)>
                                    {{ $n->display_phone_number ?? $n->phone_number_id }} {{ $n->is_default ? '(default)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label"><span class="label-text">Delay between messages</span></label>
                        <select name="send_delay_seconds" class="select select-bordered w-full">
                            @foreach ([0 => 'No custom delay', 1 => '1 second', 2 => '2 seconds', 3 => '3 seconds', 5 => '5 seconds', 10 => '10 seconds'] as $v => $label)
                                <option value="{{ $v }}" @selected((int) $campaign->send_delay_seconds === $v)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs opacity-60 mt-1">A convenience only — Meta's rate limits and account quality always take precedence.</p>
                    </div>
                    <button class="btn btn-sm btn-primary w-fit">Save</button>
                </div>
            </form>

            {{-- 2. Template + 3. Variables --}}
            <form method="POST" action="{{ route('whatsapp.campaigns.update', $campaign) }}"
                  class="card bg-base-100 border border-base-300"
                  x-data="{ tpl: '{{ $campaign->template_id }}' }">
                @csrf @method('PUT')
                <div class="card-body space-y-3">
                    <h2 class="card-title text-base">2 · Template</h2>
                    <select name="template_id" x-model="tpl" class="select select-bordered w-full">
                        <option value="">— select APPROVED template —</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}" data-vars="{{ count($t->variablePlaceholders()) }}">{{ $t->name }} ({{ $t->language }})</option>
                        @endforeach
                    </select>

                    @if ($campaign->template_id && $placeholders > 0)
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium text-sm">3 · Map {{ $placeholders }} variable(s)</h3>
                                <span class="text-[0.7rem] opacity-60">Match each template placeholder to a contact/custom field</span>
                            </div>
                            @for ($i = 1; $i <= $placeholders; $i++)
                                @php($spec = $vm[(string) $i] ?? ['type' => 'static', 'value' => ''])
                                <div class="grid grid-cols-12 gap-2 items-center" x-data="{ type: '{{ $spec['type'] }}' }"
                                     style="display: grid; grid-template-columns: 48px 1fr 1.4fr; gap: 0.5rem; align-items: center;">
                                    <span class="badge badge-primary badge-sm font-mono font-bold">&#123;&#123;{{ $i }}&#125;&#125;</span>
                                    <select name="variable_map[{{ $i }}][type]" x-model="type" class="select select-bordered select-sm w-full">
                                        <option value="contact_field">Contact field</option>
                                        <option value="custom_field">Custom field (CSV)</option>
                                        <option value="static">Static text</option>
                                    </select>
                                    <template x-if="type === 'contact_field'">
                                        <select name="variable_map[{{ $i }}][value]" class="select select-bordered select-sm w-full">
                                            <option value="name" @selected(($spec['value'] ?? '') === 'name')>Name</option>
                                            <option value="phone" @selected(($spec['value'] ?? '') === 'phone')>Phone</option>
                                            <option value="email" @selected(($spec['value'] ?? '') === 'email')>Email</option>
                                        </select>
                                    </template>
                                    <template x-if="type === 'custom_field'">
                                        @if (! empty($customFieldKeys))
                                            <select name="variable_map[{{ $i }}][value]" class="select select-bordered select-sm w-full font-mono text-xs">
                                                <option value="">— Select CSV custom field —</option>
                                                @foreach ($customFieldKeys as $cfKey)
                                                    <option value="{{ $cfKey }}" @selected(($spec['value'] ?? '') === $cfKey)>{{ $cfKey }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input name="variable_map[{{ $i }}][value]" value="{{ $spec['value'] ?? '' }}"
                                                   class="input input-bordered input-sm w-full font-mono text-xs" placeholder="Custom field key (e.g. Var2_RegNo)">
                                        @endif
                                    </template>
                                    <template x-if="type === 'static'">
                                        <input name="variable_map[{{ $i }}][value]" value="{{ $spec['value'] ?? '' }}"
                                               class="input input-bordered input-sm w-full" placeholder="Fixed text for variable {{ $i }}">
                                    </template>
                                </div>
                            @endfor
                        </div>
                    @endif

                    <div class="mt-2">
                        <label class="label"><span class="label-text">Media header (if the template has one)</span></label>
                        <select name="media_id" class="select select-bordered select-sm w-full">
                            <option value="">— none —</option>
                            @foreach ($media as $file)
                                <option value="{{ $file->id }}" @selected($campaign->media_id === $file->id)>
                                    {{ $file->original_name }} ({{ $file->category() }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs opacity-60 mt-1">
                            Upload files under <a class="link" href="{{ route('whatsapp.media.index') }}">Media</a>.
                        </p>
                    </div>

                    <button class="btn btn-sm btn-primary w-fit">Save</button>
                </div>
            </form>

            {{-- 5. Audience --}}
            <form method="POST" action="{{ route('whatsapp.campaigns.update', $campaign) }}"
                  class="card bg-base-100 border border-base-300"
                  x-data="{ type: '{{ $af['type'] ?? 'all' }}' }">
                @csrf @method('PUT')
                <div class="card-body space-y-3">
                    <h2 class="card-title text-base">4 · Audience</h2>
                    <select name="audience_filter[type]" x-model="type" class="select select-bordered w-full">
                        <option value="all">All eligible contacts</option>
                        <option value="groups">Selected groups</option>
                    </select>

                    <div x-show="type === 'groups'" class="space-y-1">
                        @foreach ($groups as $g)
                            <label class="label cursor-pointer justify-start gap-2 py-1">
                                <input type="checkbox" name="audience_filter[group_ids][]" value="{{ $g->id }}"
                                       class="checkbox checkbox-sm" @checked(in_array($g->id, $af['group_ids'] ?? []))>
                                <span class="label-text">{{ $g->name }} <span class="opacity-50">({{ $g->contacts_count }})</span></span>
                            </label>
                        @endforeach
                    </div>

                    <div>
                        <label class="label"><span class="label-text">Exclude groups (optional)</span></label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($groups as $g)
                                <label class="label cursor-pointer gap-1 py-0">
                                    <input type="checkbox" name="audience_filter[exclude_group_ids][]" value="{{ $g->id }}"
                                           class="checkbox checkbox-xs" @checked(in_array($g->id, $af['exclude_group_ids'] ?? []))>
                                    <span class="label-text text-xs">{{ $g->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-1">
                        <label class="label cursor-pointer justify-start gap-2.5 p-2.5 rounded-lg border border-base-300 bg-base-200/40">
                            <input type="hidden" name="audience_filter[exclude_already_sent_template]" value="0">
                            <input type="checkbox" name="audience_filter[exclude_already_sent_template]" value="1"
                                   class="checkbox checkbox-sm checkbox-primary"
                                   @checked(! empty($af['exclude_already_sent_template']))>
                            <div>
                                <span class="label-text text-xs font-semibold">Skip contacts who already received this template</span>
                                <span class="block text-[0.7rem] opacity-65">Prevents duplicate messages when you import new registrations later and re-run with the same template.</span>
                            </div>
                        </label>
                    </div>

                    <div class="alert alert-info text-sm">
                        <i class="ti ti-users"></i>
                        <span>Eligible recipients now: <strong>{{ number_format($audienceCount) }}</strong>.
                            Marketing templates only reach opted-in contacts.</span>
                    </div>
                    <button class="btn btn-sm btn-primary w-fit">Save audience</button>
                </div>
            </form>

            {{-- 6. Preview & test --}}
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body space-y-3">
                    <h2 class="card-title text-base">5 · Preview &amp; test</h2>
                    <div class="bg-base-200 rounded-lg p-4 text-sm whitespace-pre-line">{{ $preview ?: 'Select a template to preview.' }}</div>

                    <form method="POST" action="{{ route('whatsapp.campaigns.test', $campaign) }}" class="flex gap-2">
                        @csrf
                        <input name="test_numbers" class="input input-bordered input-sm flex-1" placeholder="Test numbers, comma-separated (max 5)">
                        <button class="btn btn-sm btn-outline"><i class="ti ti-send"></i> Send test</button>
                    </form>
                    @error('test_numbers')<p class="text-error text-xs">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Right column: WhatsApp Phone Preview + Launch panel --}}
        <div class="space-y-4">
            {{-- Live WhatsApp Phone Mockup --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider opacity-70 flex items-center gap-1.5">
                            <i class="ti ti-brand-whatsapp text-success text-base"></i> WhatsApp Preview
                        </span>
                        @if ($sampleContact)
                            <span class="badge badge-xs badge-outline font-mono">{{ $sampleContact->name ?: $sampleContact->phone_e164 }}</span>
                        @endif
                    </div>

                    <div style="background-color: #171717; border: 4px solid #262626; border-radius: 28px; padding: 8px;">
                        <div style="background-color: #075e54; color: #ffffff; padding: 8px 12px; border-top-left-radius: 20px; border-top-right-radius: 20px; display: flex; align-items: center; gap: 8px;">
                            <div style="width: 28px; height: 28px; border-radius: 9999px; background-color: #047857; display: flex; align-items: center; justify-content: center;">
                                <i class="ti ti-brand-whatsapp"></i>
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div class="text-xs font-bold truncate">{{ $campaign->phoneNumber?->display_name ?? ($campaign->organization?->name ?? 'AH&V WhatsApp') }}</div>
                                <div class="text-[0.6rem]" style="color: #a7f3d0;">Official Business</div>
                            </div>
                        </div>

                        <div style="background-color: #efeae2; padding: 12px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; min-height: 200px;">
                            <div style="background-color: #ffffff; color: #1f2937; border-radius: 10px; border-top-left-radius: 3px; padding: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.08); font-size: 12px; line-height: 1.5;">
                                @if (! empty($headerText))
                                    <div class="font-bold text-xs pb-1 mb-1 border-b border-black/10">{{ $headerText }}</div>
                                @elseif ($campaign->media)
                                    <div class="mb-2 p-2 rounded bg-emerald-50 border border-emerald-200 text-[0.68rem] text-emerald-800 flex items-center gap-1.5">
                                        <i class="ti ti-paperclip"></i> {{ $campaign->media->original_name }}
                                    </div>
                                @endif

                                <div class="whitespace-pre-line break-words">{{ $preview ?: 'Select a template and save variables to see the WhatsApp preview.' }}</div>

                                @if (! empty($footerText))
                                    <div class="text-[0.65rem] mt-1.5 pt-1 border-t border-black/5" style="color: #6b7280;">{{ $footerText }}</div>
                                @endif

                                <div class="flex items-center justify-end gap-1 text-[0.6rem] mt-1" style="color: #6b7280;">
                                    <span>{{ now('Asia/Kolkata')->format('h:i a') }}</span>
                                    <i class="ti ti-checks" style="color: #0ea5e9;"></i>
                                </div>

                                @if (! empty($templateButtons))
                                    <div class="mt-2 pt-1.5 border-t border-black/10 space-y-1">
                                        @foreach ($templateButtons as $btn)
                                            <div class="text-center py-1 px-2 rounded text-xs font-semibold border"
                                                 style="background-color: #f9fafb; color: #00a884; border-color: #e5e7eb;">
                                                <i class="ti {{ ($btn['type'] ?? '') === 'URL' ? 'ti-external-link' : (($btn['type'] ?? '') === 'PHONE_NUMBER' ? 'ti-phone' : 'ti-arrow-back-up') }} text-xs"></i>
                                                {{ $btn['text'] ?? 'Button' }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 sticky top-20">
                <div class="card-body">
                    <h2 class="card-title text-base">6 · Launch</h2>

                    @if (count($validationErrors) > 0)
                        <ul class="text-sm text-error list-disc ml-4 space-y-1">
                            @foreach ($validationErrors as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    @else
                        <div class="alert alert-success text-sm"><i class="ti ti-check"></i><span>Ready to launch.</span></div>

                        <form method="POST" action="{{ route('whatsapp.campaigns.launch', $campaign) }}"
                              x-data="{ mode: 'now' }" class="space-y-3">
                            @csrf
                            <div class="tabs tabs-boxed w-full">
                                <button type="button" class="tab flex-1" :class="mode==='now' && 'tab-active'" x-on:click="mode='now'">Send now</button>
                                <button type="button" class="tab flex-1" :class="mode==='schedule' && 'tab-active'" x-on:click="mode='schedule'">Schedule</button>
                            </div>
                            <input type="hidden" name="mode" x-model="mode">
                            <input x-show="mode==='schedule'" type="datetime-local" name="scheduled_at" class="input input-bordered input-sm w-full">
                            <p class="text-xs opacity-60">Times are in <strong>{{ $campaign->timezone }}</strong>.</p>

                            <div class="text-sm space-y-1 border-t border-base-300 pt-2">
                                <div>Recipients: <strong>{{ number_format($audienceCount) }}</strong></div>
                                <div>Template: <strong>{{ $campaign->template?->name ?? '—' }}</strong></div>
                                <div>Send mode: <strong>{{ ($campaign->send_delay_seconds ?? 0) > 0 ? 'Throttled ('.$campaign->send_delay_seconds.'s)' : 'Standard' }}</strong></div>
                            </div>

                            <label class="label cursor-pointer justify-start gap-2">
                                <input type="checkbox" name="confirm" value="1" class="checkbox checkbox-sm" required>
                                <span class="label-text">I confirm this campaign and its audience.</span>
                            </label>
                            @error('confirm')<p class="text-error text-xs">{{ $message }}</p>@enderror

                            <button class="btn btn-primary w-full" data-confirm="Launch “{{ $campaign->name }}” to {{ number_format($audienceCount) }} recipients?">
                                <i class="ti ti-rocket"></i> Launch campaign
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('whatsapp.campaigns.destroy', $campaign) }}" class="mt-2"
                          data-confirm="Delete this draft campaign?">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm btn-block text-error">Delete draft</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
