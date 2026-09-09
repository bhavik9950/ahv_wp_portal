<x-app-layout>
    <x-slot name="title">{{ $campaign->name }}</x-slot>

    @php($st = $campaign->status->value)

    <div class="max-w-3xl space-y-4" @if (in_array($st, ['processing','scheduled'])) data-auto-refresh="15" @endif>
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <a href="{{ route('whatsapp.voice-campaigns.index') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Voice Campaigns</a>
            <div class="flex gap-2">
                @can('launch', $campaign)
                    @if (in_array($st, ['processing','scheduled']))
                        <form method="POST" action="{{ route('whatsapp.voice-campaigns.pause', $campaign) }}">@csrf
                            <button class="btn btn-sm btn-warning btn-outline"><i class="ti ti-player-pause"></i> Pause</button></form>
                    @elseif ($st === 'paused')
                        <form method="POST" action="{{ route('whatsapp.voice-campaigns.resume', $campaign) }}">@csrf
                            <button class="btn btn-sm btn-success btn-outline"><i class="ti ti-player-play"></i> Resume</button></form>
                    @endif
                    @if (in_array($st, ['processing','scheduled','paused']))
                        <form method="POST" action="{{ route('whatsapp.voice-campaigns.cancel', $campaign) }}"
                              data-confirm="Cancel this voice campaign? Pending calls will be skipped.">@csrf
                            <button class="btn btn-sm btn-error btn-outline"><i class="ti ti-x"></i> Cancel</button></form>
                    @endif
                @endcan
            </div>
        </div>

        @error('launch')<div class="alert alert-error text-sm"><i class="ti ti-x"></i><span>{{ $message }}</span></div>@enderror

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body gap-3">
                <div class="flex items-center gap-2">
                    <h2 class="card-title text-base">{{ $campaign->name }}</h2>
                    @php($sm = ['completed'=>'badge-success','processing'=>'badge-info','scheduled'=>'badge-ghost','paused'=>'badge-warning','cancelled'=>'badge-error'])
                    <span class="badge {{ $sm[$st] ?? 'badge-ghost' }}">{{ $st }}</span>
                </div>
                <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <div><span class="opacity-60">Audio:</span> {{ $campaign->audio?->original_name ?? '—' }}</div>
                    <div><span class="opacity-60">Delay between calls:</span> {{ $campaign->delay_seconds }}s</div>
                    <div><span class="opacity-60">Scheduled:</span> {{ $campaign->scheduled_at?->timezone($campaign->timezone)->format('d M Y, H:i') ?? 'Immediate' }}</div>
                    <div><span class="opacity-60">Started:</span> {{ $campaign->started_at?->timezone($campaign->timezone)->format('d M Y, H:i') ?? '—' }}</div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-1">
                    @foreach ([
                        ['Total', $totals['total'] ?? 0, 'opacity-70'],
                        ['Answered', $totals['answered'] ?? 0, 'text-success'],
                        ['Failed', $totals['failed'] ?? 0, 'text-error'],
                        ['Pending', ($totals['pending'] ?? 0) + ($totals['queued'] ?? 0) + ($totals['processing'] ?? 0) + ($totals['placed'] ?? 0), 'text-info'],
                    ] as [$label, $value, $color])
                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body p-3">
                                <div class="text-lg font-semibold leading-none {{ $color }}">{{ number_format($value) }}</div>
                                <div class="text-[0.7rem] uppercase tracking-wide opacity-50 mt-0.5">{{ $label }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table table-sm" id="voice-recipients-table" data-datatable>
                <thead><tr><th>Contact</th><th>Number</th><th>Status</th><th>Duration</th><th>Called</th></tr></thead>
                <tbody>
                    @foreach ($recipients as $r)
                        <tr>
                            <td>{{ $r->contact?->name ?? '—' }}</td>
                            <td class="font-mono">+{{ $r->phone_e164 }}</td>
                            <td>
                                @php($rm = ['answered'=>'badge-success','failed'=>'badge-error','placed'=>'badge-info','skipped'=>'badge-ghost'])
                                <span class="badge badge-sm {{ $rm[$r->status->value] ?? 'badge-ghost' }}">{{ $r->status->value }}</span>
                                @if ($r->error_message)<span class="text-error text-xs">· {{ \Illuminate\Support\Str::limit($r->error_message, 40) }}</span>@endif
                            </td>
                            <td>{{ $r->duration_seconds !== null ? $r->duration_seconds.'s' : '—' }}</td>
                            <td class="text-xs opacity-60" data-order="{{ $r->called_at?->timestamp ?? 0 }}">{{ $r->called_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
