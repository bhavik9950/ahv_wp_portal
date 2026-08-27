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
                        <h3 class="font-medium text-sm mt-2">3 · Map {{ $placeholders }} variable(s)</h3>
                        @for ($i = 1; $i <= $placeholders; $i++)
                            @php($spec = $vm[(string) $i] ?? ['type' => 'static', 'value' => ''])
                            <div class="grid grid-cols-2 gap-2" x-data="{ type: '{{ $spec['type'] }}' }">
                                <select name="variable_map[{{ $i }}][type]" x-model="type" class="select select-bordered select-sm">
                                    <option value="static">Static text</option>
                                    <option value="contact_field">Contact field</option>
                                    <option value="custom_field">Custom field</option>
                                </select>
                                <template x-if="type === 'contact_field'">
                                    <select name="variable_map[{{ $i }}][value]" class="select select-bordered select-sm">
                                        <option value="name" @selected(($spec['value'] ?? '') === 'name')>Name</option>
                                        <option value="phone" @selected(($spec['value'] ?? '') === 'phone')>Phone</option>
                                        <option value="email" @selected(($spec['value'] ?? '') === 'email')>Email</option>
                                    </select>
                                </template>
                                <template x-if="type !== 'contact_field'">
                                    <input name="variable_map[{{ $i }}][value]" value="{{ $spec['value'] ?? '' }}"
                                           class="input input-bordered input-sm" placeholder="Value for variable {{ $i }}">
                                </template>
                            </div>
                        @endfor
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

        {{-- Launch panel --}}
        <div class="space-y-4">
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
