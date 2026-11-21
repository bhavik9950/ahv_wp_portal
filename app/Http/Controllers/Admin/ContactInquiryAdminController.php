<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactInquiryAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = $request->query('q');

        $query = ContactInquiry::query()->latest();

        if (filled($status) && in_array($status, ['new', 'contacted', 'converted', 'closed'], true)) {
            $query->where('status', $status);
        }

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $inquiries = $query->paginate(25)->withQueryString();

        $counts = [
            'all' => ContactInquiry::query()->count(),
            'new' => ContactInquiry::query()->where('status', 'new')->count(),
            'contacted' => ContactInquiry::query()->where('status', 'contacted')->count(),
            'converted' => ContactInquiry::query()->where('status', 'converted')->count(),
            'closed' => ContactInquiry::query()->where('status', 'closed')->count(),
        ];

        return view('admin.inquiries.index', [
            'inquiries' => $inquiries,
            'counts' => $counts,
            'currentStatus' => $status ?? 'all',
            'search' => $search,
        ]);
    }

    public function update(Request $request, ContactInquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:new,contacted,converted,closed'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $inquiry->update($validated);

        return back()->with('success', "Inquiry #{$inquiry->id} updated successfully.");
    }

    public function destroy(ContactInquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return back()->with('success', 'Inquiry deleted successfully.');
    }
}
