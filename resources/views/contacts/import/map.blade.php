<x-app-layout>
    <x-slot name="title">Map Columns</x-slot>

    <form method="POST" action="{{ route('whatsapp.contacts.import.analyze', $import) }}" class="max-w-2xl space-y-4">
        @csrf

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body">
                <h2 class="card-title text-base">Map columns — {{ $import->original_filename }}</h2>
                <p class="text-xs opacity-60">One column must be mapped to <strong>Phone</strong>. Unmapped columns are stored as custom fields.</p>

                <div class="overflow-x-auto mt-2">
                    <table class="table table-sm">
                        <thead><tr><th>CSV column</th><th>Sample</th><th>Maps to</th></tr></thead>
                        <tbody>
                            @foreach ($headers as $h)
                                <tr>
                                    <td class="font-mono">{{ $h }}</td>
                                    <td class="opacity-60 text-xs">{{ collect($sample)->pluck($h)->filter()->first() }}</td>
                                    <td>
                                        <select name="column_map[{{ $h }}]" class="select select-bordered select-sm">
                                            @foreach ($fields as $value => $label)
                                                <option value="{{ $value }}" @selected(\Illuminate\Support\Str::contains(strtolower($h), $value) && $value !== '')>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('column_map')<p class="text-error text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="card-body space-y-3">
                <h2 class="card-title text-base">Options</h2>
                @if ($groups->isNotEmpty())
                    <div>
                        <label class="label"><span class="label-text">Add all imported contacts to group</span></label>
                        <select name="group_id" class="select select-bordered select-sm">
                            <option value="">— none —</option>
                            @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                        </select>
                    </div>
                @endif
                <label class="label cursor-pointer justify-start gap-2">
                    <input type="checkbox" name="mark_opted_in" value="1" class="checkbox checkbox-sm">
                    <span class="label-text">Mark these contacts as opted in (only if you have documented consent)</span>
                </label>
                <div>
                    <label class="label"><span class="label-text">Opt-in source label</span></label>
                    <input name="opt_in_source" value="csv_import" class="input input-bordered input-sm w-full">
                </div>
            </div>
        </div>

        <button class="btn btn-primary"><i class="ti ti-search"></i> Analyze file</button>
    </form>
</x-app-layout>
