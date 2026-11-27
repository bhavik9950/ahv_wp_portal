<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionRequest;
use App\Services\System\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSubscriptionController extends Controller
{
    public function __construct(private readonly SystemSettings $settings) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $query = SubscriptionRequest::query()->withoutGlobalScopes()
            ->with(['organization', 'user', 'approvedBy'])
            ->latest('id');

        if ($status && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(25)->withQueryString();

        return view('admin.subscriptions.index', [
            'requests' => $requests,
            'currentStatus' => $status,
            'upiId' => $this->settings->billingUpiId(),
            'bankDetails' => $this->settings->billingBankDetails(),
            'plans' => $this->settings->billingPlans(),
        ]);
    }

    public function approve(SubscriptionRequest $subscriptionRequest, Request $request): RedirectResponse
    {
        $org = $subscriptionRequest->organization()->firstOrFail();

        $subscriptionRequest->update([
            'status' => 'approved',
            'approved_by_user_id' => $request->user()?->getKey(),
            'approved_at' => now(),
        ]);

        // Extend organization subscription according to the requested plan
        $org->extendSubscription($subscriptionRequest->plan);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => "Subscription for '{$org->name}' approved successfully! Active plan updated to: ".ucfirst($subscriptionRequest->plan),
        ]);
    }

    public function reject(SubscriptionRequest $subscriptionRequest, Request $request): RedirectResponse
    {
        $reason = $request->input('reason', 'Payment reference could not be verified.');

        $subscriptionRequest->update([
            'status' => 'rejected',
            'approved_by_user_id' => $request->user()?->getKey(),
            'approved_at' => now(),
            'notes' => trim(($subscriptionRequest->notes ? $subscriptionRequest->notes.' | Reason: ' : 'Reason: ').$reason),
        ]);

        return back()->with('flash_notify', [
            'type' => 'warning',
            'message' => 'Subscription request has been marked as rejected.',
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'upi_id' => ['required', 'string', 'max:100'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_holder' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'ifsc' => ['required', 'string', 'max:20'],
        ]);

        $this->settings->set('billing.upi_id', $validated['upi_id']);
        $this->settings->set('billing.bank_details', [
            'bank_name' => $validated['bank_name'],
            'account_holder' => $validated['account_holder'],
            'account_number' => $validated['account_number'],
            'ifsc' => $validated['ifsc'],
        ]);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => 'Billing and payment information updated successfully.',
        ]);
    }

    public function savePlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_key' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_lifetime' => ['nullable', 'boolean'],
            'description' => ['required', 'string', 'max:300'],
        ]);

        $plans = $this->settings->billingPlans();
        $isLifetime = $request->boolean('is_lifetime');

        $plans[$validated['plan_key']] = [
            'label' => $validated['label'],
            'price' => (int) $validated['price'],
            'days' => $isLifetime ? null : (int) ($validated['days'] ?? 30),
            'description' => $validated['description'],
        ];

        $this->settings->set('billing.plans', $plans);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => "Plan '{$validated['label']}' saved successfully.",
        ]);
    }

    public function deletePlan(string $planKey): RedirectResponse
    {
        $plans = $this->settings->billingPlans();

        if (! isset($plans[$planKey])) {
            return back()->with('flash_notify', [
                'type' => 'warning',
                'message' => 'Plan not found.',
            ]);
        }

        if (count($plans) <= 1) {
            return back()->with('flash_notify', [
                'type' => 'error',
                'message' => 'At least one subscription plan must remain active for clients.',
            ]);
        }

        $label = $plans[$planKey]['label'] ?? $planKey;
        unset($plans[$planKey]);
        $this->settings->set('billing.plans', $plans);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => "Plan '{$label}' removed.",
        ]);
    }
}

