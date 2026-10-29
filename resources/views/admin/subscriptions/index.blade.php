<x-app-layout>
    <x-slot name="title">Subscription &amp; UTR Verifications</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="ti ti-receipt-2 text-primary"></i> Subscriptions &amp; UTR Verifications
                </h2>
                <p class="text-xs opacity-60">Review incoming client payment references (UTR) and approve or reject subscription validity.</p>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('bank-settings-modal').showModal()" class="btn btn-outline btn-sm gap-1">
                    <i class="ti ti-settings"></i> Edit Bank &amp; UPI Details
                </button>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="tabs tabs-boxed bg-base-100 p-1 border border-base-300 w-fit">
            <a href="{{ route('admin.subscriptions.index') }}" class="tab tab-sm {{ ! $currentStatus ? 'tab-active' : '' }}">All</a>
            <a href="{{ route('admin.subscriptions.index', ['status' => 'pending']) }}" class="tab tab-sm {{ $currentStatus === 'pending' ? 'tab-active font-bold text-warning' : '' }}">
                Pending Verification
            </a>
            <a href="{{ route('admin.subscriptions.index', ['status' => 'approved']) }}" class="tab tab-sm {{ $currentStatus === 'approved' ? 'tab-active text-success' : '' }}">Approved</a>
            <a href="{{ route('admin.subscriptions.index', ['status' => 'rejected']) }}" class="tab tab-sm {{ $currentStatus === 'rejected' ? 'tab-active text-error' : '' }}">Rejected</a>
        </div>

        {{-- Table --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Organization</th>
                        <th>User</th>
                        <th>Plan Applied</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>UTR Number</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $req)
                        <tr class="hover">
                            <td class="text-xs opacity-60 whitespace-nowrap">{{ $req->created_at->format('d M Y, h:i A') }}</td>
                            <td class="font-bold">{{ $req->organization?->name ?? '—' }}</td>
                            <td class="text-xs">{{ $req->user?->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $req->plan === 'lifetime' ? 'badge-primary' : 'badge-ghost' }} badge-sm font-semibold">
                                    {{ ucfirst(str_replace('_', ' ', $req->plan)) }}
                                </span>
                            </td>
                            <td class="font-mono font-bold">₹{{ number_format($req->amount) }}</td>
                            <td class="text-xs uppercase font-mono">{{ $req->payment_method }}</td>
                            <td>
                                <span class="font-mono font-bold text-xs bg-base-200 px-2 py-0.5 rounded select-all">
                                    {{ $req->utr_number }}
                                </span>
                                @if ($req->notes)
                                    <div class="text-[0.7rem] opacity-60 mt-0.5 truncate max-w-xs" title="{{ $req->notes }}">
                                        {{ $req->notes }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($req->isApproved())
                                    <span class="badge badge-success badge-sm font-medium gap-1">Approved</span>
                                @elseif ($req->isPending())
                                    <span class="badge badge-warning badge-sm font-medium gap-1">Pending</span>
                                @else
                                    <span class="badge badge-error badge-sm font-medium gap-1">Rejected</span>
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                @if ($req->isPending())
                                    <div class="inline-flex gap-1">
                                        <form method="POST" action="{{ route('admin.subscriptions.approve', $req) }}" onsubmit="return confirm('Approve this payment and activate the plan for {{ addslashes($req->organization?->name ?? '') }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-xs text-white gap-1" title="Approve UTR & Extend Subscription">
                                                <i class="ti ti-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.subscriptions.reject', $req) }}" onsubmit="return confirm('Reject this UTR submission?')">
                                            @csrf
                                            <button type="submit" class="btn btn-error btn-xs btn-outline" title="Reject UTR">
                                                <i class="ti ti-x"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs opacity-50">
                                        {{ $req->approvedBy ? 'by '.$req->approvedBy->name : '—' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-8 opacity-60">
                                <i class="ti ti-inbox text-3xl block mb-2"></i>
                                No subscription verification requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $requests->links() }}
        </div>
    </div>

    {{-- Bank & UPI Settings Modal --}}
    <dialog id="bank-settings-modal" class="modal">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                <i class="ti ti-building-bank"></i> AHV Billing Bank &amp; UPI Settings
            </h3>
            <form method="POST" action="{{ route('admin.subscriptions.settings') }}" class="space-y-4">
                @csrf
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium text-xs">UPI ID (VPA)</span></label>
                    <input type="text" name="upi_id" value="{{ old('upi_id', $upiId) }}" class="input input-bordered input-sm font-mono" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium text-xs">Bank Name</span></label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $bankDetails['bank_name'] ?? '') }}" class="input input-bordered input-sm" required>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium text-xs">Account Holder Name</span></label>
                    <input type="text" name="account_holder" value="{{ old('account_holder', $bankDetails['account_holder'] ?? '') }}" class="input input-bordered input-sm" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium text-xs">Account Number</span></label>
                        <input type="text" name="account_number" value="{{ old('account_number', $bankDetails['account_number'] ?? '') }}" class="input input-bordered input-sm font-mono" required>
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium text-xs">IFSC Code</span></label>
                        <input type="text" name="ifsc" value="{{ old('ifsc', $bankDetails['ifsc'] ?? '') }}" class="input input-bordered input-sm font-mono uppercase" required>
                    </div>
                </div>
                <div class="modal-action">
                    <button type="button" onclick="document.getElementById('bank-settings-modal').close()" class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Billing Info</button>
                </div>
            </form>
        </div>
    </dialog>
</x-app-layout>
