<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Services\Audit\AuditLogger;
use App\Services\WhatsApp\WabaConfigurationService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhoneNumberController extends Controller
{
    public function __construct(private readonly WabaConfigurationService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', WhatsappPhoneNumber::class);

        return view('whatsapp.phone-numbers.index', [
            'account' => WhatsappBusinessAccount::query()->orderBy('created_at')->first(),
            'numbers' => WhatsappPhoneNumber::query()->orderByDesc('is_default')->orderBy('display_phone_number')->get(),
        ]);
    }

    public function store(Request $request, WhatsAppManager $manager): RedirectResponse
    {
        $account = WhatsappBusinessAccount::query()->orderBy('created_at')->first();
        if (! $account) {
            return back()->with('flash_notify', [
                'type' => 'error',
                'message' => 'Please configure your WhatsApp settings first.',
            ]);
        }

        $this->authorize('update', $account);

        $validated = $request->validate([
            'phone_number_id' => ['required', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'display_phone_number' => ['nullable', 'string', 'max:50'],
            'verified_name' => ['nullable', 'string', 'max:100'],
        ]);

        $phoneNumberId = trim($validated['phone_number_id']);
        $display = $validated['display_phone_number'] ?? null;
        $name = $validated['verified_name'] ?? null;
        $quality = null;
        $tier = null;

        try {
            $creds = $manager->credentialsFor($account);
            $metaData = $manager->driver()->getPhoneNumber($creds, $phoneNumberId);
            $display = $metaData['display_phone_number'] ?? $display;
            $name = $metaData['verified_name'] ?? $name;
            $quality = $metaData['quality_rating'] ?? null;
            $tier = $metaData['messaging_limit_tier'] ?? null;
        } catch (\Throwable) {
            // Keep user-supplied or default values if Meta call fails
        }

        $number = $account->phoneNumbers()->firstOrNew(['phone_number_id' => $phoneNumberId]);
        $number->organization_id = $account->organization_id;
        $number->display_phone_number = $display;
        $number->verified_name = $name;
        $number->quality_rating = $quality;
        $number->messaging_limit_tier = $tier;
        $number->status = 'available';
        $number->last_synced_at = now();

        if ($account->phoneNumbers()->where('is_default', true)->doesntExist()) {
            $number->is_default = true;
        }

        $number->save();

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => "Phone Number ID {$phoneNumberId} saved successfully.",
        ]);
    }

    public function sync(): RedirectResponse
    {
        try {
            $account = WhatsappBusinessAccount::query()->orderBy('created_at')->first();
            if (! $account) {
                return back()->with('flash_notify', [
                    'type' => 'error',
                    'message' => 'Please configure and save your WhatsApp settings first before syncing phone numbers.',
                ]);
            }

            $this->authorize('update', $account);

            $count = $this->service->syncPhoneNumbers($account);

            if ($count === 0) {
                return back()->with('flash_notify', [
                    'type' => 'info',
                    'message' => 'No phone numbers found on this Meta WABA. Please add and verify a phone number in Meta Developer Console > WhatsApp > API Setup first.',
                ]);
            }

            return back()->with('flash_notify', [
                'type' => 'success',
                'message' => "Synced {$count} phone number(s) from Meta.",
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Phone numbers sync failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('flash_notify', [
                'type' => 'error',
                'message' => 'Phone Sync Error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Live check of whether WhatsApp Business Calling is available / enabled for
     * the default number — the prerequisite for any calling work. Read-only.
     */
    public function calling(WhatsAppManager $manager): View
    {
        $this->authorize('viewAny', WhatsappPhoneNumber::class);

        $account = WhatsappBusinessAccount::query()->orderBy('created_at')->first();
        $number = WhatsappPhoneNumber::query()->orderByDesc('is_default')->orderBy('display_phone_number')->first();

        $settings = null;
        $error = null;

        if ($account !== null) {
            try {
                $creds = $manager->credentialsFor($account, $number);
                $settings = $manager->driver()->getCallingSettings($creds, (string) $creds->phoneNumberId);
            } catch (\RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return view('whatsapp.phone-numbers.calling', [
            'account' => $account,
            'number' => $number,
            'settings' => $settings,
            'error' => $error,
        ]);
    }

    /** Turn WhatsApp Business Calling on/off for the default number. */
    public function updateCalling(Request $request, WhatsAppManager $manager, AuditLogger $audit): RedirectResponse
    {
        $account = WhatsappBusinessAccount::query()->orderBy('created_at')->firstOrFail();
        $this->authorize('update', $account);

        $status = strtoupper((string) $request->string('status'));
        abort_unless(in_array($status, ['ENABLED', 'DISABLED'], true), 422);

        $number = WhatsappPhoneNumber::query()->orderByDesc('is_default')->orderBy('display_phone_number')->first();

        try {
            $creds = $manager->credentialsFor($account, $number);
            $manager->driver()->updateCallingStatus($creds, (string) $creds->phoneNumberId, $status);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['calling' => $e->getMessage()]);
        }

        if ($number !== null) {
            $audit->log('whatsapp.calling.'.strtolower($status), $number, []);
        }

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => 'WhatsApp calling '.($status === 'ENABLED' ? 'enabled' : 'disabled').'.',
        ]);
    }

    public function setDefault(WhatsappPhoneNumber $phoneNumber): RedirectResponse
    {
        $this->authorize('update', $phoneNumber);

        $this->service->setDefaultPhoneNumber($phoneNumber);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => 'Default sending number updated.',
        ]);
    }
}
