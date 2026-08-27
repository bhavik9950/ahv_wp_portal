<x-app-layout>
    <x-slot name="title">{{ $template->name }}</x-slot>

    @php($canDelete = auth()->user()->can(\App\Enums\Permission::TemplateManage->value))

    <div class="max-w-2xl space-y-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('whatsapp.templates.index') }}" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left"></i> Templates</a>
            @if ($canDelete)
                <form method="POST" action="{{ route('whatsapp.templates.destroy', $template) }}"
                      data-confirm="Delete template “{{ $template->name }}”? This also deletes it at Meta where permitted.">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-error btn-outline"><i class="ti ti-trash"></i> Delete</button>
                </form>
            @endif
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <div class="flex items-center gap-2">
                    <h2 class="card-title text-base font-mono">{{ $template->name }}</h2>
                    @php($st = $template->statusEnum())
                    <span class="badge {{ $st === \App\Enums\TemplateStatus::Approved ? 'badge-success' : ($st === \App\Enums\TemplateStatus::Rejected ? 'badge-error' : 'badge-ghost') }}">
                        {{ $template->status }}
                    </span>
                </div>
                <p class="text-xs opacity-60">{{ $template->language }} · {{ $template->category }}</p>

                @if ($template->rejection_reason)
                    <div class="alert alert-error text-sm mt-2">
                        <i class="ti ti-x"></i><span>Rejection reason: {{ $template->rejection_reason }}</span>
                    </div>
                @endif

                <div class="divider text-xs">Preview</div>
                <div class="bg-base-200 rounded-lg p-4 text-sm whitespace-pre-line">{{ $preview }}</div>
            </div>
        </div>

        <details class="collapse collapse-arrow border border-base-300 bg-base-100">
            <summary class="collapse-title text-sm font-medium">Raw Meta payload (diagnostics)</summary>
            <div class="collapse-content">
                <pre class="text-xs overflow-x-auto">{{ json_encode($template->raw_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </details>
    </div>
</x-app-layout>
