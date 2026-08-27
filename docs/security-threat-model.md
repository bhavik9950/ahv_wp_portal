# Security Threat Model

Method: STRIDE per trust boundary, plus the explicit checklist from `plan.md`
§29, §36. "Status" = where it is enforced in this build.

## Trust boundaries

1. Browser ↔ Laravel (authenticated users, multiple roles, multiple tenants)
2. Meta ↔ Webhook endpoint (unauthenticated internet, forgeable)
3. Laravel ↔ Meta Graph API (outbound, carries secrets)
4. Laravel ↔ Object storage / user-supplied media URLs (SSRF surface)
5. Operator ↔ `.env` / secrets manager

## Assets

Per-org WABA access tokens & app secrets; contact PII & phone numbers; message
content; audit trail; campaign data; user credentials/sessions.

## Threats & controls

### T1 — Cross-tenant data access (IDOR / broken access control) — **critical**
- ULID PKs (non-enumerable).
- `BelongsToOrganization` global scope on every tenant model; queries are
  auto-filtered by `current_organization_id`.
- Route-model binding + `EnsureTenantContext` middleware rejects any bound model
  whose `organization_id` ≠ active org (404, not 403 — no existence leak).
- Every controller action gated by a Policy; policies re-check org ownership.
- `super_admin` bypass is explicit and audited.
- Tests: `tests/Feature/TenantIsolationTest` — user of Org A gets 404 on Org B
  campaign / template / contact / message / WABA / report, by id manipulation.

### T2 — Privilege escalation (role tampering)
- Roles are server-side (spatie, team-scoped). No role/permission input trusted
  from the client.
- Only `org_admin`+ can manage members; cannot grant `super_admin`.
- `can:` middleware + Policy on every mutating route. Gate::before for super admin.
- Mass-assignment: all models use `$fillable` allowlists; `organization_id`,
  `is_super_admin`, `status`, `created_by` never fillable — set server-side.

### T3 — Secret leakage
- Tokens/app secrets: `encrypted` cast (AES-256-GCM via `APP_KEY`).
- API Resources never include token fields; a masked accessor
  (`••••••••abcd`) is the only representation returned.
- Logging: dedicated `whatsapp` channel with a processor that strips
  `Authorization`, `access_token`, `app_secret`, `hub.verify_token`; phone
  numbers masked to last 4 in app logs.
- `api_logs` / `audit_logs` store ids and categories only (enforced by a DTO,
  not free-form arrays).
- `.gitignore` covers `.env`, `_bootstrap/.env.original`, storage logs. Repo
  ships `.env.example` with blank secrets. A pre-commit hint + a test
  (`SecretsNeverLoggedTest`) assert no token substring in log output.
- **The token currently in `.env` is treated as compromised — deployment runbook
  step 1 is rotate/revoke in Meta.**

### T4 — Webhook forgery / replay
- `X-Hub-Signature-256` HMAC verified against raw body with per-WABA app secret,
  constant-time compare; invalid → 403, logged, `signature_valid=false` stored
  for forensics but not processed.
- Verify-token compared constant-time on GET.
- Replay: `event_fingerprint` UNIQUE ⇒ duplicates are no-ops; status events are
  monotonic (a late "sent" after "read" is ignored).
- Endpoint has a high but present rate limit; body size capped (e.g. 512 KB).

### T5 — SSRF via media URLs
- Users never provide raw URLs for server fetch in the normal flow (media is
  uploaded, then pushed to Meta). Where a URL is fetched (Meta media download):
  - scheme allowlist `https` only;
  - host must resolve to a public IP — block `127.0.0.0/8`, `10/8`,
    `172.16/12`, `192.168/16`, `169.254/16`, `::1`, fc00::/7, and cloud
    metadata `169.254.169.254`;
  - re-validate IP after each redirect (max 2), same rules;
  - connect + read timeouts, max download size, MIME allowlist.
- Implemented as `SafeHttpClient` wrapper; unit tests with a stub resolver.

### T6 — Malicious file upload
- Validate MIME by content (`finfo`), extension allowlist, magic-byte sniff,
  size limit per type (Meta's limits), reject archives/executables/SVG.
- Store outside webroot / private disk; served via signed temporary URLs only.
- Randomised stored filename; original name kept as metadata only.
- Image re-encode optional; documents not executed.

### T7 — Injection (SQLi / XSS / command / path traversal)
- Eloquent / query builder only; no string-interpolated SQL. Raw expressions
  reviewed and parameter-bound.
- Blade auto-escaping; no `{!! !!}` on user data; CSP header.
- No `exec`/`proc_open` on user input. CSV import uses `league/csv`, not shell.
- File paths from IDs, never from user strings; `basename()` + disk API.

### T8 — Auth attacks (brute force, session fixation, CSRF)
- Laravel session auth, `web` CSRF middleware on all state changes.
- Login throttle (`throttle:login` 5/min/ip+email), generic error messages.
- Session regenerated on login; idle + absolute session lifetime; `secure`,
  `http_only`, `SameSite=Lax` cookies; `SESSION_ENCRYPT=true`.
- Optional 2FA scaffold (Fortify-style) — deferred but columns present.

### T9 — API abuse / rate-limit bypass (internal)
- Per-user + per-org throttling on expensive endpoints (sync, test send,
  import, export).
- Campaign launch requires explicit confirmation token; test-send capped at N
  numbers; cannot launch a campaign whose template isn't `APPROVED`.

### T10 — Queue duplication / race (double send)
- Covered in queue-architecture.md: atomic recipient claim, pre-insert
  idempotency row, unique constraints. Tests:
  `DuplicateJobDoesNotDoubleSendTest`, `ConcurrentClaimTest`.

### T11 — Denial of wallet / runaway sending
- Global `WHATSAPP_SENDING_ENABLED` kill switch checked in every send job.
- Per-org "disable sending", "pause all campaigns", "revoke integration" admin
  controls (§33).
- Adaptive rate limiter caps throughput; Meta 429s trigger cool-off.
- Max recipients per campaign configurable; import size caps.

### T12 — Compliance / policy misuse
- Opt-out ledger enforced by the send pipeline for MARKETING templates (Phase 3).
- No feature to bypass template approval, scrape numbers, or import without a
  consent source field. Bulk send always requires an approved template.
- Audit log on every send-affecting action.

### T13 — Insecure transport / headers
- HTTPS enforced (`URL::forceScheme`, HSTS in Nginx).
- Security headers middleware: `X-Content-Type-Options`, `X-Frame-Options=DENY`,
  `Referrer-Policy`, `Permissions-Policy`, CSP (self + needed origins).
- CORS: closed by default; API origins allowlisted if/when an SPA is added.

## Residual risk / accepted for Phases 1–2

- Real Meta rate-limit behaviour can only be tuned against a live account
  (mock approximates it).
- Load testing (§37) deferred to Phase 6; design is queue-based to support it.
- 2FA present as scaffold only.
- Broadcasting/real-time deferred; polling fallback used.
