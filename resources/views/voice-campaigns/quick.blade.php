<x-app-layout>
    <x-slot name="title">Quick Voice Call</x-slot>

    <div class="max-w-lg space-y-4">
        <a href="{{ route('whatsapp.voice-campaigns.index') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Voice Campaigns</a>

        <p class="text-sm opacity-70">Place one recorded call to a single number — handy for testing a clip before a broadcast.</p>

        @unless ($providerConfigured)
            <div class="alert alert-error text-sm"><i class="ti ti-x"></i>
                <span>The voice provider is not configured (<code>VOICE_*</code> in the server <code>.env</code>).</span></div>
        @endunless

        @if ($errors->any())
            <div class="alert alert-error text-sm">
                <ul class="list-disc ml-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('whatsapp.voice-campaigns.quick.store') }}" enctype="multipart/form-data"
              data-loading data-loading-text="Calling…" class="card bg-base-100 border border-base-300">
            @csrf
            <div class="card-body space-y-4">
                <div>
                    <label class="label"><span class="label-text">Phone number</span></label>
                    <input name="phone" value="{{ old('phone') }}" required class="input input-bordered w-full" placeholder="+91 98765 43210">
                </div>
                <div>
                    <label class="label"><span class="label-text">Audio clip</span></label>
                    <input type="file" name="audio" required accept=".mp3,.ogg,.m4a,.aac,.amr" class="file-input file-input-bordered w-full">
                    <p class="text-xs opacity-60 mt-1">MP3 / OGG / M4A / AAC / AMR, up to 16 MB.</p>
                </div>
                <label class="flex items-start gap-2 text-sm rounded-lg bg-warning/10 border border-warning/30 p-3">
                    <input type="checkbox" name="consent_confirmed" value="1" class="checkbox checkbox-sm mt-0.5" required @checked(old('consent_confirmed'))>
                    <span>This recipient has consented to receive a call from us.</span>
                </label>
                <div class="flex gap-2">
                    <button class="btn btn-primary" @disabled(! $providerConfigured)><i class="ti ti-phone-outgoing"></i> Call now</button>
                    <a href="{{ route('whatsapp.voice-campaigns.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
