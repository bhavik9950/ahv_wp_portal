<x-app-layout>
    <x-slot name="title">Lead Inquiries &amp; Onboarding Requests</x-slot>
    <x-slot name="header">Customer Inquiries &amp; Leads</x-slot>

    <div class="space-y-6">
        {{-- Status Filter Badges --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('admin.inquiries.index') }}"
                   class="btn btn-sm {{ $currentStatus === 'all' ? 'btn-primary' : 'btn-ghost border border-base-300' }}">
                    All Leads <span class="badge badge-sm {{ $currentStatus === 'all' ? 'badge-neutral' : 'badge-ghost' }}">{{ $counts['all'] }}</span>
                </a>
                <a href="{{ route('admin.inquiries.index', ['status' => 'new']) }}"
                   class="btn btn-sm {{ $currentStatus === 'new' ? 'btn-warning' : 'btn-ghost border border-base-300' }}">
                    New <span class="badge badge-sm badge-warning font-bold">{{ $counts['new'] }}</span>
                </a>
                <a href="{{ route('admin.inquiries.index', ['status' => 'contacted']) }}"
                   class="btn btn-sm {{ $currentStatus === 'contacted' ? 'btn-info' : 'btn-ghost border border-base-300' }}">
                    Contacted <span class="badge badge-sm badge-info">{{ $counts['contacted'] }}</span>
                </a>
                <a href="{{ route('admin.inquiries.index', ['status' => 'converted']) }}"
                   class="btn btn-sm {{ $currentStatus === 'converted' ? 'btn-success text-white' : 'btn-ghost border border-base-300' }}">
                    Converted <span class="badge badge-sm badge-success text-white">{{ $counts['converted'] }}</span>
                </a>
                <a href="{{ route('admin.inquiries.index', ['status' => 'closed']) }}"
                   class="btn btn-sm {{ $currentStatus === 'closed' ? 'btn-neutral' : 'btn-ghost border border-base-300' }}">
                    Closed <span class="badge badge-sm">{{ $counts['closed'] }}</span>
                </a>
            </div>

            {{-- Search Bar --}}
            <form method="GET" action="{{ route('admin.inquiries.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                @if ($currentStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <div class="join w-full sm:w-72">
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search name, phone, business..."
                           class="input input-sm input-bordered join-item w-full text-xs">
                    <button type="submit" class="btn btn-sm btn-primary join-item">
                        <i class="ti ti-search"></i>
                    </button>
                </div>
                @if ($search)
                    <a href="{{ route('admin.inquiries.index', ['status' => $currentStatus !== 'all' ? $currentStatus : null]) }}" class="btn btn-sm btn-ghost btn-square" title="Clear search">
                        <i class="ti ti-x"></i>
                    </a>
                @endif
            </form>
        </div>

        {{-- Inquiries Table --}}
        <div class="card bg-base-100 border border-base-300">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date / Ref</th>
                            <th>Contact / Business</th>
                            <th>Service Interested</th>
                            <th>Message / Note</th>
                            <th>Status</th>
                            <th>Admin Notes</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inquiries as $inq)
                            @php
                                $cleanPhone = preg_replace('/\D+/', '', $inq->phone);
                                $waUrl = 'https://wa.me/' . (str_starts_with($cleanPhone, '91') ? $cleanPhone : '91' . $cleanPhone) . '?text=' . urlencode("Hello {$inq->name}, thank you for contacting AHV Software regarding {$inq->service_interested} services.");
                            @endphp
                            <tr class="hover">
                                <td>
                                    <div class="font-mono font-bold text-xs">#{{ $inq->id }}</div>
                                    <div class="text-[0.7rem] opacity-60 text-nowrap">{{ $inq->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                                </td>
                                <td>
                                    <div class="font-bold text-sm">{{ $inq->name }}</div>
                                    @if ($inq->business_name)
                                        <div class="text-xs opacity-75 flex items-center gap-1 font-semibold text-primary">
                                            <i class="ti ti-building text-xs"></i> {{ $inq->business_name }}
                                        </div>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1">
                                        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="btn btn-xs btn-outline btn-success gap-1 text-[0.7rem] py-0 px-2" title="Chat on WhatsApp">
                                            <i class="ti ti-brand-whatsapp text-sm"></i> {{ $inq->phone }}
                                        </a>
                                        @if ($inq->email)
                                            <a href="mailto:{{ $inq->email }}" class="text-[0.7rem] opacity-70 hover:opacity-100 link" title="Send Email">
                                                <i class="ti ti-mail"></i> {{ $inq->email }}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if ($inq->service_interested === 'whatsapp')
                                        <span class="badge badge-sm badge-success text-white gap-1"><i class="ti ti-brand-whatsapp"></i> WhatsApp</span>
                                    @elseif ($inq->service_interested === 'sms')
                                        <span class="badge badge-sm badge-warning gap-1"><i class="ti ti-message-dots"></i> SMS Gateway</span>
                                    @elseif ($inq->service_interested === 'voice')
                                        <span class="badge badge-sm badge-secondary gap-1"><i class="ti ti-phone-outgoing"></i> AI Voice</span>
                                    @else
                                        <span class="badge badge-sm badge-primary gap-1"><i class="ti ti-apps"></i> Full Suite</span>
                                    @endif
                                </td>
                                <td class="max-w-xs text-xs opacity-85">
                                    {{ $inq->message ?: 'No additional message provided.' }}
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.inquiries.update', $inq) }}" class="flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="select select-xs select-bordered font-semibold {{ $inq->status === 'new' ? 'border-warning text-warning' : ($inq->status === 'converted' ? 'border-success text-success' : '') }}">
                                            <option value="new" {{ $inq->status === 'new' ? 'selected' : '' }}>New</option>
                                            <option value="contacted" {{ $inq->status === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                            <option value="converted" {{ $inq->status === 'converted' ? 'selected' : '' }}>Converted</option>
                                            <option value="closed" {{ $inq->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-xs max-w-xs">
                                    <button onclick="document.getElementById('edit-note-modal-{{ $inq->id }}').showModal()"
                                            class="btn btn-xs btn-ghost border border-base-300 gap-1 text-[0.7rem]">
                                        <i class="ti ti-pencil"></i>
                                        <span class="truncate max-w-[120px]">{{ $inq->admin_notes ? Str::limit($inq->admin_notes, 20) : 'Add Note' }}</span>
                                    </button>

                                    {{-- Modal for Admin Notes --}}
                                    <dialog id="edit-note-modal-{{ $inq->id }}" class="modal">
                                        <div class="modal-box">
                                            <h3 class="font-bold text-base mb-2">Admin Notes for {{ $inq->name }} (#{{ $inq->id }})</h3>
                                            <form method="POST" action="{{ route('admin.inquiries.update', $inq) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $inq->status }}">
                                                <div class="form-control mb-4">
                                                    <textarea name="admin_notes" rows="4" class="textarea textarea-bordered text-xs" placeholder="Add follow-up details, onboarding timeline, deal size, etc.">{{ $inq->admin_notes }}</textarea>
                                                </div>
                                                <div class="modal-action">
                                                    <button type="button" onclick="document.getElementById('edit-note-modal-{{ $inq->id }}').close()" class="btn btn-sm btn-ghost">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-primary">Save Notes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </dialog>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="btn btn-xs btn-success text-white gap-1" title="WhatsApp Lead">
                                            <i class="ti ti-brand-whatsapp"></i> Chat
                                        </a>
                                        <form method="POST" action="{{ route('admin.inquiries.destroy', $inq) }}" onsubmit="return confirm('Delete this inquiry?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-ghost text-error btn-square" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 opacity-60">
                                    <i class="ti ti-inbox text-3xl mb-1 block"></i>
                                    No customer inquiries found for this status.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($inquiries->hasPages())
                <div class="p-4 border-t border-base-300">
                    {{ $inquiries->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
