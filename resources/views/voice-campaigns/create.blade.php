<x-app-layout>
    <x-slot name="title">New Voice Campaign</x-slot>

    <div class="max-w-2xl space-y-4">
        <a href="{{ route('whatsapp.voice-campaigns.index') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Voice Campaigns</a>

        @unless ($providerConfigured)
            <div class="alert alert-error text-sm">
                <i class="ti ti-x"></i>
                <span>The voice provider is not configured. Set <code>VOICE_DRIVER</code>, <code>VOICE_USER_ID</code>
                    and <code>VOICE_PASSWORD</code> in the server <code>.env</code> first.</span>
            </div>
        @endunless

        @if ($errors->any())
            <div class="alert alert-error text-sm">
                <ul class="list-disc ml-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('whatsapp.voice-campaigns.store') }}" enctype="multipart/form-data"
              x-data="{ audience: '{{ old('audience_type', 'groups') }}', mode: '{{ old('mode', 'now') }}' }"
              data-loading data-loading-text="Starting…"
              class="card bg-base-100 border border-base-300">
            @csrf
            <div class="card-body space-y-4">

                <div>
                    <label class="label"><span class="label-text">Campaign name</span></label>
                    <input name="name" value="{{ old('name') }}" required maxlength="120"
                           class="input input-bordered w-full" placeholder="Diwali wishes 2026">
                </div>

                <div>
                    <label class="label"><span class="label-text">Audio clip</span></label>
                    <input type="file" name="audio" required accept=".mp3,.ogg,.m4a,.aac,.amr"
                           class="file-input file-input-bordered w-full">
                    <p class="text-xs opacity-60 mt-1">MP3 / OGG / M4A / AAC / AMR, up to 16 MB. This plays when the call connects.</p>
                </div>

                <div>
                    <label class="label"><span class="label-text">Who to call</span></label>
                    <select name="audience_type" x-model="audience" class="select select-bordered w-full">
                        <option value="groups">Selected groups</option>
                        <option value="all">All contacts</option>
                    </select>

                    <div x-show="audience === 'groups'" class="mt-2 space-y-1 max-h-52 overflow-y-auto rounded-lg border border-base-300 p-2">
                        @forelse ($groups as $g)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="group_ids[]" value="{{ $g->id }}" class="checkbox checkbox-sm"
                                       @checked(collect(old('group_ids'))->contains($g->id))>
                                {{ $g->name }} <span class="opacity-50">({{ $g->contacts_count }})</span>
                            </label>
                        @empty
                            <p class="text-xs opacity-60">No groups yet.</p>
                        @endforelse
                    </div>
                    <p class="text-xs opacity-60 mt-1">Opted-out contacts are always excluded.</p>
                </div>

                <div>
                    <label class="label"><span class="label-text">Delay between calls (seconds)</span></label>
                    <input type="number" name="delay_seconds" value="{{ old('delay_seconds', 8) }}" min="0" max="120"
                           class="input input-bordered w-full">
                    <p class="text-xs opacity-60 mt-1">A gap of 5–10s spreads the load and looks less like a burst.</p>
                </div>

                <div>
                    <label class="label"><span class="label-text">When</span></label>
                    <select name="mode" x-model="mode" class="select select-bordered w-full">
                        <option value="now">Start now</option>
                        <option value="schedule">Schedule for later</option>
                    </select>
                    <input x-show="mode === 'schedule'" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                           class="input input-bordered w-full mt-2">
                </div>

                <label class="flex items-start gap-2 text-sm rounded-lg bg-warning/10 border border-warning/30 p-3">
                    <input type="checkbox" name="consent_confirmed" value="1" class="checkbox checkbox-sm mt-0.5" required
                           @checked(old('consent_confirmed'))>
                    <span>I confirm every recipient has consented to receive calls from us, and this campaign follows
                        TRAI DLT / DND rules and the 9am–9pm window. I understand violations carry per-call penalties.</span>
                </label>

                <div class="flex gap-2">
                    <button class="btn btn-primary" @disabled(! $providerConfigured)><i class="ti ti-phone-outgoing"></i> Start campaign</button>
                    <a href="{{ route('whatsapp.voice-campaigns.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
