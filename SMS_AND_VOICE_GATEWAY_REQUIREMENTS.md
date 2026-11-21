# AH&V Portal: Recommended Cloud SMS & AI Voice Calling Blueprint

> **Executive Recommendation:** Build a **100% Zero-Hardware, Cloud CPaaS Reseller & Aggregator** service directly inside your existing AH&V Portal. Do not buy physical devices, SIM boxes, or telecom hardware. Use wholesale cloud REST APIs, charge clients upfront via a prepaid credit wallet with a 50–100% margin, and collect monthly portal SaaS fees.

---

## 1. The Recommended Business Model

```
[Wholesale Cloud Providers]
  • SMS: MSG91 Enterprise API (Wholesale: ~₹0.12 - ₹0.14 / SMS)
  • Voice: Exotel / Vapi.ai Cloud API (Wholesale: ~₹1.80 / call min)
                     ▲
                     │ (Backend Cloud REST APIs)
[AH&V Portal Multi-Tenant Platform]
  • Multi-tenant campaigns, contact management, DLT template mapping
  • Prepaid wallet (UPI / Razorpay auto-recharge)
                     │
                     ▼ (Billed to Clients)
[Your Clients (e.g., Fit Ganganagar, Local Businesses)]
  • Pay ₹0.22 - ₹0.25 per SMS (You keep ₹0.10 - ₹0.12 net profit per SMS)
  • Pay ₹4.00 per Voice call minute (You keep ₹2.20 net profit per min)
  • Pay ₹999 - ₹2,499 / month portal subscription fee
```

### Monthly Earning Projection (Example: 20 Active Clients)
* **SMS Margin (5,00,000 total SMS):** Profit ₹0.11/SMS = **₹55,000 / month**
* **AI Voice Calls (10,000 minutes):** Profit ₹2.20/min = **₹22,000 / month**
* **Monthly SaaS Fees (20 clients × ₹1,499):** = **₹29,980 / month**
* **Total Estimated Net Profit:** **₹1,06,980+ per month** *(Zero hardware overhead)*

---

## 2. Recommended Cloud Partners

| Channel | Recommended Partner | Why This Partner? |
|---|---|---|
| **Bulk SMS (Transactional & OTP)** | **MSG91** (`msg91.com`) | Best-in-class developer APIs, instant DLT template pass-through, fast delivery (<2 sec for OTPs), native Indian business support. |
| **Outbound Telephony (Calls)** | **Exotel** (`exotel.com`) | India’s leading compliant cloud telephony provider. Handles virtual caller IDs, 9 AM – 9 PM calling hours, and DND auto-filtering. |
| **AI Voice Brain (Conversational Agent)** | **Vapi.ai / Retell AI** | Fully hosted, sub-600ms conversational AI pipeline. Connects to Exotel/Twilio numbers and handles Speech-to-Text, LLM logic, and natural Hindi/English voice synthesis without hosting your own PBX. |

---

## 3. How DLT is Handled (Zero Legal Liability for You)

You do **not** take the headache or liability of government telecom registration:
1. **Client's Role:** Each client registers their own business on Jio DLT or Airtel DLT (standard one-time process for any Indian business). They get:
   - `Principal Entity (PE) ID`
   - `Sender Header` (e.g., `FITGNR`)
   - `DLT Content Template ID`
2. **Portal's Role:** Your portal simply gives them 3 input fields in their organization settings:
   - Client pastes their `Entity ID`, `Sender ID`, and `Template ID`.
   - When a campaign runs, your backend sends these 3 IDs along with the message to MSG91.
   - The telecom operators verify the DLT chain automatically. If approved, the message delivers instantly; if not, the provider reports the error without affecting your company.

---

## 4. Technical Implementation in AH&V Portal

Your current Laravel codebase is already prepared for multi-tenancy (`OrganizationScope`) and queued dispatch. Adding SMS requires 4 clean additions:

### A. Database Migration: `sms_messages` & `wallet_balance`
```php
// Add wallet_balance to organizations
Schema::table('organizations', function (Blueprint $table) {
    $table->decimal('wallet_balance', 10, 2)->default(0.00);
});

// Create sms_messages table
Schema::create('sms_messages', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->string('to_phone', 20);
    $table->string('sender_id', 10);
    $table->string('dlt_template_id', 50)->nullable();
    $table->text('message_body');
    $table->decimal('cost', 8, 4);          // Billed to client (e.g. 0.25)
    $table->decimal('provider_cost', 8, 4); // Cost from MSG91 (e.g. 0.13)
    $table->decimal('profit', 8, 4);        // Net gain (e.g. 0.12)
    $table->string('status', 20)->default('queued'); // queued, sent, delivered, failed
    $table->string('provider_message_id', 100)->nullable();
    $table->timestamps();

    $table->index(['organization_id', 'created_at']);
});
```

### B. Gateway Service: `app/Services/Sms/Msg91Gateway.php`
```php
namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class Msg91Gateway
{
    public function send(string $toPhone, string $senderId, string $templateId, array $variables = []): array
    {
        $response = Http::withHeaders([
            'authkey' => config('services.msg91.auth_key'),
            'Content-Type' => 'application/json',
        ])->post('https://control.msg91.com/api/v5/flow/', [
            'template_id' => $templateId,
            'sender' => $senderId,
            'recipients' => [
                array_merge(['mobiles' => $toPhone], $variables)
            ]
        ]);

        return $response->json() ?? [];
    }
}
```

### C. Campaign Queue Job & Wallet Deduction
```php
// Before dispatching message:
$costPerSms = 0.24;
if ($organization->wallet_balance < $costPerSms) {
    throw new InsufficientBalanceException("Please recharge your SMS wallet.");
}

// Deduct immediately in DB transaction:
$organization->decrement('wallet_balance', $costPerSms);

// Dispatch to MSG91 via queue
SendSmsMessageJob::dispatch($messageId)->onQueue('sms-high');
```

### D. Delivery Report (DLR) Webhook: `routes/api.php`
```php
Route::post('webhooks/sms/dlr', function (Request $request) {
    $providerMsgId = $request->input('requestId');
    $status = strtolower($request->input('status')); // delivered, failed

    Message::where('provider_message_id', $providerMsgId)
        ->update([
            'status' => $status === 'delivered' ? 'delivered' : 'failed',
            'updated_at' => now(),
        ]);

    return response()->json(['status' => 'ok']);
});
```

---

## 5. Step-by-Step Launch Plan

1. **Step 1: Open an MSG91 Account**
   - Register at `msg91.com`, complete KYC, and purchase ₹1,000 test credits.
   - Obtain the API `authkey`.
2. **Step 2: Add SMS Tab to AH&V Sidebar**
   - Add **SMS Campaigns**, **SMS Templates (DLT)**, and **SMS Reports** in the left sidebar alongside WhatsApp.
3. **Step 3: Integrate Prepaid Wallet**
   - Use Razorpay / Cashfree UPI QR so clients can click "Recharge Wallet" and add ₹500, ₹1,000, or ₹5,000 instantly.
4. **Step 4: AI Voice Module (Next Phase)**
   - Sign up on `vapi.ai` or `exotel.com`.
   - Setup automated phone calls that qualify leads and play voice responses using Gemini Flash + Sarvam AI Hindi voice.
