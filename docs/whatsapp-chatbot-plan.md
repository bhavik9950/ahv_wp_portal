# WhatsApp AI Auto-Reply Bot — Implementation Plan

Status: **planned, not started.** Picked up after the current focus project.
Continues `plan.md` / `docs/implementation-phases.md` (this is "Phase 7"+).

Each stage ends the same way as every other phase: `php artisan test` green,
`pint` + `phpstan` clean, this doc + `docs/acceptance.md` updated.

---

## 1. Objective & scope

When a customer messages the AH&V WhatsApp number, an AI assistant replies
automatically — grounded in what AH&V does (software dev, social media
management, WordPress, Android/iOS, SaaS, CRM, SEO) — inside Meta's rules.

In scope:

- Auto-reply to inbound **text**, **image**, and **voice** messages.
- A knowledge base the bot answers from (no hallucinated prices / timelines).
- Human handoff (bot steps aside when a person is needed).
- Full portal control: on/off, persona, KB, transcript review, escalations.

Out of scope (for now): outbound bot-initiated conversations, multi-language TTS
replies (text replies only in Stage 1–5), payments/booking integrations.

Hard rules (unchanged from `plan.md`): official Cloud API only; no policy or
rate-limit circumvention; secrets from env; strict tenant isolation; the bot
must never send unsolicited marketing.

---

## 2. Prerequisites

| Need | Notes |
|---|---|
| **Public webhook URL** | `https://wp-ahv.ahvsoftware.com/api/webhooks/whatsapp` — the endpoint is already built (`WhatsAppWebhookController`). Add the callback URL + subscribe the **`messages`** field in Meta ▸ App ▸ WhatsApp ▸ Configuration. |
| `WABA_APP_SECRET` set | Already in `.env`. Required — POST webhooks are rejected without a valid `X-Hub-Signature-256`. |
| Deployment host | Hostinger KVM 4/8. Needs: PHP 8.3, MySQL 8, Redis, Nginx, `supervisor`, **`ffmpeg`** (voice transcode), and outbound HTTPS to `api.anthropic.com` + the ASR provider. |
| Queue workers running | `whatsapp-webhook` + a new `bot` queue via Horizon/Supervisor. |
| `ANTHROPIC_API_KEY` | New. From console.anthropic.com. |
| ASR provider key (Stage 5) | Groq Whisper / OpenAI Whisper, **or** self-hosted `whisper.cpp` on the KVM (no key). |

---

## 3. Where it plugs into the existing code

Inbound messages are already parsed and stored:

`ProcessWhatsAppWebhookJob::handleInboundMessages()` → saves a `Message`
(`direction=inbound`, `type`, raw `payload`) and runs the STOP-keyword check.

**Hook point:** right after that `->save()`, dispatch `HandleInboundMessageJob`
onto the `bot` queue with the message id. Everything new hangs off that one job.
No change to webhook parsing, signature checks, or idempotency.

```
Meta ──POST──▶ WhatsAppWebhookController
                   │ (signature verify, fingerprint, 200 in ms)
                   ▼
          ProcessWhatsAppWebhookJob  (whatsapp-webhook queue)
                   │  stores inbound Message
                   ▼
          HandleInboundMessageJob   ◀── NEW  (bot queue)
                   ├─ bot enabled for this number?  (bot_settings)
                   ├─ conversation in human mode?   (conversation_states) → stop
                   ├─ inside 24h service window?     → if not, stop (or send template)
                   ├─ media? → resolve + download via MetaCloudApiDriver
                   │     image → keep bytes for the model
                   │     audio → TranscribeAudio → text
                   ├─ build context: system + KB snippets + last N turns + new msg
                   ├─ ConversationalAgent->reply()  → Anthropic API
                   │     tools: search_kb, create_lead, escalate_to_human, book_call
                   ├─ escalate? → conversation_states = human, notify team, stop
                   └─ OutboundMessageService->sendText(...)   (existing)
```

---

## 4. Data model (new migrations)

All tenant-scoped (`BelongsToOrganization`), ULID PKs like the other business
tables.

| Table | Key columns |
|---|---|
| `bot_settings` | `whatsapp_phone_number_id` (nullable = default), `enabled` bool, `system_prompt` text, `model` string, `temperature`, `max_output_tokens`, `business_hours` json (nullable = 24/7), `fallback_message` text, `handoff_keywords` json, `daily_reply_cap` int |
| `bot_conversations` | `contact_id` (nullable), `wa_phone`, `mode` enum(`bot`,`human`,`paused`), `last_inbound_at`, `last_bot_reply_at`, `replies_today` int, `handoff_reason` nullable, `handoff_at` |
| `bot_messages` | `bot_conversation_id`, `message_id` (FK to `messages`, nullable), `role` enum(`user`,`assistant`,`tool`), `content` text, `tokens_in`, `tokens_out`, `model`, `latency_ms`, `cost_micros` |
| `knowledge_documents` | `title`, `source` enum(`manual`,`url`,`file`), `url` nullable, `body` longtext, `is_active` bool, `synced_at` |
| `knowledge_chunks` | `knowledge_document_id`, `ordinal`, `content` text, `embedding` (see §8 — blob/json, or FULLTEXT-only in MVP), `token_count` |
| `bot_escalations` | `bot_conversation_id`, `reason`, `summary` text, `status` enum(`open`,`claimed`,`closed`), `claimed_by` (user), timestamps |

`messages` already has everything for inbound/outbound; `bot_messages` is the
model-facing transcript (may differ from what's shown to the customer, e.g. tool
calls).

---

## 5. Config / env additions

`config/services.php` → new `bot` block:

```php
'bot' => [
    'driver' => env('BOT_LLM_DRIVER', 'anthropic'),
    'anthropic_key' => env('ANTHROPIC_API_KEY'),
    'model' => env('BOT_MODEL', 'claude-sonnet-5'),
    'max_output_tokens' => env('BOT_MAX_OUTPUT_TOKENS', 1024),
    'service_window_hours' => 24,
    'reply_debounce_seconds' => env('BOT_DEBOUNCE', 8),
    'daily_reply_cap' => env('BOT_DAILY_CAP', 40),
    'asr' => [
        'driver' => env('BOT_ASR_DRIVER', 'groq'),   // groq | openai | whisper_cpp
        'key' => env('BOT_ASR_KEY'),
        'endpoint' => env('BOT_ASR_ENDPOINT'),
        'model' => env('BOT_ASR_MODEL', 'whisper-large-v3'),
    ],
    'kb' => [
        'retrieval' => env('BOT_KB_RETRIEVAL', 'fulltext'), // fulltext | vector
        'top_k' => 5,
    ],
],
```

`.env` (gitignored, never in `.env.example`):
`ANTHROPIC_API_KEY`, `BOT_MODEL`, `BOT_ASR_DRIVER`, `BOT_ASR_KEY`.

`config/queue.php` / Horizon: add `bot` queue (low concurrency, e.g. 3 workers).

---

## 6. Components

| Class | Responsibility |
|---|---|
| `App\Jobs\HandleInboundMessageJob` | Orchestrates the flow in §3. `ShouldBeUnique` on message id. Debounce: if another inbound from the same number arrives within `reply_debounce_seconds`, coalesce (reply once to the latest). |
| `App\Services\Bot\ConversationalAgent` | Builds the request, calls the LLM, runs the tool loop, returns a `BotReply` DTO (text + optional escalation + usage). |
| `App\Services\Bot\LlmClient` (contract) + `AnthropicLlmClient` | Thin wrapper over the Anthropic Messages API. Mirrors the `WhatsAppDriver` pattern (contract + real + `FakeLlmClient` for tests). |
| `App\Services\Bot\PromptBuilder` | Assembles `system` (persona + guardrails + KB snippets, cache-flagged) and `messages` (last N turns from `bot_messages` + the new message, image blocks inline). |
| `App\Services\Bot\KnowledgeBase` | `retrieve(string $query): array` — FULLTEXT (MVP) or vector search over `knowledge_chunks`. |
| `App\Services\Bot\MediaResolver` | `media_id` → download bytes via `MetaCloudApiDriver` (SSRF-guarded, already exists), returns `(mime, bytes)`. |
| `App\Services\Bot\AudioTranscriber` (contract) + drivers | OGG/Opus → text. Transcode with `ffmpeg` to 16 kHz mono wav first when the provider needs it. |
| `App\Services\Bot\Tools\*` | `SearchKnowledgeBaseTool`, `CreateLeadTool`, `EscalateToHumanTool`, `BookCallTool` (Stage 6). Each = JSON schema + a handler. |
| `App\Services\Bot\HandoffService` | Flip `bot_conversations.mode`, open a `bot_escalations` row, notify the team (existing notification channel / a portal inbox). |

Scheduler: `knowledge:sync` (re-crawl `url` docs + re-chunk + re-embed) daily;
`bot:reset-daily-caps` at org midnight.

---

## 7. The LLM call (Anthropic)

Use the official **Anthropic PHP SDK** (`composer require anthropic-ai/sdk`) or
Laravel's `Http` client wrapped in `AnthropicLlmClient` (consistent with the
`SafeHttpClient` pattern). Endpoint: `POST /v1/messages`.

- **Model:** `claude-sonnet-5` default (good quality/latency/cost balance for
  support chat). Drop to `claude-haiku-4-5` if volume/cost demands it — both
  handle vision. `claude-opus-5` only if reply quality proves insufficient.
- **System prompt:** persona + hard guardrails + retrieved KB snippets. Flag the
  stable prefix with `cache_control: {type: "ephemeral"}` — ~90% cheaper on the
  repeated part.
- `max_tokens`: ~1024 (WhatsApp replies are short).
- Thinking: leave adaptive/default; effort `low` or `medium` is plenty for chat.
- **Tool use:** give it `search_knowledge_base`, `escalate_to_human`,
  `create_lead`. Run the tool loop (SDK tool runner or a manual
  `while stop_reason == "tool_use"` loop). Cap at ~4 iterations.
- Log `usage` (input/output/cache tokens) into `bot_messages.cost_micros`.
- Timeouts + typed error handling (rate limit → retry w/ backoff; other →
  send `fallback_message`, don't crash the queue job).

Skeleton system prompt (store editable in `bot_settings.system_prompt`):

```
You are the WhatsApp assistant for AH&V Software (ahvsoftware.com).
AH&V builds: custom software, web apps, WordPress sites, Android & iOS apps,
SaaS products, CRMs; and runs social-media management and SEO.

Your job: answer the customer's question, understand their requirement, and
move them toward a short discovery call with the team.

Rules:
- Never quote a price, a fixed timeline, or promise a delivery date.
  Say the team will share a tailored quote after understanding the scope.
- Only state facts that are in the knowledge base provided. If it's not there,
  say a team member will follow up — do not guess.
- Keep replies short (2–4 sentences), WhatsApp tone, the customer's language
  (English / Hindi / Hinglish — mirror them).
- If the customer is angry, asks for a human, or the request is legal/billing/
  urgent-support, call escalate_to_human and stop.
- Never send links or offers the customer didn't ask for.
```

---

## 8. Knowledge base / RAG

**MVP (Stage 3): MySQL FULLTEXT.** `knowledge_chunks.content` gets a FULLTEXT
index; `retrieve()` runs `MATCH ... AGAINST` on the customer's message, returns
top 5 chunks. Zero extra infra, good enough for a dozen service pages + FAQ.

**Upgrade (later): vector search.** Options on the KVM:
- **Qdrant** in Docker (recommended — simple, fast) — store embeddings there.
- `pgvector` on a small Postgres instance.
- `sqlite-vec` if staying single-file.

Embeddings: Anthropic has no embeddings endpoint — use Voyage AI
(`voyage-3`, Anthropic-recommended) or OpenAI `text-embedding-3-small`.
`knowledge:sync` computes + stores them; `retrieve()` does cosine top-k.

Seed content: AH&V service descriptions, process/FAQ, "what we need from you to
quote", portfolio highlights, contact/working hours. Managed in the portal
(Stage 3 UI), plus optional URL crawl of ahvsoftware.com pages.

---

## 9. Media handling

### Images (Stage 4)
`media_id` → `MediaResolver` downloads (JPEG/PNG/WebP). Pass **inline** to Claude
as an `image` block (base64) alongside the text. No OCR service — the model reads
screenshots, mockups, error photos, documents directly. Cap: reject > ~4 MB,
unsupported mime → polite "please send it as a photo or describe it".

### Voice notes (Stage 5)
`media_id` → download OGG/Opus → `ffmpeg -i in.ogg -ar 16000 -ac 1 out.wav` →
`AudioTranscriber`:

| Driver | Notes |
|---|---|
| `groq` | Whisper-large-v3, very fast + cheap, hosted. **Recommended start.** |
| `openai` | `whisper-1`, ~$0.006/min. |
| `whisper_cpp` | Self-hosted on the KVM (`small`/`medium` model). Free, no data leaves the box. Slower; fine for low volume. |

Transcript is fed to the agent like any text message. `bot_messages` stores the
transcript. (Voice *replies* out of scope — reply in text.)

---

## 10. Guardrails & safety

| Risk | Control |
|---|---|
| Bot commits to price/deadline | System prompt + a post-generation regex/keyword check that strips or re-routes replies containing currency amounts / "days"/"weeks" delivery claims. |
| Needs a human | `escalate_to_human` tool + `handoff_keywords` list ("agent", "call me", "complaint", "refund"). On escalate: `mode=human`, open escalation, notify team, bot silent until an agent closes it or 24h passes. |
| Reply loops / spam | Trigger only on `direction=inbound`; `ShouldBeUnique`; `reply_debounce_seconds`; `daily_reply_cap` per conversation → then fallback + escalate. |
| 24h window | If `now - last_inbound_at > 24h`: don't free-form. Either stay silent or send an approved "we received your message" **template** (config). |
| Meta rate limits | Reuse `WhatsAppRateLimiter` on the outbound send. |
| Sending disabled | Existing kill switch already blocks `OutboundMessageService`. Add a **separate** `bot_settings.enabled` so the bot can be paused without stopping campaigns. |
| Cost blowout | Per-org daily token budget; alert + auto-pause bot at threshold. |
| Prompt injection via customer text | Customer text is always a `user` message, never merged into `system`. Tools do their own authz + tenant scoping. |
| Data / privacy | Transcripts are tenant-scoped; retention prune (Phase 6) covers `bot_messages`. Note in privacy policy that messages are processed by an AI provider. |
| Policy | Bot answers inbound only; never initiates; no marketing pushes; clearly a business assistant. |

---

## 11. Portal UI (Stage 2 onward)

New sidebar section **"Assistant"** (permission `bot.manage`):

- **Settings** — enable toggle, model, persona/system-prompt editor,
  business hours, fallback message, handoff keywords, daily cap.
- **Knowledge base** — CRUD documents, "crawl URL", re-sync, see chunk counts.
- **Conversations** — list of `bot_conversations`, transcript view
  (customer + bot side by side), "take over" button (→ `mode=human`),
  "hand back to bot".
- **Escalations** — inbox of open `bot_escalations`, claim/close, jump to
  transcript.
- **Usage** — replies/day, tokens, est. cost, escalation rate, deflection rate.

Reuse the DataTables + `x-dt-filter` + Turbo setup already in place.

---

## 12. Phased delivery

| Stage | Deliverable | Depends on |
|---|---|---|
| **7.1 — Webhook live + inbound plumbing** | Public URL registered, `messages` field subscribed, `HandleInboundMessageJob` dispatched (no-op logging), `bot` queue + Horizon. Verify real inbound messages land as `Message` rows. | public URL |
| **7.2 — Text auto-reply (MVP)** | `bot_settings`, `bot_conversations`, `bot_messages`, `ConversationalAgent` + `AnthropicLlmClient`, hardcoded-then-editable system prompt, 24h-window check, debounce, daily cap, kill switch, **handoff keyword** → notify team. Portal: Settings + Conversations + take-over. | 7.1, `ANTHROPIC_API_KEY` |
| **7.3 — Knowledge base (FULLTEXT RAG)** | `knowledge_documents/chunks`, chunker, `KnowledgeBase::retrieve`, `search_knowledge_base` tool, KB admin UI, seed AH&V content, URL crawl. | 7.2 |
| **7.4 — Image understanding** | `MediaResolver`, inline image blocks, size/mime guards, transcript stores "[image]". | 7.2 |
| **7.5 — Voice notes** | `ffmpeg` on host, `AudioTranscriber` (Groq default), OGG→wav, transcript flow. | 7.2, ASR key |
| **7.6 — Tools & vector RAG** | `create_lead` (into CRM/contacts), `book_call` (calendar link/Cal.com), `escalate_to_human` tool proper, Qdrant + embeddings, escalations inbox, usage dashboard. | 7.3 |

Ship 7.1 + 7.2 first — that alone covers ~80% of the value.

---

## 13. Deployment notes (Hostinger KVM)

- **Stack:** Nginx + PHP-FPM 8.3, MySQL 8, Redis, Supervisor (Horizon), Certbot.
- **`ffmpeg`:** `apt install ffmpeg` (Stage 5).
- **Workers (Supervisor / Horizon):**
  `whatsapp-high`, `whatsapp-send`, `whatsapp-webhook`, `whatsapp-media`,
  `whatsapp-reports`, **`bot`** (3 workers, 120s timeout — LLM + ASR are slow).
- **Webhook must return 200 in < 5s** — it already just persists + queues; the
  bot work is all async on the `bot` queue. Good.
- **Outbound egress:** allow `api.anthropic.com`, the ASR endpoint, and
  `*.whatsapp.net` (media download).
- **Secrets:** `.env` on the box only; consider Hostinger's env UI or
  `php artisan config:cache` after deploy. Never commit.
- **Backups:** `bot_messages` / `knowledge_*` in the normal DB backup.
- **Media disk:** keep `WABA_MEDIA_DISK=local` for now (inbound media is
  transient — download, use, optionally discard); switch to R2 with the rest
  later.
- KVM 4 is enough for launch; KVM 8 if voice volume or vector DB grows.

---

## 14. Cost estimate (rough, INR)

Per inbound conversation (≈4 turns, Sonnet 5, cached system prompt):
- LLM: ~2–4K in + ~1K out ≈ **₹1–2**
- Voice (if any, 30s): Groq Whisper ≈ **₹0.05**
- Embeddings: one-time per KB doc, negligible

At 50 conversations/day ≈ **₹1,500–3,000/month** + host. Haiku 4.5 roughly
halves the LLM part. Add a hard monthly budget + auto-pause.

---

## 15. Testing

- `FakeLlmClient` / `FakeTranscriber` — deterministic, no network (like
  `MockWhatsAppDriver`). Forced in `phpunit.xml`.
- Feature: inbound text → bot reply persisted + sent; handoff keyword →
  `mode=human`, no reply, escalation opened; outside 24h → no free-form send;
  debounce coalesces; daily cap → fallback; `bot_settings.enabled=false` → silent;
  tenant isolation on all bot tables; kill switch still blocks the outbound.
- Unit: `PromptBuilder` (KB snippets in system, customer text never in system),
  price/timeline scrubber, `KnowledgeBase::retrieve` ranking, OGG→wav transcode
  path, media size/mime guards.
- Webhook signature / idempotency tests already cover the entry point.

---

## 16. Open decisions (pick before Stage 7.2)

1. **ASR provider** — Groq (fast/cheap/hosted) vs self-hosted `whisper.cpp`
   (free, private, slower). → lean Groq to start.
2. **Model** — `claude-sonnet-5` vs `claude-haiku-4-5` default. → Sonnet 5, revisit on cost.
3. **Lead sink for `create_lead`** — existing `contacts` table + a tag, or a new
   `leads` table, or push to the external AH&V CRM.
4. **Booking** — Cal.com / Calendly link in the reply, or a real integration.
5. **KB retrieval** — ship FULLTEXT, upgrade to Qdrant when answers get thin.
6. **Multi-number** — one bot config for the whole org, or per phone number.
