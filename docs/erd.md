# Database / ERD Proposal

Primary keys: **ULID** (`char(26)`) for all business tables, keeping them
sortable and non-enumerable (IDOR mitigation). Laravel default `bigint` is kept
only for framework tables (`jobs`, `failed_jobs`, `cache`, `sessions`).

All money/time stored UTC. Organization timezone applied at presentation only.

## Core identity & tenancy

### users
`id ULID, name, email UNIQUE, email_verified_at, password, is_super_admin BOOL default false, two_factor_*, remember_token, timestamps`

### organizations
`id ULID, name, slug UNIQUE, timezone default 'UTC', status ENUM(active,suspended) default active, settings JSON, timestamps, soft deletes`

### organization_user  (pivot)
`id ULID, organization_id FK, user_id FK, timestamps` — UNIQUE(organization_id, user_id)

### roles / permissions / model_has_roles / role_has_permissions
spatie/laravel-permission with `teams = true`; `team_foreign_key = organization_id`.
Seeded roles: `super_admin` (global), `org_admin`, `campaign_manager`,
`support_agent`, `viewer`.

## WhatsApp configuration

### whatsapp_business_accounts
```
id ULID
organization_id FK  (INDEX)
name
meta_business_account_id            nullable
waba_id                             the WhatsApp Business Account ID
app_id                              nullable
access_token          TEXT  (encrypted cast)
app_secret            TEXT  (encrypted cast, nullable)
webhook_verify_token  TEXT  (encrypted cast, nullable)
api_version           nullable   -- overrides global default
default_country_code  nullable
token_last_checked_at nullable
token_status ENUM(unknown,valid,invalid,expired) default unknown
connection_status ENUM(unknown,connected,error) default unknown
last_error JSON nullable
is_active BOOL default true
timestamps, soft deletes
```
UNIQUE(organization_id, waba_id)

### whatsapp_phone_numbers
```
id ULID
organization_id FK (INDEX)
whatsapp_business_account_id FK (INDEX)
phone_number_id         -- Meta Phone Number ID
display_phone_number
verified_name           nullable
quality_rating          nullable   -- GREEN/YELLOW/RED as reported
messaging_limit_tier    nullable
status ENUM(unknown,available,error,disabled) default unknown
is_default BOOL default false
last_synced_at nullable
timestamps
```
UNIQUE(whatsapp_business_account_id, phone_number_id)

## Templates

### whatsapp_templates
```
id ULID
organization_id FK (INDEX)
whatsapp_business_account_id FK (INDEX)
name                     (INDEX)
language                 e.g. en, en_US
category                 MARKETING | UTILITY | AUTHENTICATION (stored as string, not enum-locked)
status                   PENDING|APPROVED|REJECTED|PAUSED|DISABLED|UNKNOWN (string, not enum-locked)
meta_template_id         nullable
components JSON           -- normalized builder representation
raw_meta JSON             -- last raw Meta payload
rejection_reason         nullable TEXT
quality_score            nullable
last_synced_at           nullable
created_by FK users nullable
timestamps, soft deletes
```
UNIQUE(whatsapp_business_account_id, name, language)

## Messaging

### messages
```
id ULID
organization_id FK (INDEX)
whatsapp_phone_number_id FK (INDEX)
campaign_id FK nullable (INDEX)
campaign_recipient_id FK nullable
contact_id FK nullable (INDEX)
direction ENUM(outbound,inbound) default outbound
to_phone            E.164, stored; hashed column to_phone_hash for lookup
type ENUM(text,template,image,video,document,audio,location,interactive)
template_id FK nullable
payload JSON              -- rendered message sent to Meta (no secrets)
wamid                     nullable UNIQUE (INDEX)   -- Meta message id
idempotency_key           UNIQUE (INDEX)            -- org+campaign+recipient or org+uuid
status ENUM(pending,queued,processing,sent,delivered,read,failed,cancelled,skipped) default pending  (INDEX)
error_code               nullable
error_category           nullable
error_message            nullable
sent_at, delivered_at, read_at, failed_at   nullable
created_by FK users nullable
timestamps
```
Composite indexes: (organization_id, status, created_at), (campaign_id, status).

### message_status_events   (append-only)
```
id ULID
organization_id FK (INDEX)
message_id FK (INDEX)
wamid nullable
status                     -- raw status string from webhook or internal
error_code nullable
error_title nullable
error_message nullable
raw_reference  FK webhook_events nullable
occurred_at                -- timestamp from Meta if present, else now
created_at
```
No `updated_at`; rows are never mutated.

## Media

### media
```
id ULID
organization_id FK (INDEX)
disk
path
original_name
mime_type
size_bytes
checksum_sha256
meta_media_id            nullable   -- id returned by Meta upload
meta_media_expires_at    nullable
uploaded_by FK users
timestamps
```

## Webhooks

### webhook_events
```
id ULID
organization_id FK nullable (INDEX)   -- resolved after parsing
source default 'meta'
event_fingerprint  UNIQUE (INDEX)     -- sha256 of raw body (+ signature)
signature_valid BOOL
payload JSON
headers JSON                          -- minus Authorization
status ENUM(received,processing,processed,failed,ignored) default received  (INDEX)
processed_at nullable
error nullable
received_at
```

## Observability

### api_logs
```
id ULID
organization_id FK nullable (INDEX)
user_id FK nullable
request_id (INDEX)
service          -- e.g. whatsapp
operation        -- sendTemplate, fetchTemplates...
endpoint
http_status nullable
error_category nullable
duration_ms
context JSON      -- ids only, never secrets/tokens/headers
created_at
```

### audit_logs
```
id ULID
organization_id FK nullable (INDEX)
user_id FK nullable (INDEX)
action (INDEX)          -- login, waba.updated, template.submitted...
auditable_type nullable
auditable_id nullable
result ENUM(success,failure)
ip
user_agent
metadata JSON            -- no secrets
created_at
```

## Contacts / opt-in  (schema created in Phase 1 migrations, feature work Phase 3)

### contacts
`id ULID, organization_id FK, name, country_code, phone_e164 (INDEX), phone_hash (INDEX), email nullable, custom_fields JSON, opt_in_status ENUM(unknown,opted_in,opted_out) default unknown, opted_in_at, opt_in_source, opted_out_at, last_message_at, timestamps, soft deletes` — UNIQUE(organization_id, phone_e164)

### contact_groups
`id ULID, organization_id FK, name, description, timestamps` — UNIQUE(organization_id, name)

### contact_group_contact (pivot)
`id ULID, contact_group_id FK, contact_id FK` — UNIQUE pair

### opt_in_records  (append-only ledger)
`id ULID, organization_id FK, contact_id FK nullable, phone_e164 (INDEX), status ENUM(opt_in,opt_out), source, campaign_id nullable, reference nullable, note nullable, recorded_by FK users nullable, created_at`

## Campaigns  (schema Phase 1, feature work Phase 4)

### campaigns
`id ULID, organization_id FK (INDEX), name, status ENUM(draft,scheduled,processing,paused,completed,cancelled,failed) default draft (INDEX), whatsapp_phone_number_id FK, template_id FK, variable_map JSON, media_id FK nullable, audience_filter JSON, send_delay_seconds INT nullable, scheduled_at nullable (INDEX), timezone, started_at, finished_at, totals JSON, created_by FK users, timestamps`

### campaign_recipients
`id ULID, organization_id FK (INDEX), campaign_id FK (INDEX), contact_id FK nullable, phone_e164, rendered_variables JSON, status ENUM(pending,queued,sent,delivered,read,failed,skipped,opted_out) default pending (INDEX), message_id FK nullable, skip_reason nullable, attempts INT default 0, last_attempt_at, timestamps` — UNIQUE(campaign_id, phone_e164)

### scheduled_messages
`id ULID, organization_id FK, whatsapp_phone_number_id FK, contact_id FK nullable, to_phone, type, payload JSON, template_id FK nullable, media_id FK nullable, send_at (INDEX), status ENUM(pending,queued,sent,cancelled,failed) default pending, message_id FK nullable, created_by FK users, timestamps`

## Retention (Phase 6, columns ready now)

`organizations.settings` holds `retention_days` for messages / webhook_events /
audit_logs / campaigns. A scheduled prune command respects these; nulls = keep.
