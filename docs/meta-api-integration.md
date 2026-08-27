# Meta WhatsApp Cloud API Integration Plan

All calls go through `App\Services\WhatsApp` and a driver implementing
`WhatsAppDriver`. Only official Graph API endpoints are used. No policy,
approval, or rate-limit circumvention.

## Drivers

| Driver | Use | Behaviour |
|--------|-----|-----------|
| `mock` | local dev, tests, CI | Simulates accept/sent/delivered/read/failed/rate-limited/invalid-number/template-rejected. Emits fake webhook events on a delay via a queued job. |
| `meta_cloud_api` | staging/production | Real Graph API calls with Guzzle, timeouts, ret/backoff, structured logging. |

Selected by `config('services.whatsapp.driver')`, bindable per-request for tests.

## Endpoints used (version from `WABA_API_VERSION`, default `v22.0`)

| Operation | Method | Path |
|-----------|--------|------|
| Send message | POST | `/{phone_number_id}/messages` |
| Upload media (resumable/simple) | POST | `/{phone_number_id}/media` |
| Retrieve media URL | GET | `/{media_id}` |
| Download media | GET | `<returned URL>` (bearer auth, SSRF-guarded) |
| List message templates | GET | `/{waba_id}/message_templates` |
| Create template | POST | `/{waba_id}/message_templates` |
| Delete template | DELETE | `/{waba_id}/message_templates?name=` |
| Phone number info | GET | `/{phone_number_id}?fields=verified_name,quality_rating,...` |
| List phone numbers | GET | `/{waba_id}/phone_numbers` |
| Subscribed apps (webhook check) | GET | `/{waba_id}/subscribed_apps` |
| Token debug (best effort) | GET | `/debug_token` |

If a field/endpoint is not documented for the configured version, the feature is
surfaced as "not available" rather than guessed.

## Send payload construction

Built by typed message objects → `toGraphPayload()`. Example (template):

```json
{
  "messaging_product": "whatsapp",
  "to": "<E.164 without +>",
  "type": "template",
  "template": {
    "name": "order_dispatched_update",
    "language": { "code": "en" },
    "components": [
      { "type": "header", "parameters": [{ "type": "image", "image": { "id": "<media_id>" }}] },
      { "type": "body", "parameters": [
        { "type": "text", "text": "Asha" },
        { "type": "text", "text": "ORD-1042" }
      ]}
    ]
  }
}
```

Variable values come from `campaign.variable_map` rendered per recipient; every
`{{n}}` placeholder in the template body must map to a value or validation fails
before launch.

## Connection validation (WABA settings screen)

| Check | Call | Pass condition |
|-------|------|----------------|
| Test connection | GET `/{phone_number_id}` | HTTP 200 |
| Validate phone number | GET `/{phone_number_id}?fields=display_phone_number,verified_name,quality_rating` | field present |
| Validate WABA | GET `/{waba_id}?fields=name,timezone_id` | HTTP 200 |
| API permissions | GET `/{waba_id}/message_templates?limit=1` | not 403 |
| Token expiry | GET `/debug_token` | `data.is_valid == true`; store `expires_at` |
| Webhook config | GET `/{waba_id}/subscribed_apps` | app listed |

Results stored on `whatsapp_business_accounts` (`connection_status`,
`token_status`, `last_error`) and shown as badges. Never returns the token.

## Template sync

`SyncTemplatesJob` (per WABA), paginates `message_templates`, upserts into
`whatsapp_templates` keyed by (waba, name, language). Stores `raw_meta`,
`status` verbatim, `rejection_reason`. Runs: on demand ("Refresh"), after a
create submission, and hourly via scheduler. Unknown status strings are stored
as-is and shown as `UNKNOWN`-styled.

## Error normalisation

`MetaErrorMapper::classify(array $error): NormalizedError`

| Meta signal | Category | Retry? |
|-------------|----------|--------|
| HTTP 429, code 4/80007/130429, `Retry-After` header | `rate_limited` | yes, honour Retry-After / backoff |
| HTTP 5xx, code 1/2/131000 (internal), timeouts | `temporary` | yes, exponential backoff |
| code 190 / 401 / 10 / 200-series perms | `auth` | no — alert admin |
| code 131026 (undeliverable), 131047/131051 (re-engagement/unsupported), 131052 (media download), invalid `to` | `invalid_recipient` | no |
| code 132xxx (template) | `template` | no — mark recipient skipped, flag template |
| code 131053 (media upload), 131057 | `media` | no |
| anything else | `unknown` | single retry then fail |

User-facing message is a mapped friendly string; `raw_meta` kept for admins only.

## Rate limiting & throttling

`WhatsAppRateLimiter` — token-bucket in Redis (or DB lock locally), keyed by
`phone_number_id`. Starts conservative, adapts:

- On success: allow configured campaign delay (0–custom) but never below a safe
  floor.
- On `rate_limited`: multiply the interval, set a cool-off until `Retry-After`,
  record a `rate_limit` event, requeue with backoff.
- Respects `messaging_limit_tier` when known (soft cap on in-flight per day).

The campaign "sending delay" is a UX convenience layered **on top of** the limiter;
it can only slow sending, never speed it past the limiter.

## Webhooks

- `GET /api/webhooks/whatsapp` — verify: compare `hub.verify_token` to the
  configured token (constant-time), echo `hub.challenge`.
- `POST /api/webhooks/whatsapp` — verify `X-Hub-Signature-256` HMAC-SHA256 over
  the raw body using the app secret (per WABA; fall back to global). Reject on
  mismatch with 403. Then: compute fingerprint, insert `webhook_events`
  (unique fingerprint → duplicate short-circuits to 200), dispatch
  `ProcessWebhookEventJob`, return 200 within milliseconds.
- Processing job: parse `entry[].changes[]`; handle `messages` (inbound),
  `statuses` (delivery), `message_template_status_update`, `phone_number_*`
  quality updates. Each status appends a `message_status_events` row and updates
  the `messages` row only forward (`pending<sent<delivered<read`, `failed`
  terminal).

## Outbound safety gates (checked in the send job, in order)

1. Global kill switch `WHATSAPP_SENDING_ENABLED`.
2. Organization not suspended; WABA `is_active`; phone number not disabled.
3. Campaign status is `processing` (not paused/cancelled).
4. Recipient status is `pending`/`queued` and has no successful `message`.
5. (Phase 3+) contact not `opted_out` for MARKETING templates.
6. Idempotency key not already used by a non-failed message.
7. Rate limiter permits (else requeue).
