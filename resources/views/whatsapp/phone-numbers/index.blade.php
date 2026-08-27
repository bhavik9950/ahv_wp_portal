<x-app-layout>
    <x-slot name="title">Phone Numbers</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::WabaManage->value))

    <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <p class="text-sm opacity-70">Phone numbers registered on your WhatsApp Business Account.</p>
            @if ($canManage && $account)
                <form method="POST" action="{{ route('whatsapp.phone-numbers.sync') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline"><i class="ti ti-refresh"></i> Sync from Meta</button>
                </form>
            @endif
        </div>

        @if (! $account)
            <div class="alert alert-warning"><i class="ti ti-alert-triangle"></i>
                <span>Configure your WhatsApp Business Account first under
                    <a class="link" href="{{ route('whatsapp.settings.edit') }}">Settings</a>.</span>
            </div>
        @else
            <div class="card bg-base-100 border border-base-300 overflow-x-auto">
                <table class="table" data-datatable>
                    <thead>
                        <tr>
                            <th>Number</th><th>Verified name</th><th>Quality</th>
                            <th>Limit tier</th><th>Status</th><th>Default</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($numbers as $n)
                            <tr>
                                <td class="font-mono">{{ $n->display_phone_number ?? $n->phone_number_id }}</td>
                                <td>{{ $n->verified_name ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-sm {{ $n->quality_rating === 'GREEN' ? 'badge-success' : ($n->quality_rating === 'RED' ? 'badge-error' : 'badge-ghost') }}">
                                        {{ $n->quality_rating ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $n->messaging_limit_tier ?? '—' }}</td>
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
                        @empty
                            <tr><td colspan="6" class="text-center opacity-60 py-6">No phone numbers synced yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
