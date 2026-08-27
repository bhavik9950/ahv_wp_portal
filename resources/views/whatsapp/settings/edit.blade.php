<x-app-layout>
    <x-slot name="title">WhatsApp Settings</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::WabaManage->value))

    <div class="max-w-3xl space-y-6">
        @if (! $account && $prefill && ($prefill['has_token'] ?? false))
            <div class="alert alert-info text-sm">
                <i class="ti ti-info-circle"></i>
                <span>
                    Bootstrap values were found in <code>.env</code> and pre-filled below.
                    @if (blank($prefill['waba_id']))
                        <strong>Add your WhatsApp Business Account ID</strong> (Meta ▸ App ▸ WhatsApp ▸ API Setup — the number above "Phone number ID"),
                    @endif
                    then <strong>Save</strong> to activate. Leave the secret fields blank to use the ones from <code>.env</code>.
                    You can also run <code>php artisan waba:setup --waba-id=&lt;id&gt;</code>.
                </span>
            </div>
        @endif

        {{-- Connection status --}}
        @if ($account)
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <h2 class="card-title text-base">{{ $account->name }}</h2>
                            <p class="text-xs opacity-60">WABA ID {{ $account->waba_id }}</p>
                        </div>
                        <div class="flex gap-2">
                            @php($s = $account->connection_status)
                            <span class="badge {{ $s === 'connected' ? 'badge-success' : ($s === 'error' ? 'badge-error' : 'badge-ghost') }}">
                                <i class="ti ti-plug-connected"></i>&nbsp;{{ ucfirst($s) }}
                            </span>
                            <span class="badge {{ $account->token_status === 'valid' ? 'badge-success' : 'badge-ghost' }}">
                                <i class="ti ti-key"></i>&nbsp;Token: {{ ucfirst($account->token_status) }}
                            </span>
                        </div>
                    </div>

                    @if ($canManage)
                        <form method="POST" action="{{ route('whatsapp.settings.check') }}" class="mt-3">
                            @csrf
                            <button class="btn btn-sm btn-outline"><i class="ti ti-refresh"></i> Run connection checks</button>
                        </form>
                    @endif

                    @if (! empty($checks))
                        <ul class="mt-4 space-y-1 text-sm">
                            @foreach ($checks as $c)
                                <li class="flex items-start gap-2">
                                    <i class="ti {{ $c['passed'] ? 'ti-circle-check text-success' : 'ti-circle-x text-error' }}"></i>
                                    <span><strong>{{ $c['label'] }}:</strong> {{ $c['message'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif

        {{-- Configuration form --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Configuration</h2>
                <p class="text-xs opacity-60 mb-2">
                    Credentials are encrypted at rest and never displayed in full. Leave a secret
                    field blank to keep the current value.
                </p>

                <form method="POST" action="{{ route('whatsapp.settings.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <fieldset @disabled(! $canManage) class="space-y-4">

                        @php($field = fn($name, $label, $value = null, $type = 'text', $ph = null) => view('whatsapp.settings.field', compact('name','label','value','type','ph')))

                        {!! $field('name', 'Display name', old('name', $account->name ?? config('app.name').' WABA')) !!}
                        {!! $field('waba_id', 'WhatsApp Business Account ID', old('waba_id', $account->waba_id ?? $prefill['waba_id'] ?? '')) !!}
                        {!! $field('meta_business_account_id', 'Meta Business Account ID (optional)', old('meta_business_account_id', $account->meta_business_account_id ?? $prefill['meta_business_account_id'] ?? '')) !!}
                        {!! $field('app_id', 'App ID (optional)', old('app_id', $account->app_id ?? $prefill['app_id'] ?? '')) !!}
                        {!! $field('api_version', 'Graph API version (optional)', old('api_version', $account->api_version ?? ''), 'text', config('services.whatsapp.api_version')) !!}
                        {!! $field('default_country_code', 'Default country code', old('default_country_code', $account->default_country_code ?? config('services.whatsapp.default_country_code'))) !!}

                        <div class="divider text-xs">Secrets</div>

                        {!! $field('access_token', 'Access token', null, 'password', $account?->maskedAccessToken() ?? (($prefill['has_token'] ?? false) ? 'using .env value' : 'not set')) !!}
                        {!! $field('app_secret', 'App secret (for webhook signature)', null, 'password', $account?->maskedAppSecret() ?? (($prefill['has_app_secret'] ?? false) ? 'using .env value' : 'not set')) !!}
                        {!! $field('webhook_verify_token', 'Webhook verify token', null, 'password', ($account && $account->hasWebhookVerifyToken()) ? 'set' : (($prefill['has_verify_token'] ?? false) ? 'using .env value' : 'not set')) !!}
                    </fieldset>

                    @if ($canManage)
                        <button class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save settings</button>
                    @else
                        <p class="text-sm opacity-60">You have read-only access to WhatsApp settings.</p>
                    @endif
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
