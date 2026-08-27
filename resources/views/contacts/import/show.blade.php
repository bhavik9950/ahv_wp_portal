<x-app-layout>
    <x-slot name="title">Import Preview</x-slot>

    <div class="max-w-xl space-y-4"
         @if (in_array($import->status, ['pending', 'analyzing', 'importing'])) data-auto-refresh="3" @endif>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-base">{{ $import->original_filename }}</h2>
                    <span class="badge {{ $import->status === 'completed' ? 'badge-success' : ($import->status === 'failed' ? 'badge-error' : 'badge-ghost') }}">
                        {{ ucfirst($import->status) }}
                    </span>
                </div>

                @if (in_array($import->status, ['analyzing', 'importing', 'pending']))
                    <div class="flex items-center gap-2 text-sm opacity-70 mt-2">
                        <span class="loading loading-spinner loading-sm"></span>
                        {{ $import->status === 'importing' ? 'Importing valid rows…' : 'Analyzing…' }}
                        <a href="" class="link">refresh</a>
                    </div>
                @endif

                @if (in_array($import->status, ['analyzed', 'importing', 'completed']))
                    <div class="stats stats-vertical sm:stats-horizontal shadow-none border border-base-300 mt-3">
                        <div class="stat py-2"><div class="stat-title text-xs">Total</div><div class="stat-value text-lg">{{ number_format($import->total_rows) }}</div></div>
                        <div class="stat py-2"><div class="stat-title text-xs">Valid</div><div class="stat-value text-lg text-success">{{ number_format($import->valid_rows) }}</div></div>
                        <div class="stat py-2"><div class="stat-title text-xs">Invalid</div><div class="stat-value text-lg text-error">{{ number_format($import->invalid_rows) }}</div></div>
                        <div class="stat py-2"><div class="stat-title text-xs">Duplicates</div><div class="stat-value text-lg text-warning">{{ number_format($import->duplicate_rows) }}</div></div>
                    </div>
                @endif

                @if ($import->status === 'completed')
                    <div class="alert alert-success text-sm mt-3">
                        <i class="ti ti-check"></i><span>Imported {{ number_format($import->imported_rows) }} contact(s).</span>
                    </div>
                @endif

                @if ($import->status === 'failed')
                    <div class="alert alert-error text-sm mt-3"><i class="ti ti-x"></i><span>{{ $import->error }}</span></div>
                @endif

                <div class="flex gap-2 mt-4">
                    @if ($import->error_report_path)
                        <a href="{{ route('whatsapp.contacts.import.errors', $import) }}" data-turbo="false" class="btn btn-sm btn-outline">
                            <i class="ti ti-download"></i> Download invalid rows
                        </a>
                    @endif

                    @if ($import->isAnalyzed() && $import->valid_rows > 0)
                        <form method="POST" action="{{ route('whatsapp.contacts.import.commit', $import) }}"
                              data-confirm="Import {{ number_format($import->valid_rows) }} valid contact(s)?">
                            @csrf
                            <button class="btn btn-sm btn-primary"><i class="ti ti-database-import"></i> Import {{ number_format($import->valid_rows) }} contacts</button>
                        </form>
                    @endif

                    @if ($import->isFinished())
                        <a href="{{ route('whatsapp.contacts.index') }}" class="btn btn-sm">Back to contacts</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
