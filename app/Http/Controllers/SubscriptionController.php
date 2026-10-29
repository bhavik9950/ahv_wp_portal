<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Services\System\SystemSettings;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly SystemSettings $settings,
    ) {}

    public function index(): View
    {
        $org = $this->current->resolve();
        $plans = $this->settings->billingPlans();
        $upiId = $this->settings->billingUpiId();
        $bankDetails = $this->settings->billingBankDetails();

        $requests = SubscriptionRequest::query()
            ->latest('id')
            ->limit(10)
            ->get();

        return view('subscription.index', [
            'organization' => $org,
            'plans' => $plans,
            'upiId' => $upiId,
            'bankDetails' => $bankDetails,
            'requests' => $requests,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $plans = $this->settings->billingPlans();

        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:'.implode(',', array_keys($plans))],
            'amount' => ['required', 'integer', 'min:1'],
            'utr_number' => ['required', 'string', 'min:6', 'max:100'],
            'payment_method' => ['required', 'string', 'in:upi,bank_transfer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $org = $this->current->resolve();

        // Check for duplicate pending request with same UTR
        $existing = SubscriptionRequest::query()->withoutGlobalScopes()
            ->where('utr_number', trim($validated['utr_number']))
            ->first();

        if ($existing !== null) {
            return back()->withErrors(['utr_number' => 'This UTR / Transaction Reference has already been submitted.'])->withInput();
        }

        SubscriptionRequest::query()->create([
            'organization_id' => $org->getKey(),
            'user_id' => $user->getKey(),
            'plan' => $validated['plan'],
            'amount' => (int) $validated['amount'],
            'utr_number' => trim($validated['utr_number']),
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => 'Payment reference (UTR) submitted successfully! AHV team will verify and activate your plan shortly.',
        ]);
    }
}
