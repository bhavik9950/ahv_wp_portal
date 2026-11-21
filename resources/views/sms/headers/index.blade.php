<x-app-layout>
    <x-slot name="title">DLT Sender Headers</x-slot>
    <x-slot name="header">DLT Headers &amp; Sender IDs</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Approved DLT Headers</h2>
                <p class="text-xs opacity-70">Register your 6-character Alpha sender headers (e.g. FITGNR, AHVSFT) approved by your telecom DLT portal.</p>
            </div>
            <button onclick="document.getElementById('new-header-modal').showModal()" class="btn btn-primary btn-sm gap-2">
                <i class="ti ti-plus"></i> Add New Header
            </button>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sender Header</th>
                            <th>Entity ID (PE ID)</th>
                            <th>Description</th>
                            <th>Templates</th>
                            <th>Status</th>
                            <th>Default</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($senderIds as $h)
                            <tr class="hover">
                                <td class="font-bold font-mono text-primary text-base">{{ $h->sender_id }}</td>
                                <td class="font-mono text-xs opacity-80">{{ $h->dlt_entity_id ?? '—' }}</td>
                                <td class="text-xs">{{ $h->description ?? 'Primary Sender Header' }}</td>
                                <td><span class="badge badge-sm badge-outline">{{ $h->templates_count }} templates</span></td>
                                <td>
                                    <span class="badge badge-xs {{ $h->status === 'active' ? 'badge-success' : 'badge-ghost' }}">
                                        {{ ucfirst($h->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($h->is_default)
                                        <span class="badge badge-sm badge-primary gap-1"><i class="ti ti-check text-xs"></i> Default</span>
                                    @else
                                        <span class="opacity-40 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('sms.headers.destroy', $h) }}" onsubmit="return confirm('Delete this DLT header?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-xs text-error"><i class="ti ti-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 opacity-60 text-xs">
                                    No Sender Headers registered yet. Add your TRAI DLT approved 6-character header (e.g. FITGNR).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Header Modal --}}
    <dialog id="new-header-modal" class="modal">
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base mb-3 flex items-center gap-2">
                <i class="ti ti-certificate text-primary"></i> Register DLT Sender Header
            </h3>
            <form method="POST" action="{{ route('sms.headers.store') }}" class="space-y-4">
                @csrf
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs font-bold">Header / Sender ID (6 Characters)</span></label>
                    <input type="text" name="sender_id" maxlength="10" placeholder="e.g. FITGNR or AHVSFT" required
                           class="input input-sm input-bordered font-mono uppercase text-xs">
                    <span class="text-[0.7rem] opacity-50 mt-1">Must match the exact 6-char header approved on Jio/Airtel DLT</span>
                </div>

                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs font-bold">Principal Entity ID (PE ID)</span></label>
                    <input type="text" name="dlt_entity_id" placeholder="e.g. 1201159123456789012"
                           class="input input-sm input-bordered font-mono text-xs">
                    <span class="text-[0.7rem] opacity-50 mt-1">Your registered entity number on the telecom portal</span>
                </div>

                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs font-bold">Description / Purpose</span></label>
                    <input type="text" name="description" placeholder="e.g. Member OTPs & Alerts"
                           class="input input-sm input-bordered text-xs">
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-2 py-1">
                        <input type="checkbox" name="is_default" value="1" class="checkbox checkbox-sm checkbox-primary" checked>
                        <span class="label-text text-xs">Set as default sender for this organization</span>
                    </label>
                </div>

                <div class="modal-action pt-2">
                    <button type="button" onclick="document.getElementById('new-header-modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm gap-2">
                        <i class="ti ti-check"></i> Register Header
                    </button>
                </div>
            </form>
        </div>
    </dialog>
</x-app-layout>
