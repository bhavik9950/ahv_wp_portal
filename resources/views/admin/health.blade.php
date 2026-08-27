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
</x-app-layout>
