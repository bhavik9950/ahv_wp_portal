# Architecture Assessment & Design — AH&V WhatsApp/WABA Portal

## 1. Context

No pre-existing AH&V Laravel codebase was available in the working directory
(`c:\ahv_wp_portal` contained only `.env` and `plan.md`). This build is therefore
**greenfield** on Laravel 12, but structured so it can later be merged into, or
kept consistent with, an existing AH&V ecosystem.

### Local environment constraints

| Component | Available locally | Strategy |
|-----------|-------------------|----------|
| PHP       | 8.2.12            | Laravel 12 supports `^8.2`. Production target 8.3+. |
| Composer  | 2.10              | OK |
| MySQL 8   | No                | SQLite for local dev/tests; MySQL 8 for production (documented). |
| Redis     | No                | `database` queue + cache driver locally; Redis + Horizon in production. |
| Docker    | No                | Bare-metal deployment docs (Nginx/PHP-FPM/Supervisor). |

All infrastructure choices are `.env`-switchable so no code changes are needed to
move from local to production.

## 2. High-level architecture

```
Browser (Blade + Livewire/Alpine)
      │  session auth, CSRF
      ▼
Laravel 12 app  ──►  Policies / Form Requests / Services
      │                      │
      │                      ├─ Services\WhatsApp\*  (Meta integration, behind a driver)
      │                      ├─ Queue jobs (send, webhook, media, reports)
      │                      └─ Events → Broadcasting (prod) / polling (local)
      ▼
MySQL 8 / SQLite      Redis / database queue      Object storage (R2/S3) / local disk
      ▲                      │
      │                      ▼
      │              Horizon workers ──► Meta Graph API (WhatsApp Cloud API)
      │                                        │
      └──────────  Webhook controller  ◄───────┘  (status + inbound events)
```

## 3. Module boundaries

- **Auth & Identity** — Laravel session auth (Breeze-style), optional Sanctum for
  a future API. Login throttling, password rules, session expiration.
- **Tenancy** — `organizations`, `organization_user` pivot, a `current_organization_id`
  on the session, a global `BelongsToOrganization` trait + global scope, and a
  `EnsureTenantContext` middleware. Every tenant-owned model carries
  `organization_id` and is scoped automatically.
- **RBAC** — roles/permissions per organization (spatie/laravel-permission with
  `teams` mode, team = organization) plus one platform-level `super_admin`.
- **WABA Configuration** — encrypted credential storage per
  `whatsapp_business_account`; connection validators.
- **Meta Service Layer** — `app/Services/WhatsApp/*` with a driver contract
  (`meta_cloud_api` and `mock`), a rate limiter, an error normaliser.
- **Templates** — local model synced from Meta; builder + validation.
- **Webhooks** — signature-verified endpoint, raw event persistence, async
  processing with idempotency.
- **Messages & Status** — outbound message records, append-only status events.
- **Contacts / Groups / Opt-in** — CRUD, async CSV import, consent ledger.
- **Campaigns** — wizard, batching, throttling, scheduling, pause/resume, retry.
- **Reporting** — dashboard aggregates, per-campaign reports, CSV export.
- **Audit & Admin** — audit log, emergency kill switches, health monitor.

## 4. Service layer contract

```
interface WhatsAppDriver
    sendText(To, string $body): SendResult
    sendTemplate(To, TemplateMessage): SendResult
    sendMedia(To, MediaMessage): SendResult
    sendLocation(To, LocationMessage): SendResult
    sendInteractive(To, InteractiveMessage): SendResult
    uploadMedia(file): MediaId
    fetchTemplates(WabaId): TemplateCollection
    createTemplate(WabaId, TemplateDefinition): TemplateSubmitResult
    deleteTemplate(WabaId, name): void
    getPhoneNumber(PhoneNumberId): PhoneNumberInfo
    verifyToken(): TokenStatus
```

`SendResult` normalises `{ wamid?, accepted: bool, error?: NormalizedError }`.
`NormalizedError` classifies into: `temporary | permanent | rate_limited |
auth | invalid_recipient | template | media | unknown` and carries the raw Meta
payload for admin diagnostics only.

Controllers never call Meta directly. Jobs call services; services call the driver.

## 5. Configuration

`config/services.php`:

```php
'whatsapp' => [
    'driver'          => env('WABA_DRIVER', 'mock'),
    'base_url'        => env('WABA_BASE_URL', 'https://graph.facebook.com'),
    'api_version'     => env('WABA_API_VERSION', 'v22.0'),
    'default_cc'      => env('WABA_DEFAULT_COUNTRY_CODE', '91'),
    'webhook_verify_token' => env('WABA_WEBHOOK_VERIFY_TOKEN'),
    'app_secret'      => env('WABA_APP_SECRET'),
    'sending_enabled' => env('WHATSAPP_SENDING_ENABLED', true),
    'log_channel'     => env('WABA_LOG_CHANNEL', 'whatsapp'),
],
```

Per-organization credentials (access token, phone number id, waba id, app secret)
live **encrypted in the database**, not in `.env`. The `.env` values are only a
fallback/bootstrap for a single-tenant install and for the mock driver.

## 6. Deferred / out of scope for Phases 1–2

Contacts, groups, CSV import, opt-in ledger (Phase 3); campaigns, batching,
throttling, scheduling (Phase 4); dashboards and reports (Phase 5); load testing,
production hardening docs completion (Phase 6).
