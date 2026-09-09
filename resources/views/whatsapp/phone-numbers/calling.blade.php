<x-app-layout>
    <x-slot name="title">WhatsApp Calling</x-slot>

    <div class="max-w-2xl space-y-4">
        <a href="{{ route('whatsapp.phone-numbers.index') }}" class="btn btn-ghost btn-sm">
            <i class="ti ti-arrow-left"></i> Phone Numbers
        </a>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body gap-3">
                <h2 class="card-title text-base"><i class="ti ti-phone"></i> WhatsApp Business Calling</h2>
                <p class="text-sm opacity-70">
                    Live status for <span class="font-mono">{{ $number?->display_phone_number ?? $number?->phone_number_id ?? '—' }}</span>,
                    read from Meta just now (<code>GET /settings?fields=calling</code>).
                </p>

                @if (! $account)
                    <div class="alert alert-warning text-sm"><i class="ti ti-alert-triangle"></i>
                        <span>Configure your WhatsApp Business Account first under
                            <a class="link" href="{{ route('whatsapp.settings.edit') }}">Settings</a>.</span>
                    </div>
                @elseif ($error)
                    <div class="alert alert-error text-sm">
                        <i class="ti ti-x"></i>
                        <span>
                            Meta returned an error — calling is most likely <strong>not available</strong> for this
                            account yet:<br>
                            <span class="font-mono text-xs">{{ $error }}</span>
                        </span>
                    </div>
                @else
                    @php($status = strtoupper((string) ($settings['status'] ?? 'NOT_SET')))
                    <div class="flex items-center gap-2">
                        <span class="text-sm opacity-60">Calling status:</span>
                        <span class="badge {{ $status === 'ENABLED' ? 'badge-success' : 'badge-ghost' }}">{{ $status }}</span>
                    </div>

                    @if ($settings === [])
                        <p class="text-sm opacity-70">
                            The API responded, but no calling configuration exists for this number yet. That usually
                            means the feature is reachable for your account but has never been turned on.
                        </p>
                    @else
                        <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                            @foreach ($settings as $key => $value)
                                <div>
                                    <span class="opacity-60">{{ Str::headline((string) $key) }}:</span>
                                    <span class="font-mono text-xs">{{ is_scalar($value) ? $value : json_encode($value) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                <div class="divider text-xs my-1">What this means</div>

                <ul class="text-sm space-y-1.5 opacity-80 list-disc ml-4">
                    <li>
                        When status is <strong>ENABLED</strong>, a call icon appears in the customer's chat with your
                        business and they can place a WhatsApp voice call to this number.
                    </li>
                    <li>
                        Meta requires a messaging limit of <strong>1,000+ business-initiated conversations / 24h</strong>
                        (higher on some tiers) and a GREEN-ish quality rating to enable it.
                        This number: <span class="font-mono">{{ $number?->messaging_limit_tier ?? '—' }}</span>,
                        quality <span class="font-mono">{{ $number?->quality_rating ?? '—' }}</span>.
                    </li>
                    <li>
                        Enabling the status is <strong>not enough on its own</strong>. An incoming WhatsApp call sends an
                        SDP offer to the <code>calls</code> webhook; something has to answer it over WebRTC or SIP.
                        Without that media layer (a media server or a BSP that provides one) the call button would show
                        but calls would not connect — so we don't flip it on from here yet.
                    </li>
                </ul>

                <a href="{{ route('whatsapp.phone-numbers.calling') }}" class="btn btn-sm btn-outline w-fit" data-turbo="false">
                    <i class="ti ti-refresh"></i> Re-check
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
