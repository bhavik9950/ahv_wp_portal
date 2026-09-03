<x-app-layout>
    <x-slot name="title">Import Preview</x-slot>

    <div class="max-w-xl space-y-4"
         @if ($import->isBusy()) data-auto-refresh="2" @endif>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-base truncate">{{ $import->original_filename }}</h2>
                    @php($sm = ['completed' => 'badge-success', 'failed' => 'badge-error', 'importing' => 'badge-info', 'analyzed' => 'badge-primary'])
                    <span class="badge {{ $sm[$import->status] ?? 'badge-ghost' }} gap-1">
                        @if ($import->isBusy())<span class="loading loading-spinner loading-xs"></span>@endif
                        {{ ucfirst($import->status) }}
                    </span>
                </div>

                {{-- Row breakdown — climbs live during analysis --}}
                @if (in_array($import->status, ['analyzed', 'importing', 'completed']) || ($import->status === 'analyzing' && $import->total_rows > 0))
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3">
                        @foreach ([
                            ['Total rows', $import->total_rows, 'opacity-70'],
                            ['Valid', $import->valid_rows, 'text-success'],
                            ['Invalid', $import->invalid_rows, 'text-error'],
                            ['Duplicates', $import->duplicate_rows, 'text-warning'],
                        ] as [$label, $value, $color])
                            <div class="rounded-box border border-base-300 p-2 text-center">
                                <div class="text-xl font-semibold {{ $value ? $color : 'opacity-30' }}">{{ number_format((int) $value) }}</div>
                                <div class="text-[0.7rem] uppercase tracking-wide opacity-50">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (in_array($import->status, ['analyzing', 'pending', 'queued']))
                    <div class="flex items-center gap-2 text-sm opacity-70 mt-3">
                        <span class="loading loading-spinner loading-sm"></span>
                        @if ($import->status === 'queued')
                            Queued — waiting for the background worker to pick it up…
                        @else
                            Reading and validating the file… {{ $import->total_rows > 0 ? number_format((int) $import->total_rows).' rows so far' : '' }}
                        @endif
                    </div>
                @endif

                @if ($import->looksStuck())
                    <div class="alert alert-warning text-sm mt-3">
                        <i class="ti ti-alert-triangle"></i>
                        <span>
                            No progress for 2+ minutes — the background queue worker isn't running.
                            On the server: <code class="bg-base-300 px-1 rounded">supervisorctl status</code> and
                            <code class="bg-base-300 px-1 rounded">supervisorctl restart ahv-queue:*</code>,
                            or check <strong>Admin → System Health</strong>. The import resumes automatically once the worker is back.
                        </span>
                    </div>
                @endif

                {{-- Live import progress --}}
                @if (in_array($import->status, ['importing', 'completed']))
                    <div class="mt-4 space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium">
                                {{ $import->status === 'completed' ? 'Imported' : 'Importing…' }}
                            </span>
                            <span class="tabular-nums opacity-70">
                                {{ number_format((int) $import->imported_rows) }} / {{ number_format((int) $import->valid_rows) }}
                            </span>
                        </div>
                        <progress class="progress {{ $import->status === 'completed' ? 'progress-success' : 'progress-info' }} w-full"
                                  value="{{ $import->progressPercent() }}" max="100"></progress>
                        <div class="text-xs opacity-50 text-right">{{ $import->progressPercent() }}%</div>
                    </div>
                @endif

                @if ($import->status === 'completed')
                    <div class="alert alert-success text-sm mt-3">
                        <i class="ti ti-check"></i>
                        <span><strong>{{ number_format((int) $import->imported_rows) }}</strong> contact(s) added.
                            @php($skipped = (int) $import->valid_rows - (int) $import->imported_rows)
                            @if ($skipped > 0) ({{ number_format($skipped) }} were added by another import in the meantime.) @endif
                        </span>
                    </div>
                @endif

                @if ($import->status === 'failed')
                    <div class="alert alert-error text-sm mt-3"><i class="ti ti-x"></i><span>{{ $import->error }}</span></div>
                @endif

                <div class="flex flex-wrap gap-2 mt-4">
                    @if ($import->isBusy())
                        <a href="{{ route('whatsapp.contacts.import.show', $import) }}" class="btn btn-sm btn-ghost">
                            <i class="ti ti-refresh"></i> Refresh
                        </a>
                    @endif

                    @if ($import->error_report_path)
                        <a href="{{ route('whatsapp.contacts.import.errors', $import) }}" data-turbo="false" class="btn btn-sm btn-outline">
                            <i class="ti ti-download"></i> Download {{ number_format((int) $import->invalid_rows + (int) $import->duplicate_rows) }} skipped row(s)
                        </a>
                    @endif

                    @if ($import->isAnalyzed() && $import->valid_rows > 0)
                        <form method="POST" action="{{ route('whatsapp.contacts.import.commit', $import) }}"
                              data-loading data-loading-text="Starting…"
                              data-confirm="Import {{ number_format((int) $import->valid_rows) }} valid contact(s)?">
                            @csrf
                            <button class="btn btn-sm btn-primary">
                                <i class="ti ti-database-import"></i> Import {{ number_format((int) $import->valid_rows) }} contacts
                            </button>
                        </form>
                    @endif

                    @if ($import->isAnalyzed() && $import->valid_rows === 0)
                        <p class="text-sm opacity-60">Nothing to import — every row was invalid or a duplicate.</p>
                    @endif

                    @if ($import->isFinished())
                        <a href="{{ route('whatsapp.contacts.index') }}" class="btn btn-sm">View contacts</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
