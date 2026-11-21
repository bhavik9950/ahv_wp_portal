<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsSenderId;
use App\Models\Sms\SmsTemplate;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SmsTemplateController extends Controller
{
    public function index(CurrentOrganization $currentOrg): View
    {
        $org = $currentOrg->resolve();

        $templates = SmsTemplate::query()
            ->with('sender')
            ->latest()
            ->get();

        $senders = SmsSenderId::query()->where('status', 'active')->orderBy('sender_id')->get();

        return view('sms.templates.index', [
            'organization' => $org,
            'templates' => $templates,
            'senders' => $senders,
        ]);
    }

    public function store(Request $request, CurrentOrganization $currentOrg): RedirectResponse
    {
        $org = $currentOrg->resolve();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'dlt_template_id' => ['required', 'string', 'max:50'],
            'sms_sender_id' => ['nullable', 'exists:sms_sender_ids,id'],
            'template_type' => ['required', 'string', 'in:transactional,service_implicit,service_explicit,promotional'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        // Auto-detect variable count (e.g. {#var#}, {1}, {#var1#})
        preg_match_all('/\{#var\d*#\}|\{\d+\}/i', $validated['body'], $matches);
        $variables = $matches[0] ?? [];

        SmsTemplate::create([
            'organization_id' => $org->id,
            'sms_sender_id' => $validated['sms_sender_id'] ?? null,
            'name' => $validated['name'],
            'dlt_template_id' => trim($validated['dlt_template_id']),
            'template_type' => $validated['template_type'],
            'body' => $validated['body'],
            'variables' => $variables,
            'status' => 'approved',
        ]);

        return redirect()->route('sms.templates.index')->with('flash_notify', [
            'type' => 'success',
            'message' => "DLT Template '{$validated['name']}' saved successfully.",
        ]);
    }

    public function destroy(SmsTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('sms.templates.index')->with('flash_notify', [
            'type' => 'success',
            'message' => "DLT Template '{$template->name}' deleted.",
        ]);
    }
}
