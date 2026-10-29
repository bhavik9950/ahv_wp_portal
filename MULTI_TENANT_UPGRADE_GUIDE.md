# AHV WhatsApp Portal — Multi-Tenant Architecture & Upgrade Roadmap

> **Platform Status:** Ready for Multi-Tenancy.  
> **Key Benefit:** Aapko **0 se shuru karne ki bilkul zaroorat nahi hai**. Is portal ka database aur code architecture pehle din se hi **Multi-Tenant First** design kiya gaya hai. Har table me `organization_id` aur automatic tenant scoping pehle se active hai.

---

## 1. Current Architecture: Portal Me Kya Pehle Se Multi-Tenant Hai?

| Component | Current Status | Kaise Kaam Karta Hai |
| :--- | :--- | :--- |
| **Database Isolation** | ✅ **100% Ready** | Sabhi models (`Message`, `Campaign`, `Contact`, `WhatsappTemplate`, `BotConversation`, `Media`, etc.) `BelongsToOrganization` trait use karte hain. Har query me automatically `WHERE organization_id = ?` lagta hai. |
| **Meta WABA per Client** | ✅ **100% Ready** | `whatsapp_business_accounts` table me har Organization ka apna alag `waba_id`, `phone_number_id`, aur `access_token` encrypted store hota hai. |
| **Webhook Routing** | ✅ **100% Ready** | Meta jab webhook bhejta hai, usme `metadata.phone_number_id` hota hai. Portal `phone_number_id` se turant us client ki exact `Organization` dhoondh leta hai. **Org A aur Org B ke data me koi mix-up nahi ho sakta.** |
| **Role & Permission Isolation** | ✅ **100% Ready** | Spatie Permission **teams mode** me configured hai jahan `team_foreign_key = organization_id`. Client ka user sirf apni organization ke role (`org_admin`, `campaign_manager`, `viewer`) me rehta hai. |
| **Super Admin vs Client** | ✅ **100% Ready** | `users.is_super_admin` flag AHV core team ke paas hai. Global system health, emergency kill switch, aur raw webhook events sirf super admin ko dikhte hain. |

---

## 2. Meta Verification & Client Card / Billing Setup

Aapka sawal: *Client ka Meta verify ho aur Credit/Debit card usi ka use ho taaki Meta ke message charges usi ke card se katein, AHV ke nahi.*

### Yeh Kaise Possible Hai? (Meta Cloud API Billing Model)
Meta WhatsApp Cloud API me billing **WABA (WhatsApp Business Account)** level par hoti hai, portal level par nahi. Meta har 24-ghante ke conversation window ka charge us WABA se jude **Payment Method (Credit/Debit Card)** se deduct karta hai.

Do practical tareeqe hain ise implement karne ke:

### Option A: Direct Client WABA Setup (Sabse Simple — Zero Code Changes)
1. **Client Meta Business Manager banayega:**  
   Client `business.facebook.com` par jaakar apna Business Account create/verify karta hai.
2. **Client apna Card add karega:**  
   Client apne Meta Business Manager me `Settings > Payment Methods` me jaakar apna **Credit / Debit Card** add karta hai aur use apne WABA par default billing set karta hai.  
   *(Result: Meta ke har message ka paisa direct client ke bank/card se katega, AHV par ₹0 ka bill aayega).*
3. **Portal par Phone Number link karna:**  
   Client (ya AHV admin) client ki Organization me jakar `WABA Settings` me unka:
   - `WABA ID`
   - `Phone Number ID`
   - `Permanent System User Access Token`
   daal deta hai.
4. **Done:** Client ka phone number activate ho jata hai aur saare message usi ke WABA aur card se chalte hain.

---

### Option B: Meta Embedded Signup (One-Click "Login with Facebook" Popup)
Agar aap chahte hain ki client ko Meta Business Manager me manually IDs copy-paste na karni pade:
1. Aapke AHV Meta App me **WhatsApp Embedded Signup** enable kiya jata hai.
2. Client ke dashboard me ek button hota hai: **"Connect WhatsApp with Facebook"**.
3. Client pop-up me Facebook se login karta hai, apna business choose karta hai, apna phone number verify karta hai, aur wahin **apna Credit/Debit Card attach kar deta hai**.
4. Meta securely aapke portal ko access token aur phone number ID callback ke through return kar deta hai.
5. Portal auto-create kar deta hai unka `WhatsappBusinessAccount` aur `WhatsappPhoneNumber`.

---

## 3. Data Conflict Kyun Nahi Hoga? (Security Guarantee)

Aapka concern tha ki *naya user aane par data conflict to nahi hoga?*

Nahi hoga, kyunki:

```
                  ┌──────────────────────────────────────────────┐
                  │           Central Meta Webhook URL           │
                  │   https://wap.ahvsoftware.com/api/webhooks   │
                  └──────────────────────┬───────────────────────┘
                                         │
                         (Webhook contains phone_number_id)
                                         │
                                         ▼
                  ┌──────────────────────────────────────────────┐
                  │    ProcessWhatsAppWebhookJob (Router)        │
                  │   WhatsappPhoneNumber::where(phone_id)       │
                  └──────────────────────┬───────────────────────┘
                                         │
                 ┌───────────────────────┴───────────────────────┐
                 │                                               │
                 ▼                                               ▼
     ┌──────────────────────┐                        ┌──────────────────────┐
     │   Organization #1    │                        │   Organization #2    │
     │      (AHV Main)      │                        │     (New Client)     │
     ├──────────────────────┤                        ├──────────────────────┤
     │ • Own Contacts       │                        │ • Own Contacts       │
     │ • Own Templates      │                        │ • Own Templates      │
     │ • Own Chats/Messages │                        │ • Own Chats/Messages │
     │ • Own WABA & Cards   │                        │ • Own WABA & Cards   │
     └──────────────────────┘                        └──────────────────────┘
```

1. **Global Eloquent Scopes:** Agar koi developer galti se `Contact::all()` bhi likh de, to database sirf logged-in user ki `organization_id` ke contacts return karega.
2. **Session Context (`EnsureTenantContext`):** Jab client login karta hai, uska session uski organization ke sath lock ho jata hai. Woh doosre tenant ka data kisi bhi URL se access nahi kar sakta (404/403 return hota hai).
3. **Media Storage:** Har client ki files aur media unke organization folder me store hoti hain (`storage/app/organizations/{org_id}/media/...`).

---

## 4. Client Dashboard: Restricted Modules (Kya Dena Hai, Kya Chhupana Hai)

Client ko same simple dashboard replica milega, lekin administrative controls restrict rahenge:

| Module / Feature | AHV Admin (Super Admin) | New Client (`OrgAdmin` / `Manager`) | Restriction Kaise Lagegi |
| :--- | :---: | :---: | :--- |
| **Campaigns & Broadcasts** | ✅ Yes | ✅ Yes (Apne contacts ko) | Pehle se scoped hai |
| **1-on-1 Inbox & Live Chats** | ✅ Yes | ✅ Yes (Apne customers se) | Pehle se scoped hai |
| **Templates Management** | ✅ Yes | ✅ Yes (Apne Meta approved) | Pehle se scoped hai |
| **Contacts & Import CSV** | ✅ Yes | ✅ Yes (Apne contacts) | Pehle se scoped hai |
| **Reports & Analytics** | ✅ Yes | ✅ Yes (Apna delivery rate) | Pehle se scoped hai |
| **AI Assistant (Bot)** | ✅ Yes | ⚙️ Configurable per org | `bot_settings.enabled` per org |
| **Voice Campaigns** | ✅ Yes | ⚙️ Optional add-on | Role permission / Feature flag |
| **WABA Raw API Keys** | ✅ Full Access | 🔒 View-only ya Hidden | Prevent client from breaking token |
| **Admin Controls (`/admin/*`)** | ✅ Full Access | ❌ **Strictly Hidden / 403** | `EnsureSuperAdmin` middleware |
| **Webhook Events Logs** | ✅ Full Access | ❌ **Strictly Hidden / 403** | Super admin only |
| **System Health & Killswitch**| ✅ Full Access | ❌ **Strictly Hidden / 403** | Super admin only |

---

## 5. Step-by-Step Upgrade Roadmap (Bina Dubara 0 Se Start Kiye)

Portal ko current state se multi-client production me switch karne ke 4 aasan steps:

### Step 1: Tenancy Mode Switch karna (1 Minute)
`config/tenant.php` me setting already maujood hai:
```env
# .env file me add karein:
TENANT_MODE=multi
```
Isse `EnsureTenantContext` middleware automatic pehli org lene ke bajaye user ke linked organization ko load karega.

### Step 2: Naya Client Onboard Karne Ka Artisan Command
Naye client ke liye organization aur admin user create karne ka ek simple command:
```bash
php artisan org:create "Client Company Name" --admin-email="client@client.com" --admin-name="Client Admin"
```
Command internally:
1. `Organization::create(['name' => 'Client Company Name', 'slug' => 'client-slug'])`
2. `User::create([...])`
3. `$user->organizations()->attach($org)`
4. `$user->assignRole(OrganizationRole::OrgAdmin)` (scoped to this org)
5. `WhatsappBusinessAccount::create([...])`

### Step 3: Sidebar & Menu Restrictions Ensure Karna
Sidebar Blade file (`resources/views/layouts/navigation.blade.php` ya sidebar partial) me:
- **Admin Section** (`Webhook Events`, `System Health`, `Emergency Controls`) already super admin check par hona chahiye:
  ```blade
  @if (auth()->user()->isSuperAdmin())
      <div class="menu-title">Admin</div>
      <li><a href="{{ route('admin.webhook-events.index') }}">Webhook Events</a></li>
      <li><a href="{{ route('admin.health') }}">System Health</a></li>
      <li><a href="{{ route('admin.controls') }}">Emergency Controls</a></li>
  @endif
  ```
- Client user jab login karega, use clean business dashboard dikhega (Campaigns, Chats, Messages, Contacts, Media, Reports).

### Step 4: Organization Switcher (Only for AHV Super Admins)
AHV team agar chahe ki ek click par Client A ya Client B ke dashboard me jaakar dekh sake:
- Top bar par ek dropdown add karein: `Switch Organization` (sirf `isSuperAdmin()` ke liye visible).
- Dropdown select karne par session me `current_organization_id` change ho jata hai.
- Client users ko yeh dropdown nahi dikhega — woh strictly apni company ke portal par rahenge.

---

## 6. Summary Checklist

- [x] **Enum MessageType Fix:** `unsupported`, `reaction`, `sticker`, `contacts`, `button`, etc. support add ho chuka hai.
- [x] **Webhook Resilience:** Webhooks unknown message types par crash nahi honge.
- [x] **Architecture Verified:** System 100% multi-tenant ready hai without rewriting any module.
- [ ] **Client Onboarding:** Naye client ka Meta Business Manager me apna card add karwa ke WABA link karna.

