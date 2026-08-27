<x-app-layout>
    <x-slot name="title">Emergency Controls</x-slot>

    @php($isSuper = auth()->user()->isSuperAdmin())

    <div class="max-w-2xl space-y-6">
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle"></i>
            <span>These controls take effect immediately across the whole platform.</span>
        </div>

        {{-- Global sending kill switch --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Outbound WhatsApp sending</h2>
                <p class="text-sm opacity-70">
                    When disabled, no queued job will call Meta. In-flight requests may still complete.
                    @unless ($configSendingEnabled)
                        <br><strong class="text-warning">Also disabled in configuration (WHATSAPP_SENDING_ENABLED=false).</strong>
                    @endunless
                </p>
                <div class="flex items-center gap-3 mt-2">
                    <span class="badge {{ $sendingEnabled ? 'badge-success' : 'badge-error' }}">
                        {{ $sendingEnabled ? 'Enabled' : 'Disabled' }}
                    </span>
                    <form method="POST" action="{{ route('admin.controls.sending') }}"
                          data-confirm="{{ $sendingEnabled ? 'Disable all outbound WhatsApp sending?' : '' }}">
                        @csrf
                        <input type="hidden" name="enable" value="{{ $sendingEnabled ? 0 : 1 }}">
                        <button class="btn btn-sm {{ $sendingEnabled ? 'btn-error' : 'btn-success' }}">
                            {{ $sendingEnabled ? 'Disable sending' : 'Enable sending' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Pause all campaigns --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Campaigns</h2>
                <p class="text-sm opacity-70">{{ $activeCampaigns }} campaign(s) currently processing.</p>
                <form method="POST" action="{{ route('admin.controls.pause-campaigns') }}" class="mt-2"
                      data-confirm="Pause every running campaign now?">
                    @csrf
                    <button class="btn btn-sm btn-warning" @disabled($activeCampaigns === 0)>
                        <i class="ti ti-player-pause"></i> Pause all campaigns
                    </button>
                </form>
            </div>
        </div>

        {{-- Revoke integration (super admin) --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">WhatsApp Business Accounts</h2>
                @forelse ($wabaAccounts as $acct)
                    <div class="flex items-center justify-between gap-3 py-1">
                        <div>
                            <div class="font-medium">{{ $acct->name }}</div>
                            <div class="text-xs opacity-60">
                                {{ $acct->is_active ? 'Active' : 'Revoked' }} · WABA {{ $acct->waba_id }}
                            </div>
                        </div>
                        @if ($isSuper && $acct->is_active)
                            <form method="POST" action="{{ route('admin.controls.revoke', $acct) }}"
                                  data-confirm="Revoke this integration? No messages will be sent through it until re-activated.">
                                @csrf
                                <button class="btn btn-xs btn-error btn-outline">Revoke</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm opacity-60">No WhatsApp Business Account configured.</p>
                @endforelse
                @unless ($isSuper)
                    <p class="text-xs opacity-50 mt-2">Revoking an integration requires super-administrator access.</p>
                @endunless
            </div>
        </div>
    </div>

</x-app-layout>
