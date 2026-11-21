# AH&V Portal: Multi-Channel Expansion Blueprint (WhatsApp + SMS + Voice)

> **Design Goal:** Seamlessly add SMS and Voice modules to AH&V Portal without cluttering the existing WhatsApp dashboard. We implement a **Module Switcher (Suite Switcher)** pattern with **per-tenant subscription paywalls**, so clients only see and access the services they pay for.

---

## 1. UX Architecture: The "Module Switcher" Concept

Instead of squeezing WhatsApp, SMS, and Voice into one messy dashboard, the portal uses a **Top-Level Suite Switcher** (similar to Google Workspace or Zoho):

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│ AH&V Portal   [ Suite: WhatsApp ▾ ]   [ Org: FIT GANGANAGAR ▾ ]  (●) User Profile │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### When Switched to "WhatsApp Portal" Mode:
* **Sidebar Shows:**
  - WhatsApp Dashboard
  - Phone Numbers & Overview
  - WhatsApp Templates (Meta)
  - WhatsApp Campaigns
  - 2-Way Live Chats
  - WhatsApp Reports & Webhooks
* **Shared Modules:** Contacts, Contact Groups, Team Members, Settings.

### When Switched to "SMS Portal" Mode:
* **Sidebar Dynamically Changes To:**
  - SMS Dashboard (Sent, Delivered, Failed, Balance)
  - DLT Headers / Sender IDs (e.g., `FITGNR`)
  - DLT Content Templates (e.g., OTP, Promo)
  - SMS Campaigns (Bulk Dispatch)
  - Quick SMS (Single / Test Message)
  - SMS Reports & DLR Logs
* **Shared Modules:** Contacts, Contact Groups, Wallet Recharge, Settings.

---

## 2. Multi-Tenant Subscription & Paywall Logic (Entitlements)

When the Platform Admin creates or edits a Client Organization, the Admin selects which modules the client has purchased:

```
[✓] WhatsApp Service (Active)
[ ] SMS Service (Inactive / Not Subscribed)
[ ] Voice AI Calling (Private Preview)
```

### What Happens When a Client Tries to Switch?
* **Case 1: Client is subscribed to both:**
  - They click the suite switcher dropdown and switch smoothly between WhatsApp and SMS dashboards.
* **Case 2: Client is subscribed to WhatsApp ONLY and clicks "SMS Portal":**
  - Instead of a 404 or broken page, they land on a **Branded Paywall Page**:
    > **🔒 SMS Service Not Subscribed**  
    > *Your organization currently does not have an active SMS plan.*  
    > *Features included in SMS:* DLT Sender ID, Transactional OTPs, Fast Bulk SMS to non-WhatsApp users.  
    > **[ Contact AH&V Software to Activate ]** or **[ Upgrade Subscription ]**
* **Case 3: Client is subscribed to SMS ONLY and clicks "WhatsApp Portal":**
  - Shows the same friendly paywall for WhatsApp.

---

## 3. Code-Level Implementation Roadmap (Priority-Wise)

```
Phase 0: Module Subscriptions & Paywall Gates
   └── (Admin Org Edit + Middleware + Paywall View)
Phase 1: UI Suite Switcher & Dynamic Sidebar
   └── (Header Dropdown + Route Prefixes /whatsapp & /sms)
Phase 2: SMS Core Engine & Prepaid Wallet
   └── (DLT Models + MSG91 REST Driver + Wallet Deductions)
Phase 3: SMS Campaigns & Queue Dispatcher
   └── (Contact Selector + Async Jobs + DLT Variable Merger)
Phase 4: Webhook DLR Engine & SMS Reports
   └── (Delivery Status Callback + Funnel Analytics)
```

---

### Priority 0 (P0): Multi-Module Entitlements & Subscription Middleware
* **Database Migration:**
  - Add `enabled_channels` (JSON array: `['whatsapp', 'sms']`) or boolean flags (`has_whatsapp`, `has_sms`, `has_voice`) to `organizations` table.
* **Platform Admin UI:**
  - Update Client Organization Create/Edit form (`admin.organizations.edit`) to toggle module access with checkboxes.
* **Middleware (`EnsureChannelSubscribed`):**
  - Route groups wrapped with `channel.subscribed:sms` or `channel.subscribed:whatsapp`.
  - Checks if `currentOrg()->hasChannel('sms')`. If false, renders the branded upgrade paywall view.

---

### Priority 1 (P1): Suite Switcher UI & Dynamic Navigation
* **Top Header Switcher Component:**
  - A clean dropdown in the top navbar next to "Switch Org":
    - `WhatsApp Portal` (Icon: Brand WhatsApp)
    - `SMS Portal` (Icon: Message Dots)
    - `Voice Calling` (Badge: Coming Soon)
* **Route Separation:**
  - All WhatsApp routes grouped under: `/whatsapp/...`
  - All SMS routes grouped under: `/sms/...`
  - Shared assets grouped under: `/contacts`, `/settings`, `/wallet`
* **Dynamic Sidebar (`layouts/navigation.blade.php`):**
  - Detects current active module from route (`request()->is('sms*')` vs `request()->is('whatsapp*')`).
  - Renders the exact relevant navigation menu without visual clutter.

---

### Priority 2 (P2): SMS Core Engine & Prepaid Wallet
* **Zero-Hardware Cloud Driver (`Msg91Gateway`):**
  - Simple Guzzle/HTTP service in Laravel calling MSG91 REST API.
  - Zero telecom hardware, zero SMPP complexity.
* **Prepaid Wallet (`organizations.wallet_balance`):**
  - Real-time balance check before dispatch.
  - Cost calculated: `Audience Count × ₹0.24`.
  - Atomically decrements balance inside DB transaction.
* **DLT Header & Template Management:**
  - Client stores their approved DLT `Principal Entity ID`, `Sender Header` (e.g., `FITGNR`), and `Template ID`.

---

### Priority 3 (P3): SMS Campaigns & Queue Dispatch
* **Models:** `SmsCampaign`, `SmsMessage`.
* **Asynchronous Queue Worker (`SendSmsCampaignJob`):**
  - Chunks contact list (500 contacts per chunk).
  - Posts to wholesale cloud API with rate limiting.
  - Tracks individual message IDs.

---

### Priority 4 (P4): DLR Webhook & Reporting
* **Webhook Endpoint:** `/api/webhooks/sms/dlr`
  - Receives delivery confirmation from MSG91.
  - Updates `sms_messages.status` to `delivered` or `failed`.
* **SMS Reports Dashboard:**
  - Delivery funnel: Total, Sent, Delivered, Failed.
  - Filter by date range, sender ID, or campaign.

---

## 4. External Work Checklist (Outside Code / Business Tasks)

Before or during the coding phase, the following non-technical tasks must be done:

### 1. Wholesale Cloud Account Setup
* Register a master business account on **[MSG91.com](https://msg91.com)**.
* Complete AH&V Software business KYC (PAN, GST, Bank details).
* Purchase an initial test credit pack (₹1,000 to ₹5,000) to obtain live API keys (`authkey`).

### 2. Payment Gateway for Wallet Auto-Recharge
* Activate **Razorpay** or **Cashfree** on your business account.
* Clients can click **"Recharge SMS Wallet"** $\rightarrow$ Scan UPI QR / Netbanking $\rightarrow$ Instant wallet top-up via webhook.

### 3. Client DLT Onboarding SOP (1-Page Guide)
* Create a simple 1-page PDF or Help Doc for your clients:
  - *"How to register on Jio DLT or Airtel DLT in 15 minutes."*
  - *"How to get your Sender Header (e.g. FITGNR)."*
  - *"How to copy your DLT Template ID into AH&V Portal."*
  - *(This saves you from spending hours explaining telecom rules to non-tech clients).*

### 4. Pricing & Margin Strategy
* **Wholesale Purchase Cost:** ~₹0.12 to ₹0.14 per SMS (from MSG91).
* **Retail Selling Price:**
  - **Plan A (Pay As You Go):** ₹0.25 per SMS (100% margin).
  - **Plan B (High Volume Recharge):** 50,000 SMS pack @ ₹0.20 per SMS (60% margin).
  - **Platform Monthly Fee:** ₹999/mo for SMS only, or ₹1,999/mo for Combo (WhatsApp + SMS).

---

## 5. Summary Recommendation

| Step | What To Build | Effort | Who Owns It |
|---|---|---|---|
| **Step 1** | Module Switcher UI + Entitlements Middleware + Paywall View | 1 - 2 Days | Developer |
| **Step 2** | MSG91 Account KYC & Test API Key | 1 Day | Business / Admin |
| **Step 3** | SMS DLT Templates + Prepaid Wallet + Campaign Dispatch | 2 - 3 Days | Developer |
| **Step 4** | Webhook DLR + SMS Reports & Analytics | 1 - 2 Days | Developer |
| **Step 5** | Launch to First Pilot Client (e.g., FIT Ganganagar) | 1 Day | Business + Developer |
