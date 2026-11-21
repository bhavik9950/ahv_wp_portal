<x-app-layout>
    <x-slot name="title">Phone Numbers</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::WabaManage->value))

    <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <p class="text-sm opacity-70">Phone numbers registered on your WhatsApp Business Account.</p>
            <div class="flex gap-2">
                @if ($canManage && $account)
                    <button type="button" onclick="document.getElementById('add-phone-modal').showModal()" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i> Add Phone ID
                    </button>
                    <form method="POST" action="{{ route('whatsapp.phone-numbers.sync') }}" data-turbo="false">
                        @csrf
                        <button class="btn btn-sm btn-outline"><i class="ti ti-refresh"></i> Sync from Meta</button>
                    </form>
                @endif
                @if ($account)
                    <a href="{{ route('whatsapp.phone-numbers.calling') }}" class="btn btn-sm btn-ghost">
                        <i class="ti ti-phone"></i> Calling status
                    </a>
                @endif
            </div>
        </div>

        @if (! $account)
            <div class="alert alert-warning"><i class="ti ti-alert-triangle"></i>
                <span>Configure your WhatsApp Business Account first under
                    <a class="link" href="{{ route('whatsapp.settings.edit') }}">Settings</a>.</span>
            </div>
        @else
            <div class="card bg-base-100 border border-base-300 overflow-x-auto">
                <table class="table" id="phone-numbers-table" data-datatable data-no-sort="5">
                    <thead>
                        <tr>
                            <th>Number</th><th>Verified name</th><th>Quality</th>
                            <th>Limit tier</th><th>Status</th><th>Default</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($numbers as $n)
                            <tr>
                                <td class="font-mono">{{ $n->display_phone_number ?? $n->phone_number_id }}</td>
                                <td>{{ $n->verified_name ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-sm {{ $n->quality_rating === 'GREEN' ? 'badge-success' : ($n->quality_rating === 'RED' ? 'badge-error' : 'badge-ghost') }}">
                                        {{ $n->quality_rating ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $tierMap = [
                                            'TIER_250' => '250 / 24h',
                                            'TIER_1K' => '1,000 / 24h',
                                            'TIER_2K' => '2,000 / 24h',
                                            'TIER_10K' => '10,000 / 24h',
                                            'TIER_100K' => '100,000 / 24h',
                                            'TIER_UNLIMITED' => 'Unlimited',
                                        ];
                                        $displayTier = $tierMap[$n->messaging_limit_tier] ?? $n->messaging_limit_tier;
                                    @endphp
                                    <span class="badge badge-sm {{ $n->messaging_limit_tier ? 'badge-primary badge-outline font-bold' : 'badge-ghost' }} font-mono">
                                        {{ $displayTier ?: 'Check Meta' }}
                                    </span>
                                </td>
                                <td>{{ ucfirst($n->status) }}</td>
                                <td>
                                    @if ($n->is_default)
                                        <span class="badge badge-primary badge-sm">Default</span>
                                    @elseif ($canManage)
                                        <form method="POST" action="{{ route('whatsapp.phone-numbers.default', $n) }}">
                                            @csrf
                                            <button class="btn btn-xs btn-ghost">Make default</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($canManage && $account)
        <dialog id="add-phone-modal" class="modal">
            <div class="modal-box max-w-md">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-2 flex items-center gap-2">
                    <i class="ti ti-phone-plus text-primary"></i> Add Phone Number ID
                </h3>
                <p class="text-xs opacity-70 mb-4">
                    Paste your Meta Phone Number ID (from Meta Developer Console ▸ WhatsApp ▸ API Setup).
                </p>

                <form method="POST" action="{{ route('whatsapp.phone-numbers.store') }}" class="space-y-4">
                    @csrf
                    <div class="form-control">
                        <label class="label pb-1" for="modal_phone_number_id">
                            <span class="label-text font-medium text-xs">Phone Number ID <span class="text-error">*</span></span>
                        </label>
                        <input id="modal_phone_number_id"
                               type="text"
                               name="phone_number_id"
                               required
                               placeholder="e.g. 1453599307834671"
                               class="input input-bordered w-full font-mono text-sm" />
                    </div>

                    <div class="form-control">
                        <label class="label pb-1" for="modal_display_number">
                            <span class="label-text font-medium text-xs">Display Phone Number (Optional)</span>
                        </label>
                        <input id="modal_display_number"
                               type="text"
                               name="display_phone_number"
                               placeholder="e.g. +91 98765 43210"
                               class="input input-bordered w-full text-sm" />
                    </div>

                    <div class="modal-action">
                        <button type="button" onclick="document.getElementById('add-phone-modal').close()" class="btn btn-sm btn-ghost">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary gap-1">
                            <i class="ti ti-check"></i> Save Number
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    @endif
</x-app-layout>
