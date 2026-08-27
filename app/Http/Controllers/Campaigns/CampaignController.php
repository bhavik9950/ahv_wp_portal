<?php

declare(strict_types=1);

namespace App\Http\Controllers\Campaigns;

use App\Enums\TemplateStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Campaigns\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Media;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\Campaigns\CampaignAudienceResolver;
use App\Services\Campaigns\CampaignLauncher;
use App\Services\Campaigns\CampaignService;
use App\Services\Campaigns\CampaignVariableRenderer;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\OutboundMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaigns,
        private readonly CampaignLauncher $launcher,
        private readonly CampaignAudienceResolver $audience,
        private readonly CampaignVariableRenderer $renderer,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Campaign::class);

        $campaigns = Campaign::query()
            ->withCount('recipients')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('campaigns.index', ['campaigns' => $campaigns, 'filters' => $request->only('status')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $campaign = $this->campaigns->createDraft($data['name']);

        return redirect()->route('whatsapp.campaigns.edit', $campaign);
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $campaign->load(['template', 'phoneNumber', 'media']);
        $sampleContact = $this->audience->query($campaign)->first();

        return view('campaigns.edit', [
            'campaign' => $campaign,
            'numbers' => WhatsappPhoneNumber::query()->orderByDesc('is_default')->get(),
            'templates' => WhatsappTemplate::query()->where('status', TemplateStatus::Approved->value)->orderBy('name')->get(),
            'groups' => ContactGroup::query()->withCount('contacts')->orderBy('name')->get(),
            'media' => Media::query()->latest()->get(),
            'audienceCount' => $campaign->template_id ? $this->audience->count($campaign) : 0,
            'placeholders' => count($this->renderer->render($campaign, null)),
            'preview' => $this->buildPreview($campaign, $sampleContact),
            'validationErrors' => $this->launcher->validate($campaign),
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaigns->updateDraft($campaign, $request->validated());

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Campaign saved.']);
    }

    public function audiencePreview(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        return response()->json($this->audience->summary($campaign));
    }

    public function test(Request $request, Campaign $campaign, OutboundMessageService $sender): RedirectResponse
    {
        $this->authorize('view', $campaign);

        $data = $request->validate([
            'test_numbers' => ['required', 'string'],
        ]);

        $errors = $this->launcher->validate($campaign);
        // Test send only needs template + number, not an audience.
        $errors = array_values(array_filter($errors, fn ($e) => ! str_contains($e, 'audience')));
        if ($errors !== []) {
            return back()->withErrors(['test_numbers' => implode(' ', $errors)]);
        }

        $numbers = collect(preg_split('/[\s,;]+/', $data['test_numbers']))
            ->map(fn ($n) => preg_replace('/\D/', '', (string) $n))
            ->filter()->unique()->take(5);

        $phoneNumber = $campaign->phoneNumber()->first();
        $template = $campaign->template()->first();
        $sample = $this->audience->query($campaign)->first();
        $vars = $this->renderer->render($campaign, $sample);

        foreach ($numbers as $n) {
            $sender->send($phoneNumber, OutboundMessage::templateWithParams(
                new Recipient($n), $template->name, $template->language, $vars,
            ), ['idempotency_key' => 'campaign-test:'.Str::ulid(), 'template_id' => $template->getKey()]);
        }

        return back()->with('flash_notify', ['type' => 'info', 'message' => "Test message sent to {$numbers->count()} number(s)."]);
    }

    public function launch(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('launch', $campaign);

        $data = $request->validate([
            'mode' => ['required', 'in:now,schedule'],
            'scheduled_at' => ['required_if:mode,schedule', 'nullable', 'date', 'after:now'],
            'confirm' => ['accepted'],
        ]);

        $at = $data['mode'] === 'schedule'
            ? Carbon::parse($data['scheduled_at'], $campaign->timezone)
            : null;

        try {
            $this->launcher->schedule($campaign, $at);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['confirm' => $e->getMessage()]);
        }

        return redirect()->route('whatsapp.campaigns.report', $campaign)
            ->with('flash_notify', ['type' => 'success', 'message' => $at ? 'Campaign scheduled.' : 'Campaign launched.']);
    }

    public function pause(Campaign $campaign): RedirectResponse
    {
        $this->authorize('launch', $campaign);
        $this->launcher->pause($campaign);

        return back()->with('flash_notify', ['type' => 'warning', 'message' => 'Campaign paused.']);
    }

    public function resume(Campaign $campaign): RedirectResponse
    {
        $this->authorize('launch', $campaign);
        $this->launcher->resume($campaign);

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Campaign resumed.']);
    }

    public function cancel(Campaign $campaign): RedirectResponse
    {
        $this->authorize('launch', $campaign);
        $this->launcher->cancel($campaign);

        return back()->with('flash_notify', ['type' => 'warning', 'message' => 'Campaign cancelled.']);
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        abort_unless($campaign->isEditable(), 409, 'Only draft campaigns can be deleted.');
        $campaign->delete();

        return redirect()->route('whatsapp.campaigns.index')
            ->with('flash_notify', ['type' => 'success', 'message' => 'Draft campaign deleted.']);
    }

    private function buildPreview(Campaign $campaign, mixed $contact): string
    {
        $template = $campaign->template()->first();
        if ($template === null) {
            return '';
        }

        $body = collect($template->components ?? [])->firstWhere('type', 'BODY');
        $text = is_array($body) ? (string) ($body['text'] ?? '') : '';
        $values = $this->renderer->render($campaign, $contact instanceof Contact ? $contact : null);

        return preg_replace_callback('/\{\{\s*(\d+)\s*\}\}/', fn ($m) => $values[(int) $m[1] - 1] ?? "[{$m[1]}]", $text) ?? $text;
    }
}
