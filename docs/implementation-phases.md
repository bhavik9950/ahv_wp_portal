# Implementation Phases

Mirrors `plan.md` §55. Each phase ends with: `php artisan test` green,
`pint`/`phpstan` clean, docs updated.

## Phase 1 — Foundation  (this delivery)

- Laravel 12 scaffold, Pint, Larastan, Pest.
- Packages: `laravel/sanctum`, `spatie/laravel-permission`, `league/csv`,
  `laravel/horizon` (prod), `pestphp/pest`.
- Config: `config/services.php` `whatsapp` block; `config/logging.php` `whatsapp`
  channel; env-switchable DB (sqlite/mysql) + queue (database/redis).
- Migrations: **all** tables from erd.md (so later phases add features, not
  schema churn), with indexes and FKs.
- Models + casts + `$fillable` + relationships + `BelongsToOrganization` trait
  and global scope.
- Tenancy: `EnsureTenantContext` middleware, org switcher, `current_organization_id`
  in session.
- RBAC: spatie teams mode, `RoleSeeder`, `Gate::before` super admin, base
  Policies.
- Auth: login/logout/register(admin-invite)/password reset, throttling, secure
  session config, security-headers middleware.
- WABA config module: CRUD (encrypted fields), masked resource, connection
  validators (Test Connection / Validate Phone / Validate WABA / Permissions /
  Token / Webhook) — working against the **mock** driver and real driver.
- Meta service layer: `WhatsAppDriver` contract, `MockWhatsAppDriver`,
  `MetaCloudApiDriver`, `WhatsAppManager`, `WhatsAppRateLimiter`,
  `MetaErrorMapper`, typed message DTOs, `SafeHttpClient`.
- Audit log service + `api_logs` writer.
- Admin: emergency controls (kill switch, disable org, pause all), health
  monitor page (DB/queue/redis/driver/token/webhook freshness).
- UI shell: Blade + Livewire, nav from §41, dashboard placeholder.
- Tests: tenant isolation, RBAC, WABA authz, secrets-not-logged, SSRF blocker,
  mock driver behaviours, connection validators.

## Phase 2 — WhatsApp Core  (this delivery)

- Send test message (single & template) via `whatsapp-high` queue, with all
  outbound safety gates and idempotency.
- Template module: list/search/filter, builder (header/body/footer/buttons/
  variables/media header), client+server validation, preview, submit to Meta,
  refresh status, view rejection reason, delete where permitted.
- `SyncTemplatesJob` + hourly schedule + on-demand refresh; raw status stored;
  unknown states tolerated.
- Webhook endpoint: GET verify + POST receive, signature verification,
  `webhook_events` persistence, `ProcessWebhookEventJob`, idempotency.
- Message status tracking: `messages` + append-only `message_status_events`,
  forward-only transitions, conversation/message viewer with status timeline.
- Mock driver emits simulated webhook callbacks so the full loop is testable
  offline.
- Tests: webhook auth + idempotency, duplicate status handling, invalid template
  cannot launch, retry/backoff classification, kill switch blocks sends,
  unauthorized user cannot submit templates.

## Phase 3 — Contacts

Contacts/groups CRUD, async CSV import with preview + invalid-row export,
duplicate/invalid-number detection, E.164 normalisation, opt-in/opt-out ledger,
unsubscribe handling, send pipeline consults opt-out for MARKETING.

## Phase 4 — Campaigns

Wizard (§42), audience selection, variable mapping, media, preview, test send,
scheduling with org timezone, batched dispatch, adaptive throttling, pause/
resume/cancel, retry strategy, per-recipient records.

## Phase 5 — Reporting

Dashboard aggregates + date filters + trend charts, per-campaign report with
recipient detail, delivery/read/failure metrics, CSV export (tenant-safe),
optional broadcasting for live tiles (polling fallback).

## Phase 6 — Security & Production

Retention prune commands wired to org settings, full security test sweep (§36),
k6/Artillery load scripts for 10k/50k/100k with a results template, Horizon
monitoring + alerts, `/health`, deployment docs (Nginx/PHP-FPM/Supervisor/
Horizon/Scheduler/SSL), monitoring/alerting runbook.

## Acceptance tracking

`plan.md` §57 items are tracked as a checklist in `docs/acceptance.md`, updated
at the end of each phase.
