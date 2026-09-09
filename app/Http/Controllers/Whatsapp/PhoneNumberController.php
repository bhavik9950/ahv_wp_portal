<?php

declare(strict_types=1);

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Services\WhatsApp\WabaConfigurationService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\RedirectResponse;
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

    public function sync(): RedirectResponse
    {
        $account = WhatsappBusinessAccount::query()->orderBy('created_at')->firstOrFail();
        $this->authorize('update', $account);

        $count = $this->service->syncPhoneNumbers($account);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => "Synced {$count} phone number(s) from Meta.",
        ]);
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
