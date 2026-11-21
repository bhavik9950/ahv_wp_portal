<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsSenderId;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SmsSenderIdController extends Controller
{
    public function index(CurrentOrganization $currentOrg): View
    {
        $org = $currentOrg->resolve();

        $senderIds = SmsSenderId::query()
            ->withCount('templates')
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return view('sms.headers.index', [
            'organization' => $org,
            'senderIds' => $senderIds,
        ]);
    }

    public function store(Request $request, CurrentOrganization $currentOrg): RedirectResponse
    {
        $org = $currentOrg->resolve();

        $validated = $request->validate([
            'sender_id' => ['required', 'string', 'min:3', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'dlt_entity_id' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $senderHeader = strtoupper(trim($validated['sender_id']));

        // If setting as default, clear others
        if ($request->boolean('is_default')) {
            SmsSenderId::query()->update(['is_default' => false]);
        }

        SmsSenderId::create([
            'organization_id' => $org->id,
            'sender_id' => $senderHeader,
            'dlt_entity_id' => $validated['dlt_entity_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => 'active',
            'is_default' => $request->boolean('is_default') || SmsSenderId::query()->count() === 0,
        ]);

        return redirect()->route('sms.headers.index')->with('flash_notify', [
            'type' => 'success',
            'message' => "DLT Sender Header '{$senderHeader}' registered successfully.",
        ]);
    }

    public function destroy(SmsSenderId $header): RedirectResponse
    {
        $header->delete();

        return redirect()->route('sms.headers.index')->with('flash_notify', [
            'type' => 'success',
            'message' => "Sender Header '{$header->sender_id}' deleted.",
        ]);
    }
}
