<x-app-layout>
    <x-slot name="title">DLT Templates</x-slot>
    <x-slot name="header">DLT Content Templates</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Approved DLT Message Templates</h2>
                <p class="text-xs opacity-70">Register content templates approved by your telecom DLT blockchain. Messages matching these templates will deliver instantly.</p>
            </div>
            <button onclick="document.getElementById('new-template-modal').showModal()" class="btn btn-primary btn-sm gap-2">
                <i class="ti ti-plus"></i> Add DLT Template
            </button>
        </div>

        <div class="card bg-base-100 border border-base-300">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Template ID</th>
                            <th>Category</th>
                            <th>Sender Header</th>
                            <th>Body Text</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $t)
                            <tr class="hover">
                                <td class="font-bold">{{ $t->name }}</td>
                                <td class="font-mono text-xs opacity-80">{{ $t->dlt_template_id }}</td>
                                <td>
                                    <span class="badge badge-sm badge-outline text-[0.7rem] uppercase">
                                        {{ str_replace('_', ' ', $t->template_type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-sm badge-ghost font-mono">{{ $t->sender?->sender_id ?? 'ANY' }}</span>
                                </td>
                                <td class="max-w-xs text-xs truncate" title="{{ $t->body }}">{{ $t->body }}</td>
                                <td>
                                    <span class="badge badge-xs {{ $t->status === 'approved' ? 'badge-success' : 'badge-ghost' }}">
                                        {{ ucfirst($t->status) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('sms.templates.destroy', $t) }}" onsubmit="return confirm('Delete this template?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-xs text-error"><i class="ti ti-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 opacity-60 text-xs">
                                    No DLT templates registered yet. Add your registered template ID and approved message text.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Template Modal --}}
    <dialog id="new-template-modal" class="modal">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-base mb-3 flex items-center gap-2">
                <i class="ti ti-template text-primary"></i> Add Approved DLT Template
            </h3>
            <form method="POST" action="{{ route('sms.templates.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-bold">Template Name</span></label>
                        <input type="text" name="name" placeholder="e.g. Login OTP or Event Alert" required
                               class="input input-sm input-bordered text-xs">
                    </div>

                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-bold">DLT Content Template ID</span></label>
                        <input type="text" name="dlt_template_id" placeholder="e.g. 120716123456789012" required
                               class="input input-sm input-bordered font-mono text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-bold">Associated Sender Header</span></label>
                        <select name="sms_sender_id" class="select select-sm select-bordered text-xs">
                            <option value="">Any Sender Header</option>
                            @foreach ($senders as $s)
                                <option value="{{ $s->id }}">{{ $s->sender_id }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-bold">Category</span></label>
                        <select name="template_type" class="select select-sm select-bordered text-xs">
                            <option value="service_implicit">Service Implicit (Transactional)</option>
                            <option value="service_explicit">Service Explicit (Updates)</option>
                            <option value="transactional">Pure OTP</option>
                            <option value="promotional">Promotional (Offers)</option>
                        </select>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs font-bold">Approved Template Body Text</span></label>
                    <textarea name="body" rows="4" required placeholder="Dear {#var#}, your verification code is {#var#}. Do not share it with anyone. - FITGNR"
                              class="textarea textarea-bordered text-xs font-mono"></textarea>
                    <span class="text-[0.7rem] opacity-50 mt-1">Include variables like {#var#} or {1} exactly as approved on DLT</span>
                </div>

                <div class="modal-action pt-2">
                    <button type="button" onclick="document.getElementById('new-template-modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm gap-2">
                        <i class="ti ti-check"></i> Save Template
                    </button>
                </div>
            </form>
        </div>
    </dialog>
</x-app-layout>
