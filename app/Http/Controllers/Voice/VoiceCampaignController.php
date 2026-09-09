<?php

declare(strict_types=1);

namespace App\Http\Controllers\Voice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voice\StoreVoiceCampaignRequest;
use App\Models\ContactGroup;
use App\Models\VoiceCampaign;
use App\Services\Voice\VoiceBroadcastManager;
use App\Services\Voice\VoiceCampaignService;
use App\Services\WhatsApp\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class VoiceCampaignController extends Controller
{
    public function __construct(
        private readonly VoiceCampaignService $service,
        private readonly VoiceBroadcastManager $manager,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', VoiceCampaign::class);

        return view('voice-campaigns.index', [
            'campaigns' => VoiceCampaign::query()->withCount('recipients')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', VoiceCampaign::class);

        return view('voice-campaigns.create', [
            'groups' => ContactGroup::query()->withCount('contacts')->orderBy('name')->get(),
            'providerConfigured' => $this->manager->isConfigured(),
        ]);
    }

    public function store(StoreVoiceCampaignRequest $request, MediaLibrary $media): RedirectResponse
    {
        $this->authorize('create', VoiceCampaign::class);

        $data = $request->validated();

        try {
            $audio = $media->store($request->file('audio'));
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['audio' => $e->getMessage()]);
        }

        $campaign = $this->service->createDraft($data['name']);
        $campaign->forceFill([
            'audio_media_id' => $audio->getKey(),
            'audience_filter' => [
                'type' => $data['audience_type'],
                'group_ids' => $data['group_ids'] ?? [],
                'contact_ids' => $data['contact_ids'] ?? [],
                'exclude_group_ids' => $data['exclude_group_ids'] ?? [],
            ],
            'delay_seconds' => (int) $data['delay_seconds'],
            'consent_confirmed' => true,
        ])->save();

        $at = $data['mode'] === 'schedule'
            ? Carbon::parse($data['scheduled_at'], $campaign->timezone)
            : null;

        try {
            $this->service->launch($campaign, $at);
        } catch (\RuntimeException $e) {
            return redirect()->route('whatsapp.voice-campaigns.show', $campaign)
                ->withErrors(['launch' => $e->getMessage()]);
        }

        return redirect()->route('whatsapp.voice-campaigns.show', $campaign)
            ->with('flash_notify', ['type' => 'success', 'message' => $at ? 'Voice campaign scheduled.' : 'Voice campaign started.']);
    }

    public function show(VoiceCampaign $voiceCampaign): View
    {
        $this->authorize('view', $voiceCampaign);

        $voiceCampaign->load('audio');

        return view('voice-campaigns.show', [
            'campaign' => $voiceCampaign,
            'totals' => $voiceCampaign->totals ?? $voiceCampaign->recomputeTotals(),
            'recipients' => $voiceCampaign->recipients()->with('contact')->limit(2000)->get(),
        ]);
    }

    public function pause(VoiceCampaign $voiceCampaign): RedirectResponse
    {
        $this->authorize('launch', $voiceCampaign);
        $this->service->pause($voiceCampaign);

        return back()->with('flash_notify', ['type' => 'warning', 'message' => 'Voice campaign paused.']);
    }

    public function resume(VoiceCampaign $voiceCampaign): RedirectResponse
    {
        $this->authorize('launch', $voiceCampaign);
        $this->service->resume($voiceCampaign);

        return back()->with('flash_notify', ['type' => 'success', 'message' => 'Voice campaign resumed.']);
    }

    public function cancel(VoiceCampaign $voiceCampaign): RedirectResponse
    {
        $this->authorize('launch', $voiceCampaign);
        $this->service->cancel($voiceCampaign);

        return back()->with('flash_notify', ['type' => 'warning', 'message' => 'Voice campaign cancelled.']);
    }

    public function destroy(VoiceCampaign $voiceCampaign): RedirectResponse
    {
        $this->authorize('delete', $voiceCampaign);

        abort_unless($voiceCampaign->isEditable(), 409, 'Only draft campaigns can be deleted.');
        $voiceCampaign->delete();

        return redirect()->route('whatsapp.voice-campaigns.index')
            ->with('flash_notify', ['type' => 'success', 'message' => 'Draft voice campaign deleted.']);
    }
}
