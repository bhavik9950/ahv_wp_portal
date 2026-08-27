# Queue Architecture

Local: `QUEUE_CONNECTION=database`. Production: `redis` + Horizon.

## Queues

| Queue | Purpose | Prod workers | Timeout | Tries |
|-------|---------|--------------|---------|-------|
| `whatsapp-high` | connection tests, single/test sends, admin actions | 2 | 60s | 3 |
| `whatsapp-send` | campaign per-recipient sends | scale (4–20) | 60s | 5 (custom backoff) |
| `whatsapp-webhook` | inbound webhook processing | 3–6 | 30s | 5 |
| `whatsapp-media` | media upload/download, CSV import chunks | 2 | 300s | 3 |
| `whatsapp-reports` | report aggregation, CSV export | 1–2 | 600s | 2 |
| `default` | mail, misc | 1 | 60s | 3 |

Horizon `balance=auto`. Each queue independently scalable via supervisor group
counts.

## Campaign send pipeline

```
CampaignScheduler (every minute)
  └─ finds campaigns where status=scheduled AND scheduled_at<=now
       └─ marks status=processing, dispatches DispatchCampaignBatchJob(cursor)

DispatchCampaignBatchJob            [whatsapp-high]
  - re-checks campaign status == processing (else stop, no requeue)
  - pulls next N (e.g. 500) campaign_recipients where status=pending
  - for each: SendCampaignMessageJob dispatched to whatsapp-send
      with delay = index * effective_delay (capped)
  - if more remain: re-dispatch DispatchCampaignBatchJob(next cursor)
      with a small delay so pause takes effect between batches
  - if none remain and no in-flight: FinalizeCampaignJob

SendCampaignMessageJob(recipientId) [whatsapp-send]
  - lock recipient row (SELECT ... FOR UPDATE / atomic status compare-and-set
    pending|queued -> processing); if not acquired -> return (another worker has it)
  - run outbound safety gates (see meta-api-integration.md)
  - rateLimiter->acquire(phoneNumberId) or release+retry with backoff
  - create messages row with idempotency_key (unique) BEFORE the API call;
    if insert violates unique -> load existing, skip if already sent
  - driver->send(...)
      success: message.status=sent, wamid stored, recipient.status=sent
      rate_limited: recipient back to pending, release lock, $this->release(backoff)
      temporary: throw -> Laravel retry w/ backoff [5s,30s,120s,600s]
      permanent/*: message.status=failed, recipient.status=failed|skipped, no retry
  - always: rateLimiter feedback, api_logs row

FinalizeCampaignJob
  - recompute totals, set status=completed (or failed if 100% failed)
```

## Idempotency

- `messages.idempotency_key` UNIQUE. Campaign key = `sha1(org:campaign:recipient)`.
  Ad-hoc/test key = `sha1(org:uuid)`.
- Insert the `messages` row (status `processing`) **before** calling Meta. A retry
  of the same job hits the unique constraint, loads the row, and:
  - if `wamid` present or status ∈ {sent,delivered,read} → treat as done.
  - if status `failed` and error was permanent → done (no resend).
  - if status `processing`/`pending` and older than the job timeout → allowed to
    retry the API call (previous attempt died mid-flight).
- Webhook idempotency: `webhook_events.event_fingerprint` UNIQUE; duplicate POST
  returns 200 without re-processing. `message_status_events` additionally
  de-dupes on `(message_id, status, occurred_at)`.

## Race conditions

- Recipient claim: atomic `UPDATE campaign_recipients SET status='processing'
  WHERE id=? AND status IN ('pending','queued')` — 0 rows affected ⇒ another
  worker owns it, job exits cleanly.
- Campaign state transitions via a single `UPDATE ... WHERE status=?` guard.
- Redis `Cache::lock("campaign:{id}:finalize", 10)` around FinalizeCampaignJob.
- Scheduler uses `->withoutOverlapping()`.

## Pause / resume / cancel

- **Pause**: `UPDATE campaigns SET status='paused' WHERE id=? AND status='processing'`.
  `DispatchCampaignBatchJob` checks status at the top of every batch and stops
  enqueuing. In-flight `SendCampaignMessageJob`s check status in the safety gates
  and, if paused, put the recipient back to `pending` and exit.
- **Resume**: `status='processing'`, dispatch a fresh `DispatchCampaignBatchJob`.
  Only `pending` recipients are picked up; `sent`/`failed` are never revisited.
- **Cancel**: `status='cancelled'`; remaining `pending` recipients set to
  `skipped` (reason `campaign_cancelled`) by `FinalizeCampaignJob`.

## Failed jobs

`failed_jobs` table + Horizon. `whatsapp-send` permanent failures do not land in
`failed_jobs` (handled inline → recipient `failed`); only unexpected exceptions
do. A daily digest reports `failed_jobs` count and top error categories.

## Scheduler (`routes/console.php` / `bootstrap/app.php`)

| Command | Cadence |
|---------|---------|
| `campaigns:dispatch-due` | everyMinute, withoutOverlapping |
| `scheduled-messages:dispatch-due` | everyMinute |
| `whatsapp:sync-templates` | hourly |
| `whatsapp:refresh-health` | everyFiveMinutes |
| `whatsapp:check-tokens` | daily |
| `whatsapp:prune` (retention) | daily |
| `horizon:snapshot` | everyFiveMinutes |
