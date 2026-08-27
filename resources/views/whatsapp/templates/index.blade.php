<x-app-layout>
    <x-slot name="title">Templates</x-slot>

    @php($canManage = auth()->user()->can(\App\Enums\Permission::TemplateManage->value))

    <div class="space-y-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <form method="GET" class="flex flex-wrap gap-2">
                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name…" class="input input-bordered input-sm" />
                <select name="status" class="select select-bordered select-sm">
                    <option value="">Any status</option>
                    @foreach (['APPROVED','PENDING','REJECTED','PAUSED','DISABLED'] as $s)
                        <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                <select name="category" class="select select-bordered select-sm">
                    <option value="">Any category</option>
                    @foreach (['MARKETING','UTILITY','AUTHENTICATION'] as $c)
                        <option value="{{ $c }}" @selected(($filters['category'] ?? '') === $c)>{{ $c }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm">Filter</button>
            </form>

            <div class="flex gap-2">
                <form method="POST" action="{{ route('whatsapp.templates.sync') }}">@csrf
                    <button class="btn btn-sm btn-outline" @disabled(! $hasAccount)><i class="ti ti-refresh"></i> Sync from Meta</button>
                </form>
                @if ($canManage)
                    <a href="{{ route('whatsapp.templates.create') }}" class="btn btn-sm btn-primary" @disabled(! $hasAccount)>
                        <i class="ti ti-plus"></i> New template
                    </a>
                @endif
            </div>
        </div>

        @unless ($hasAccount)
            <div class="alert alert-warning"><i class="ti ti-alert-triangle"></i>
                <span>Configure a WhatsApp Business Account first under <a class="link" href="{{ route('whatsapp.settings.edit') }}">Settings</a>.</span>
            </div>
        @endunless

        <div class="card bg-base-100 border border-base-300 overflow-x-auto">
            <table class="table">
                <thead><tr><th>Name</th><th>Language</th><th>Category</th><th>Status</th><th>Last synced</th></tr></thead>
                <tbody>
                    @forelse ($templates as $t)
                        <tr class="hover cursor-pointer" data-href="{{ route('whatsapp.templates.show', $t) }}">
                            <td class="font-mono">
                                <a class="link link-hover" href="{{ route('whatsapp.templates.show', $t) }}">{{ $t->name }}</a>
                            </td>
                            <td>{{ $t->language }}</td>
                            <td>{{ $t->category ?? '—' }}</td>
                            <td>
                                @php($st = $t->statusEnum())
                                <span class="badge badge-sm {{ $st === \App\Enums\TemplateStatus::Approved ? 'badge-success' : ($st === \App\Enums\TemplateStatus::Rejected ? 'badge-error' : 'badge-ghost') }}">
                                    {{ $t->status }}
                                </span>
                            </td>
                            <td class="text-xs opacity-60">{{ $t->last_synced_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center opacity-60 py-6">No templates. Sync from Meta or create one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $templates->links() }}
    </div>
</x-app-layout>
