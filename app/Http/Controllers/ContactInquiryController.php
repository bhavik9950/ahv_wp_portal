<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContactInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactInquiryController extends Controller
{
    /**
     * Store a new contact/onboarding inquiry with anti-bot validation.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Honeypot check: Bots fill hidden fields
        if ($request->filled('b_hp_security_check')) {
            return back()->with('success', 'Thank you! Your inquiry has been submitted successfully. Our team will contact you shortly.');
        }

        // 2. Math Captcha validation
        $cNum1 = (int) $request->session()->get('inquiry_captcha_num1', 0);
        $cNum2 = (int) $request->session()->get('inquiry_captcha_num2', 0);
        $expectedAnswer = $cNum1 + $cNum2;
        $userAnswer = (int) $request->input('captcha_answer');

        if ($expectedAnswer <= 0 || $userAnswer !== $expectedAnswer) {
            return back()
                ->withInput()
                ->withErrors(['captcha_answer' => 'Incorrect security captcha answer. Please solve the math question again.']);
        }

        // Reset captcha in session after use
        $request->session()->forget(['inquiry_captcha_num1', 'inquiry_captcha_num2']);

        // 3. Validation
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:100'],
            'business_name' => ['nullable', 'string', 'max:150'],
            'service_interested' => ['required', 'string', 'in:whatsapp,sms,voice,all'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        ContactInquiry::query()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'business_name' => $validated['business_name'] ?? null,
            'service_interested' => $validated['service_interested'],
            'message' => $validated['message'] ?? null,
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        return redirect()->to(url()->previous().'#contact-form')
            ->with('success', 'Thank you! Your request has been submitted. Our onboarding specialist will contact you on WhatsApp / Phone within 2-4 hours.');
    }
}
