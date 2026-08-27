<x-app-layout>
    <x-slot name="title">Campaigns</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::CampaignManage->value))

    <div class="space-y-4">
        <div class="flex items-end justify-between flex-wrap gap-3">
            <x-dt-filter label="Status" target="#campaigns-table" :col="1">
                <option value="">Any status</option>
                @foreach (\App\Enums\CampaignStatus::cases() as $s)
                    <option value="{{ ucfirst($s->value) }}">{{ ucfirst($s->value) }}</option>
                @endforeach
            </x-dt-filter>
            @if ($canManage)
                <form method="POST" action="{{ route('whatsapp.campaigns.store') }}" class="flex gap-2">
                    @csrf
                    <input name="name" class="input input-bordered input-sm" placeholder="New campaign name" required>
                    <button class="btn btn-sm btn-primary"><i class="ti ti-plus"></i> Create</button>
                </form>
            @endif
        </div>

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table" id="campaigns-table" data-datatable data-order='[[4,"desc"]]' data-no-sort="5">
                <thead><tr><th>Name</th><th>Status</th><th>Template</th><th>Recipients</th><th>Scheduled</th><th></th></tr></thead>
                <tbody>
                    @foreach ($campaigns as $c)
                        <tr class="hover">
                            <td><a class="link link-hover font-medium" href="{{ $c->status === \App\Enums\CampaignStatus::Draft ? route('whatsapp.campaigns.edit', $c) : route('whatsapp.campaigns.report', $c) }}">{{ $c->name }}</a></td>
                            <td>
                                @php($m = ['completed'=>'badge-success','processing'=>'badge-info','paused'=>'badge-warning','failed'=>'badge-error','cancelled'=>'badge-ghost'])
                                <span class="badge badge-sm {{ $m[$c->status->value] ?? 'badge-ghost' }}">{{ ucfirst($c->status->value) }}</span>
                            </td>
                            <td>{{ $c->template?->name ?? '—' }}</td>
                            <td data-order="{{ $c->recipients_count }}">{{ number_format($c->recipients_count) }}</td>
                            <td class="text-xs opacity-60" data-order="{{ $c->scheduled_at?->timestamp ?? 0 }}">{{ $c->scheduled_at?->timezone($c->timezone)->format('d M H:i') ?? '—' }}</td>
                            <td class="text-right">
                                @if ($c->status === \App\Enums\CampaignStatus::Draft)
                                    <a href="{{ route('whatsapp.campaigns.edit', $c) }}" class="btn btn-xs btn-ghost">Edit</a>
                                @else
                                    <a href="{{ route('whatsapp.campaigns.report', $c) }}" class="btn btn-xs btn-ghost">Report</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
