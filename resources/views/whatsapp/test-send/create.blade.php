<x-app-layout>
    <x-slot name="title">Send Test Message</x-slot>

    <div class="max-w-xl space-y-4">
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

                <div x-show="mode === 'template'" x-data="{ tpl: null }">
                    <label class="label"><span class="label-text">Approved template</span></label>
                    <select name="template_id" class="select select-bordered w-full" x-on:change="tpl = $event.target.selectedOptions[0]?.dataset">
                        <option value="">— select —</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}" data-vars="{{ count($t->variablePlaceholders()) }}">{{ $t->name }} ({{ $t->language }})</option>
                        @endforeach
                    </select>
                    @error('template_id')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
                    <template x-if="tpl && Number(tpl.vars) > 0">
                        <div class="mt-2 space-y-2">
                            <template x-for="i in Number(tpl.vars)" :key="i">
                                <input :name="`variables[${i-1}]`" class="input input-bordered input-sm w-full" x-bind:placeholder="'Value for variable ' + i">
                            </template>
                        </div>
                    </template>
                </div>

                <button class="btn btn-primary"><i class="ti ti-send"></i> Send test</button>
            </div>
        </form>
    </div>
</x-app-layout>
