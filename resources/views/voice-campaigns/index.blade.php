<x-app-layout>
    <x-slot name="title">Voice Campaigns</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm opacity-70">Automated calls that play a recorded audio clip to a list of contacts.</p>
            @can('create', \App\Models\VoiceCampaign::class)
                <div class="flex gap-2">
                    <a href="{{ route('whatsapp.voice-campaigns.quick') }}" class="btn btn-sm btn-ghost">
                        <i class="ti ti-phone-outgoing"></i> Quick call
                    </a>
                    <a href="{{ route('whatsapp.voice-campaigns.create') }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i> New voice campaign
                    </a>
                </div>
            @endcan
        </div>

        <div class="alert alert-warning text-sm">
            <i class="ti ti-gavel"></i>
            <span>India: recorded promotional calls need TRAI DLT registration, run only 9am–9pm, and must skip
                DND/NCPR numbers without consent. You are responsible for compliance.</span>
        </div>

        @if ($campaigns->isEmpty())
            <div class="alert text-sm"><i class="ti ti-info-circle"></i>
                <span>No voice campaigns yet.</span></div>
        @else
            <div class="card bg-base-100 border border-base-300 overflow-x-auto">
                <table class="table" id="voice-campaigns-table" data-datatable data-order='[[3,"desc"]]'>
                    <thead><tr><th>Name</th><th>Status</th><th>Recipients</th><th>Created</th></tr></thead>
                    <tbody>
                        @foreach ($campaigns as $c)
                            <tr class="hover cursor-pointer" data-href="{{ route('whatsapp.voice-campaigns.show', $c) }}">
                                <td><a class="link link-hover" href="{{ route('whatsapp.voice-campaigns.show', $c) }}">{{ $c->name }}</a></td>
                                <td>
                                    @php($sm = ['completed'=>'badge-success','processing'=>'badge-info','scheduled'=>'badge-ghost','paused'=>'badge-warning','cancelled'=>'badge-error'])
                                    <span class="badge badge-sm {{ $sm[$c->status->value] ?? 'badge-ghost' }}">{{ $c->status->value }}</span>
                                </td>
                                <td>{{ number_format($c->recipients_count) }}</td>
                                <td class="text-xs opacity-60" data-order="{{ $c->created_at?->timestamp ?? 0 }}">{{ $c->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
