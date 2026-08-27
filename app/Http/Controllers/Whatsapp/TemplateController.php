<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Whatsapp\StoreTemplateRequest;
use App\Jobs\SyncTemplatesJob;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\Templates\TemplateComposer;
use App\Services\WhatsApp\Templates\TemplateSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateSubmissionService $submissions,
        private readonly TemplateComposer $composer,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WhatsappTemplate::class);

        $templates = WhatsappTemplate::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('whatsapp.templates.index', [
            'templates' => $templates,
            'filters' => $request->only(['status', 'category', 'q']),
            'hasAccount' => WhatsappBusinessAccount::query()->exists(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', WhatsappTemplate::class);

        return view('whatsapp.templates.create', [
            'language' => config('services.whatsapp.template_language', 'en'),
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', WhatsappTemplate::class);

        $account = WhatsappBusinessAccount::query()->orderBy('created_at')->firstOrFail();

        $template = $this->submissions->submit($account, $request->validated());

        return redirect()
            ->route('whatsapp.templates.show', $template)
            ->with('flash_notify', ['type' => 'success', 'message' => 'Template submitted to Meta for review.']);
    }

    public function show(WhatsappTemplate $template): View
    {
        $this->authorize('view', $template);

        $body = collect($template->components)->firstWhere('type', 'BODY')['text'] ?? '';

        return view('whatsapp.templates.show', [
            'template' => $template,
            'preview' => $this->composer->preview($body),
        ]);
    }

    public function sync(): RedirectResponse
    {
        $this->authorize('viewAny', WhatsappTemplate::class);

        $account = WhatsappBusinessAccount::query()->orderBy('created_at')->firstOrFail();
        SyncTemplatesJob::dispatchSync($account->getKey());

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Templates synced from Meta.']);
    }

    public function destroy(WhatsappTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $this->submissions->delete($template);

        return redirect()
            ->route('whatsapp.templates.index')
            ->with('flash_notify', ['type' => 'success', 'message' => 'Template deleted.']);
    }
}
