<x-app-layout>
    <x-slot name="title">System Health</x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($components as $c)
            @php($map = ['ok' => ['ti-circle-check','text-success'], 'warning' => ['ti-alert-triangle','text-warning'], 'error' => ['ti-circle-x','text-error'], 'na' => ['ti-minus','opacity-40']])
            @php([$icon, $color] = $map[$c->status] ?? $map['na'])
            <div class="card bg-base-100 border border-base-300">
                <div class="card-body flex-row items-center gap-4 py-4">
                    <i class="ti {{ $icon }} text-2xl {{ $color }}"></i>
                    <div>
                        <div class="font-medium">{{ $c->label }}</div>
                        <div class="text-xs opacity-60">{{ $c->message }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-xs opacity-50 mt-4">
        Machine-readable probe: <code>GET /health</code>
    </p>

    {{-- Storage Logs & 1-Click Cleanup --}}
    <div class="card bg-base-100 border border-base-300 shadow-sm mt-6">
        <div class="card-body p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-sm uppercase tracking-wide opacity-75">Application Log Files</h3>
                    <p class="text-xs opacity-60">Old dated logs (&gt;3 days) and oversized logs (&gt;2 MB) auto-clean daily via scheduler.</p>
                </div>
                <form method="POST" action="{{ route('admin.health.clear-logs') }}" onsubmit="return confirm('Clear all log files now?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline btn-error gap-1.5">
                        <i class="ti ti-trash"></i> Clear All Logs
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-xs">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th class="text-right">Size</th>
                            <th class="text-right">Last Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logFiles ?? [] as $lf)
                            <tr>
                                <td class="font-mono text-xs">{{ $lf['name'] }}</td>
                                <td class="text-right font-mono text-xs">
                                    {{ $lf['size_kb'] >= 1024 ? number_format($lf['size_kb'] / 1024, 2).' MB' : $lf['size_kb'].' KB' }}
                                </td>
                                <td class="text-right text-xs opacity-70">{{ $lf['updated_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center opacity-60 py-3">No log files found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
