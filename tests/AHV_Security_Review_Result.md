# AH&V WhatsApp Portal — Internal Security Review Result

**Project:** AH&V Software — WhatsApp / WABA Management Portal
**Environment reviewed:** LOCAL working tree (`c:\ahv_wp_portal`), commit state as of 2026‑08‑31
**Stack:** Laravel 12 · PHP 8.2 (target 8.3+) · MySQL 8 · Blade + Turbo Drive + Alpine + Vite · Meta WhatsApp Cloud API
**Method:** Authorized static/code review against `tests/AHV_Universal_Security_Testing_Checklist.md`. No destructive testing, no DoS, no real‑payment manipulation, no mass data extraction. `composer audit` + `npm audit` run locally.
**Reviewer note:** This is a code + configuration review. Items marked *(manual)* still need live Burp/DAST verification, especially anything gated on `TENANT_MODE=multi` or real HTTPS.

---

## A. Executive summary

The application is **well‑built for its threat model**. Multi‑tenancy, webhook authenticity, secret handling, authorization, injection safety and file‑upload validation are all implemented deliberately and hold up under review. Dependencies are clean. There are **no Critical findings and one High finding**, which is a configuration/hygiene issue (a real `APP_KEY` committed to `.env.example`) made materially worse by the project's known git‑history token leak.

The remaining findings are **Medium/Low**: a production‑config guard rail around `TRUSTED_PROXIES`, a systemic (but currently dormant) tenant‑unscoped `exists:` validation pattern, the new Webhook‑Events viewer not being tenant‑scoped, and CSV formula‑injection on exports. None of these are exploitable for data theft in the current **single‑tenant** deployment, but several become High the moment `TENANT_MODE=multi` is switched on.

**Release recommendation:** *Conditional GO for the single‑tenant production launch* once **H‑1**, **M‑1** and the **L‑2 production‑config checklist** are done. **Do NOT enable `TENANT_MODE=multi`** until **M‑2** and **M‑3** are fixed.

---

## B. Critical / High findings

### H‑1 — Live `APP_KEY` committed to `.env.example`

```text
Finding ID:        H-1
Title:             Application encryption key (APP_KEY) is committed to the repo in .env.example, identical to the live .env key
Severity:          High
Affected Project:  AH&V WhatsApp Portal
Affected file:     .env.example line 3  (APP_KEY=base64:/n0z...)  — same value as .env line 3
Affected Role:     n/a (repo read access)
Prerequisites:     Read access to the Git repo (history already contains a leaked live WABA token — see project memory) + any copy of the database (backup, dump, a future SQLi, a stolen disk).
Steps to Reproduce:
  1. Read APP_KEY from .env.example in the repository / Git history.
  2. Obtain any dump of `whatsapp_business_accounts` (access_token, app_secret,
     webhook_verify_token columns are Eloquent `encrypted` casts).
  3. Decrypt with the known key → full WABA credentials in cleartext.
  4. Same key signs every `signed` URL: forge `/unsubscribe/{contact}` links to
     mass opt‑out contacts, or forge `/whatsapp/media/{id}` URLs.
  5. Same key encrypts session cookies (SESSION_ENCRYPT=true) → forge sessions.
Expected Result:   .env.example ships a placeholder; the production key is unique and never committed.
Actual Result:     A working key is in the repo and is the same key the app currently runs on.
Security Impact:   Decryption of all at-rest WABA secrets; signed-URL forgery; session-cookie forgery. Amplified by the prior GitHub token-leak incident.
Business Impact:   WABA takeover / bans, customer opt-out sabotage, account compromise.
Evidence:          .env.example:3 == .env:3
Recommended Fix:
  - `php artisan key:generate` to mint a NEW key for the real deployment.
  - Replace the value in .env.example with `APP_KEY=` (empty placeholder).
  - Re-encrypt stored secrets under the new key (re-run `php artisan waba:setup`
    or `php artisan model:prune`-style re-save), OR accept re-entry via Settings.
  - Rotate the WABA access token (still outstanding from the earlier incident).
  - Confirm `.gitignore` keeps `.env` out (it does) and scrub the key from history
    if the repo is or was ever public.
Status:            OPEN
```

---

## C. Medium / Low findings

### M‑1 — `TRUSTED_PROXIES=*` must not reach production

```text
Severity:          Medium
Affected file:     .env line ~8  (TRUSTED_PROXIES=*), bootstrap/app.php trustProxies()
Prerequisites:     TRUSTED_PROXIES=* left set in a production .env with no trusted proxy actually in front of PHP-FPM.
Impact:            An attacker sets `X-Forwarded-For: <random>` on each request and:
                   - rotates past the per-IP login limiter (20/min/IP) and the
                     `whatsapp-webhook` limiter (600/min/IP);
                   - writes a forged client IP into audit_logs and webhook_events.
                   The per-EMAIL login limiter (5/min) still applies, so this is
                   throttle degradation, not a full auth bypass.
Why it exists:     Correctly set to "*" for the Cloudflare quick-tunnel used in webhook testing.
Recommended Fix:   In production set TRUSTED_PROXIES to the real fronting proxy only —
                   Cloudflare's published IP ranges, or the Hostinger load-balancer /
                   Nginx address (often `127.0.0.1` if Nginx → PHP-FPM on the same box).
                   Never `*`. Add this to the deployment checklist.
Status:            OPEN (acceptable for local tunnel testing)
```

### M‑2 — Tenant‑unscoped `exists:` validation rules (systemic, currently dormant)

```text
Severity:          Medium  (→ High if TENANT_MODE=multi)
Affected files:
  app/Http/Requests/Campaigns/UpdateCampaignRequest.php  (whatsapp_phone_number_id,
      template_id, media_id, audience_filter.group_ids.*, .contact_ids.*,
      .exclude_group_ids.*)
  app/Http/Requests/Whatsapp/SendTestMessageRequest.php  (whatsapp_phone_number_id,
      template_id)
  app/Http/Requests/Contacts/StoreContactRequest.php     (groups.*)
  app/Http/Controllers/Contacts/ContactGroupController.php:70-72 (group_id, contact_ids.*)
  app/Http/Controllers/Contacts/ContactImportController.php:78   (group_id)
Issue:             Every `exists:<table>,id` rule queries the raw table with no
                   `organization_id` predicate, so a crafted request can reference
                   another organization's phone number / template / media / group /
                   contact id and pass validation.
Current impact:    LOW in practice — single-tenant (no "other" org), and OrganizationScope
                   on the models means the referenced object resolves to null when the
                   campaign/send actually loads it (scoped relations, `findOrFail`).
                   No cross-tenant data is *read*.
Future impact:     HIGH once TENANT_MODE=multi, or if any consumer switches to
                   `withoutGlobalScopes()` / raw `whereIn()`. E.g. assign your campaign
                   another org's media_id, send a test from another org's phone number.
Recommended Fix:   Scope each rule, e.g.
                     Rule::exists('whatsapp_templates', 'id')
                         ->where('organization_id', app(TenantContext::class)->id())
                   or validate against a tenant-scoped query result. Add a test that a
                   foreign id is rejected.
Status:            OPEN
```

### M‑3 — Webhook Events viewer is not tenant‑scoped

```text
Severity:          Medium  (→ leaks customer PII across tenants if TENANT_MODE=multi)
Affected files:    app/Http/Controllers/Admin/WebhookEventController.php (index + show)
                   app/Models/WebhookEvent.php (intentionally NOT BelongsToOrganization)
Issue:             index() returns the latest 500 webhook_events across ALL organizations
                   to any user holding `audit.view`; show() route-model-binds any
                   WebhookEvent by ULID with no ownership check. Payloads contain
                   inbound customer message text + customer phone numbers.
Current impact:    LOW — single-tenant; `audit.view` is only in the org_admin role.
Future impact:     MEDIUM/HIGH multi-tenant — one org's admin reads another org's
                   customer conversations.
Recommended Fix:   Filter to `where('organization_id', $tenantId)->orWhereNull('organization_id')`
                   in index(); add an ownership/`organization_id` check (or a
                   WebhookEventPolicy) in show(); or gate the whole viewer behind
                   `super-admin` since it is a platform-diagnostic tool.
Status:            OPEN
```

### L‑1 — CSV formula injection in exports

```text
Severity:          Low
Affected files:    app/Http/Controllers/Contacts/ContactExportController.php:34
                   app/Http/Controllers/Campaigns/CampaignReportController.php:56
                   app/Services/Contacts/ContactImportService.php:85 (error-rows CSV)
Issue:             Raw fputcsv() writes user-controlled contact `name` (and error text)
                   with no formula escaping. A contact named `=HYPERLINK("http://x","a")`
                   or `+cmd|'/c calc'!A1` executes when the CSV is opened in Excel/Sheets.
Recommended Fix:   Use `League\Csv\EscapeFormula` (league/csv is already a dependency):
                     $writer->addFormatter(new EscapeFormula());
                   or prefix cells starting with = + - @ TAB CR with a single quote.
Status:            OPEN
```

### L‑2 — Production configuration checklist (pre‑deploy gate)

```text
Severity:          Low (Info) — but a hard release gate
Current .env (local, correct for dev):
  APP_ENV=local            → MUST be `production`
  APP_DEBUG=true           → MUST be `false`  (stack traces / env dump otherwise)
  SESSION_SECURE_COOKIE=false → MUST be `true` (HTTPS)
  TRUSTED_PROXIES=*        → see M-1
  WABA_WEBHOOK_REQUIRE_SIGNATURE=true → keep true
Already handled in code:  AppServiceProvider forces https + HIBP password check +
                          Model::shouldBeStrict only when !isProduction.
Also verify at deploy:    `php artisan config:cache`, `route:cache`, `view:cache`;
                          storage/ + bootstrap/cache/ writable only by the app user;
                          web root = public/ only (no app/ , .env, storage/ served);
                          Nginx denies dotfiles; queue worker + scheduler under Supervisor.
Status:            OPEN (deployment task)
```

### L‑3 — Auth endpoints missing defense‑in‑depth throttle

```text
Severity:          Low
Affected file:     routes/auth.php
Issue:             `password.store` (reset submission) and `password.update`
                   (change password while logged in) have no `throttle` middleware.
                   Reset tokens are 64-char random (not brute-forceable) so impact is
                   low; password.update requires a valid session + current_password.
Recommended Fix:   Add `->middleware('throttle:6,1')` to both. Consider
                   `Auth::logoutOtherDevices()` on password change.
Status:            OPEN
```

### L‑4 — CORS not explicitly configured

```text
Severity:          Low / Info
Issue:             config/cors.php is not published; framework default is
                   allowed_origins=['*'] on `api/*`. Only `/api/webhooks/whatsapp`
                   exists there (server-to-server, no cookies) so it is not currently
                   exploitable, but any future authenticated `/api` route would inherit
                   a wildcard origin.
Recommended Fix:   `php artisan config:publish cors`; set allowed_origins to the
                   portal's own domain(s); supports_credentials=false; restrict methods.
Status:            OPEN
```

### L‑5 — No `Cache-Control` on authenticated HTML

```text
Severity:          Low
Issue:             SecurityHeaders does not add `Cache-Control: no-store` for
                   authenticated pages. A shared/intermediate proxy could cache
                   dashboard / contacts / messages HTML.
Recommended Fix:   Add `Cache-Control: no-store, private` + `Pragma: no-cache` in
                   SecurityHeaders for non-public routes (skip `/health`, `/up`,
                   signed public pages).
Status:            OPEN
```

### L‑6 — `unsafe-eval` in CSP `script-src` (accepted risk)

```text
Severity:          Info
Issue:             `script-src 'self' 'unsafe-eval'` — required by the standard
                   Alpine.js build. No `'unsafe-inline'` for scripts, and the review
                   found no `<script>` / inline-handler injection sink (all JSON
                   embeds use JSON_HEX_* / @json). Residual risk: an injected string
                   reaching `eval()`/`Function()` would run.
Recommended Fix:   Optional hardening later: Alpine CSP build + drop 'unsafe-eval'.
Status:            ACCEPTED
```

---

## D. Existing security controls (verified present and correct)

| Area | Control |
|---|---|
| **Multi‑tenant isolation** | `OrganizationScope` global scope, **fail‑closed** (`whereRaw('1 = 0')` when no tenant bound). `BelongsToOrganization` trait auto‑fills `organization_id` on create. `EnsureTenantContext` middleware; **runs before `SubstituteBindings`** (priority list fix) so route‑model binding is tenant‑scoped. `BindsTenant` trait on every campaign/webhook queue job. Bulk `Contact::insert` sets `organization_id` explicitly. Multi‑tenant membership check present in `EnsureTenantContext` for `TENANT_MODE=multi`. |
| **Webhook authenticity** | `X-Hub-Signature-256` HMAC‑SHA256 over the **raw body**, constant‑time compare; `require_signature` default true; verify‑token GET handshake constant‑time. Idempotent on `sha256(body)` fingerprint (unique). Returns 200 in ms, all work queued. Proxy/tunnel‑safe (raw‑body HMAC). Bad‑signature attempts recorded (`status=ignored`) not processed. |
| **Secrets** | WABA `access_token` / `app_secret` / `webhook_verify_token` = Eloquent `encrypted` casts; masked accessors (`••••abcd`); never returned in API/UI. `RedactSensitiveProcessor` on the `whatsapp` log channel + `AuditLogger` key redaction strip tokens/secrets/authorization/phone. `.env`, `.env.*` (except example), `_bootstrap/`, `tmp_laravel/`, `csv/`, `*.sqlite` gitignored. New: Meta API error bodies logged only to the redacted `whatsapp` channel. |
| **Authentication** | Breeze, trimmed — **no self‑registration**. Session `regenerate()` on login, `invalidate()` + token rotate on logout (no fixation). Dual‑layer login throttle: `throttle:login` middleware (5/min email+ip, 20/min ip) **and** `LoginRequest::ensureIsNotRateLimited` (5 attempts). Generic reset response (no user enumeration). Password policy 12+ upper/lower/number, `->uncompromised()` (HIBP) in production. `forgot-password` + `confirm-password` throttled 6/min. |
| **Authorization / RBAC** | 7 auto‑discovered Policies (Campaign, Contact, Media, Message, WhatsappBusinessAccount, WhatsappPhoneNumber, WhatsappTemplate). spatie/permission **teams mode** (team = org). `Gate::before` → super‑admin. Every controller action calls `$this->authorize(...)`. All `admin/*` routes `can:org.manage` / `can:audit.view` / `super-admin`. Route‑model binding is tenant‑scoped. `MessagePolicy` 404s (not 403s) cross‑org objects. |
| **Injection** | 100% Eloquent / query builder. Every `selectRaw` / `DB::raw` / `whereRaw` uses a **constant string** — no user interpolation anywhere. DataTables moved fully **client‑side** → no server‑side sort‑column / order‑direction injection surface. Phone search normalises to digits before `LIKE`. |
| **File upload / download** | `MediaLibrary`: real MIME via **finfo** (not filename) + extension allowlist + per‑category size cap + magic‑byte sniff; **rejects** exe/archive/SVG. Server‑generated `media/{orgId}/{ULID}.{ext}` paths → no traversal. `visibility: private`. Download route = `auth` + `tenant` + **valid signature** + scoped binding. Template sample upload (new) validates file + per‑type size cap; goes to Meta's resumable‑upload API with the token in the `Authorization` **header** (not URL). CSV import: `mimes:csv,txt`, 10 MB cap, ULID path, tenant‑scoped `ContactImport`. |
| **SSRF** | `App\Services\WhatsApp\Support\SafeHttpClient` — https‑only, rejects `0/8 10/8 100.64/10 127/8 169.254/16 172.16/12 192.168/16 224/4 …` (incl. `169.254.169.254` cloud metadata), re‑validates **every redirect hop** (max 2), size cap. Built + unit‑tested. *(Not yet wired — the app does not fetch user URLs yet; wire it into inbound‑media download when that phase lands.)* |
| **Security headers** | CSP (`default-src 'self'`, `frame-ancestors 'none'`, `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`, `img-src 'self' data: blob:`, no script `unsafe-inline`), HSTS (https only, 1 yr, includeSubDomains), `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (camera/mic/geo/FLoC off), `Cross-Origin-Opener-Policy: same-origin`, `X-Permitted-Cross-Domain-Policies: none`. |
| **CSRF / sessions** | Laravel VerifyCsrfToken on all web POST/PUT/DELETE; `/api/webhooks/*` correctly exempt (in `api` group, authenticity by HMAC). Turbo submits the hidden `_token`. Session: `encrypt=true`, `http_only=true`, `same_site=lax`, DB driver, 120‑min lifetime. |
| **Rate limiting / abuse** | login, forgot‑password, confirm‑password, `whatsapp-webhook` (600/min/ip). `WhatsAppRateLimiter` token‑bucket (Redis/DB lock) per phone number, adaptive backoff on 429. Index result caps: messages 1 000, contacts 2 000, campaign recipients 2 000 (with "showing latest N" notice). Test‑send max 5 recipients. |
| **Business logic** | `messages.idempotency_key` unique; `sha1(org:campaign:recipient)` for campaigns. Forward‑only message status (rank compare, never regresses past webhook `delivered`/`read`). Campaign state transitions via atomic status `UPDATE` guards + `Cache::lock` on finalize. `SendCampaignMessageJob` `ShouldBeUnique`. MARKETING template to opted‑out contact ⇒ `Skipped`, no send; audience resolver forces `opted_in` for MARKETING. Signed, effectively single‑use unsubscribe. Server owns all state; no client‑settable status/role/owner fields (`$fillable` audited, trusted layers use `forceFill`/`forceCreate`). |
| **Logging / audit** | `AuditLogger` records create/update/delete + connection checks + sensitive WABA actions with actor + redacted context. `webhook_events` append‑only (`$timestamps=false`). Passwords/tokens never logged. New: log timestamps in IST via a channel tap (storage stays UTC). |
| **Dependencies** | `composer audit` → **no advisories**. `npm audit --omit=dev` → **0 vulnerabilities**. `composer.lock` + `package-lock.json` committed. PHPStan level 5 + Pint clean. 138 Pest tests green (incl. tenant‑isolation, webhook auth/idempotency, RBAC, SSRF blocker, secrets‑not‑logged, forward‑only status). |

---

## E. Missing controls / recommendations

| # | Recommendation | Priority |
|---|---|---|
| 1 | Fix **H‑1** (fresh APP_KEY, placeholder in `.env.example`, re‑encrypt, rotate WABA token). | **High** |
| 2 | Scope every `exists:` rule to the tenant (**M‑2**); add a "foreign id rejected" test. | **High before multi‑tenant** |
| 3 | Tenant‑scope or super‑admin‑gate the Webhook Events viewer (**M‑3**). | **High before multi‑tenant** |
| 4 | Pin `TRUSTED_PROXIES` to real proxy CIDRs in production (**M‑1**). | Medium |
| 5 | `League\Csv\EscapeFormula` on all three CSV exports (**L‑1**). | Medium |
| 6 | Production `.env` checklist (**L‑2**) as a deploy gate. | Medium |
| 7 | `throttle:6,1` on `password.store` + `password.update`; `logoutOtherDevices` on password change (**L‑3**). | Low |
| 8 | Publish + restrict `config/cors.php` (**L‑4**); `Cache-Control: no-store` on auth pages (**L‑5**). | Low |
| 9 | Wire `SafeHttpClient` into inbound‑media download when that phase is built; keep the `connect-src` CSP updated when the LLM bot adds `api.anthropic.com`. | Low (future) |
| 10 | Consider MFA/TOTP for `org_admin` / `super_admin` before onboarding real client tenants. | Low (future) |
| 11 | Retention‑prune command for `messages`, `webhook_events`, `bot_messages`, `audit_logs` (Phase 6). | Low (future) |
| 12 | Move to PHPStan level 8 + add `larastan` strict rules incrementally. | Low |

---

## F. Prioritized remediation plan

1. **Before production launch (single‑tenant):**
   - H‑1 (APP_KEY + token rotation)
   - M‑1 (`TRUSTED_PROXIES`), L‑2 (`APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`)
   - L‑1 (CSV escaping) — quick, low‑risk
2. **Before enabling `TENANT_MODE=multi`:**
   - M‑2 (scope all `exists:` rules + tests)
   - M‑3 (webhook viewer scoping)
   - Re‑run this review's IDOR section against two live orgs *(manual/Burp)*
3. **Hardening backlog:** L‑3, L‑4, L‑5, then E‑9…E‑12.

---

## G. Security release recommendation

**CONDITIONAL GO — single‑tenant production.**
Ship once **H‑1**, **M‑1** and the **L‑2** deploy checklist are complete. L‑1 strongly recommended in the same release.

**NO‑GO for `TENANT_MODE=multi`** until **M‑2** and **M‑3** are fixed and IDOR is manually re‑verified across two organizations.

No Critical vulnerabilities were found. The absence of findings in an area of this report does **not** by itself certify that area — see section I.

---

## H. Files / routes / functions reviewed

- **Config:** `bootstrap/app.php` (middleware, trusted proxies, priority list), `config/session.php`, `config/logging.php`, `config/services.php` (whatsapp), `.env` / `.env.example`, `phpstan.neon`, `.gitignore`
- **Middleware:** `SecurityHeaders`, `EnsureTenantContext`, `EnsureSuperAdmin`
- **Tenancy:** `App\Models\Scopes\OrganizationScope`, `App\Models\Concerns\BelongsToOrganization`, `App\Support\TenantContext`, `App\Support\CurrentOrganization`, `App\Jobs\Concerns\BindsTenant`
- **Auth:** `routes/auth.php`, `AuthenticatedSessionController`, `LoginRequest`, `AppServiceProvider` (rate limiters, password policy, Vite attrs)
- **AuthZ:** all `app/Policies/*`, `routes/web.php` (admin group, `can:` gates, signed routes)
- **Webhooks:** `WhatsAppWebhookController`, `WhatsAppWebhookService`, `WebhookSignature`, `ProcessWhatsAppWebhookJob`
- **API/driver:** `MetaCloudApiDriver` (incl. new `uploadTemplateSample`), `MockWhatsAppDriver`, `MetaErrorMapper`, `WhatsAppManager`, `WabaCredentials`, `Support\SafeHttpClient`
- **Input/validation:** `StoreTemplateRequest`, `SendTestMessageRequest`, `UpdateCampaignRequest`, `StoreContactRequest`, `UpdateWabaSettingsRequest`, import/group controller inline validation
- **File handling:** `MediaLibrary`, `MediaController`, `ContactImportController`, `ContactImportService`, `TemplateSubmissionService`, `TemplateComposer`
- **Business logic:** `OutboundMessageService`, `MessageStatusUpdater`, `CampaignLauncher`, `CampaignAudienceResolver`, `SendCampaignMessageJob`, `DispatchCampaignBatchJob`, `FinalizeCampaignJob`, `OptInService`
- **Reporting/export:** `DashboardMetrics`, `ReportsController`, `ContactExportController`, `CampaignReportController`, `WebhookEventController`
- **Views (XSS sinks):** `partials/sidebar*`, `whatsapp/settings/field.blade.php`, `admin/webhook-events/*`, `whatsapp/test-send/create.blade.php`, `whatsapp/messages/show`, `whatsapp/templates/show`, `partials/trend-chart`, `layouts/*`
- **Frontend JS:** `resources/js/*` (turbo-setup, datatables, alerts, template-preview, form-loading, nav-active) — CSP compatibility, no eval sinks on user data
- **Automated:** `composer audit`, `npm audit`, `phpstan` (L5), `pint`, `pest` (138)

---

## I. Requires manual / Burp Suite verification (not covered by static review)

- Live IDOR/BOLA probing across **two real organizations** with `TENANT_MODE=multi` (campaign, contact, media, template, message, webhook‑event objects; ID swapping; HTTP‑method swapping).
- Session/cookie behaviour over **real HTTPS** with `SESSION_SECURE_COOKIE=true` (Secure/HttpOnly/SameSite flags on the wire, logout invalidation, concurrent sessions).
- CSP effectiveness against a **crafted stored payload** rendered in a live browser (contact name, WABA display name, inbound message body, template body from Meta).
- Rate‑limit bypass attempts via `X-Forwarded-For` spoofing with a **misconfigured `TRUSTED_PROXIES`**.
- Webhook signature **rejection** with a genuinely invalid `X-Hub-Signature-256` delivered over the tunnel, and replay of a captured valid delivery.
- Meta **template‑approval / policy** behaviour (out of security scope, but relevant to the election‑campaign template).
- **TLS configuration** of the production host (Hostinger KVM) — cipher suites, protocol versions, HSTS preload, cert chain.
- Signed‑URL **expiry and object‑binding** (`/whatsapp/media/{id}`, `/unsubscribe/{contact}`) tested with tampered signatures and swapped ids over the wire.
- File‑upload **content‑type confusion** with real polyglot files (e.g. a valid‑JPEG‑that‑is‑also‑PHP) against the finfo + magic‑byte checks.

---

## J. Testing limitations

- Static/code review only; no running instance was fuzzed or proxied.
- Single‑tenant deployment — multi‑tenant isolation was reasoned about from the scope/middleware code, **not** exercised with two live tenants.
- No production infrastructure (Hostinger KVM, Nginx, TLS, firewall, backups) existed to review — section 31/32 of the checklist is **deferred to deployment**.
- No mobile app, no payment integration, no WebSocket/broadcast, no Telescope/Horizon — those checklist sections are **N/A** for the current build.
- `composer audit` / `npm audit` reflect advisory databases as of the review date only.
- The prior **GitHub WABA‑token leak** (history rewritten, token **not** rotated per owner decision) is a standing accepted risk recorded in project memory; H‑1 compounds it.

---

*Generated from an authorized internal review. Redact this file's `APP_KEY` reference before sharing outside AH&V.*
