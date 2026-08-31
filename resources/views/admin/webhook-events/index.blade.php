<x-app-layout>
    <x-slot name="title">Webhook Events</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex flex-wrap items-end gap-2">
                <x-dt-filter label="Status" target="#webhook-events-table" :col="3">
                    <option value="">Any status</option>
                    @foreach (['received','processing','processed','failed','ignored'] as $s)
                        <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                </x-dt-filter>
                <x-dt-filter label="Signature" target="#webhook-events-table" :col="4">
                    <option value="">Any</option>
                    <option value="valid">Valid</option>
                    <option value="invalid">Invalid</option>
                </x-dt-filter>
            </div>
            <p class="text-xs opacity-60">
                Last event:
                <span class="font-medium">{{ $lastAt?->diffForHumans() ?? 'never' }}</span>
                · showing latest {{ number_format($events->count()) }}
            </p>
        </div>

        @if ($pending > 0)
            <div class="alert alert-warning text-sm">
                <i class="ti ti-alert-triangle"></i>
                <span>
                    <strong>{{ $pending }}</strong> event(s) received but not processed.
                    Make sure a queue worker is running:
                    <code class="bg-base-300 px-1 rounded">php artisan queue:work --queue=whatsapp-webhook,whatsapp-high,default</code>
                </span>
            </div>
        @endif

        @if ($events->isEmpty())
            <div class="alert text-sm">
                <i class="ti ti-info-circle"></i>
                <span>No webhook events yet. Once Meta's callback URL points at
                    <code class="bg-base-300 px-1 rounded">/api/webhooks/whatsapp</code>
                    and the <code>messages</code> field is subscribed, deliveries land here.</span>
            </div>
        @endif

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table table-sm" id="webhook-events-table" data-datatable data-order='[[0,"desc"]]'>
                <thead><tr>
                    <th>Received</th><th>Kind</th><th>Summary</th><th>Status</th><th>Signature</th><th>Error</th>
                </tr></thead>
                <tbody>
                    @foreach ($events as $e)
                        <tr class="hover cursor-pointer" data-href="{{ route('admin.webhook-events.show', $e) }}">
                            <td class="text-xs opacity-70 whitespace-nowrap" data-order="{{ $e->received_at?->timestamp ?? 0 }}">
                                {{ $e->received_at?->format('d M H:i:s') ?? '—' }}
                            </td>
                            <td class="font-mono text-xs">{{ $e->kind() }}</td>
                            <td class="text-xs max-w-xs truncate">{{ $e->summary() }}</td>
                            <td>
                                @php($sm = ['processed'=>'badge-success','failed'=>'badge-error','processing'=>'badge-info','ignored'=>'badge-ghost'])
                                <span class="badge badge-sm {{ $sm[$e->status] ?? 'badge-warning' }}">{{ $e->status }}</span>
                            </td>
                            <td>
                                @if ($e->signature_valid)
                                    <span class="badge badge-sm badge-success">valid</span>
                                @else
                                    <span class="badge badge-sm badge-error">invalid</span>
                                @endif
                            </td>
                            <td class="text-xs text-error max-w-xs truncate">{{ $e->error }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
