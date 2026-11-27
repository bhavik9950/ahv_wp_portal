# Laravel AI SDK (`laravel/ai`) — Integration & Product Roadmap

This document outlines a phased plan for integrating **Laravel's official AI SDK (`laravel/ai`)** into the **AH&V WhatsApp Portal** (`Laravel 12` / `PHP 8.2+`). Each phase compares the **Current State** in the repository, the **Planned Updates** (architecture and workflow level, without implementation code), and the **Benefits**.

---

## Executive Summary

The portal already has a clean multi-tenant foundation for AI automation (`BotService`, `PromptBuilder`, `AssistantController`, `BotConversation`, and `anthropic-ai/sdk`). However, it is currently limited to a single LLM provider (Anthropic), static system prompts, keyword-based handoffs, and no document knowledge base. Adopting `laravel/ai` unlocks multi-provider support, per-tenant RAG (PDF/FAQ search), tool calling, structured inbox triage, and AI-assisted template creation.

---

## Phase 1: Laravel AI SDK Foundation & Multi-Provider Support

### Current State
- `composer.json` includes `anthropic-ai/sdk` (`^0.47.0`) directly.
- LLM integration is handled via `app/Services/Bot/Llm/AnthropicLlmClient.php` and `FakeLlmClient.php`.
- `AssistantController` (`app/Http/Controllers/Bot/AssistantController.php`) and `resources/views/assistant/settings.blade.php` store per-organization `system_prompt`, `model`, `daily_reply_cap`, `handoff_keywords`, and `fallback_message`.
- Sidebar navigation currently marks **Assistant** and **Assistant Settings** with a `SOON` badge.

### Planned Updates
1. Install `laravel/ai` and configure provider credentials (OpenAI, Anthropic, Gemini) in environment configuration.
2. Introduce a new `LaravelAiLlmClient` inside `app/Services/Bot/Llm/` implementing the existing Bot LLM contract so `BotService` works without breaking existing flows.
3. Add a **Provider** selector alongside the **Model** field in `AssistantController` and `resources/views/assistant/settings.blade.php` so each organization can choose its preferred AI provider and model.
4. Remove the `SOON` badge from the sidebar once Phase 1 testing passes and enable the Assistant module per tenant.

### Benefits
- **Zero Vendor Lock-in:** Switch between OpenAI, Claude, and Gemini per tenant or per use case without rewriting service code.
- **Cost Optimization:** Use lightweight models (e.g., Gemini Flash / GPT-4o-mini) for simple FAQ replies and stronger models for complex conversations.
- **Backward Compatibility:** Existing `BotService` and `FakeLlmClient` workflows remain intact.

---

## Phase 2: Per-Tenant Knowledge Base (RAG / Document Search)

### Current State
- All business knowledge (event dates, venue, pricing, FAQs) must be manually typed into a single `system_prompt` text field (capped at 8,000 characters in `AssistantController`).
- There is no support for uploading brochures, PDFs, or FAQ documents, nor any vector/semantic search.

### Planned Updates
1. Add a new database table and storage workflow for organization-scoped knowledge base documents (PDF, TXT, DOCX, or plain FAQ entries).
2. Add a **Knowledge Base** management section in `resources/views/assistant/settings.blade.php` where each organization can upload, view, and delete its documents.
3. Create a queued background job that extracts text from uploaded files, splits it into chunks, and generates vector embeddings via `laravel/ai`.
4. Update `PromptBuilder` (or the AI Agent) to query only the active organization's embeddings and inject the top matching context snippets when answering inbound WhatsApp messages.

### Benefits
- **Accurate Customer Answers:** Customers asking specific questions (e.g., *"last date of registration"*, *"venue"*, *"entry fee"*) receive exact answers grounded in the client's official documents.
- **Multilingual Support:** Documents uploaded in English can accurately answer queries asked in Hindi or Hinglish.
- **Strict Tenant Isolation:** Vector queries are scoped by `organization_id`, ensuring one client's data is never exposed to another.

---

## Phase 3: Agentic Tools & Smart Human Handoff

### Current State
- Human handoff relies only on static comma-separated `handoff_keywords` configured in Assistant settings.
- The bot can only send text replies; it cannot update contact records, assign groups, or process opt-outs autonomously.

### Planned Updates
1. Define Laravel AI **Tools** scoped to the current tenant and contact:
   - **Smart Handoff Tool:** Detects customer frustration, complaints, or explicit requests for a human agent (even in Hindi/Hinglish) and switches `BotConversation` to human mode while flagging the conversation in the dashboard.
   - **Group Assignment Tool:** Automatically attaches or moves a contact into an existing contact group (e.g., `10km`, `21km`, `Interested`) based on conversation intent.
   - **Opt-Out Compliance Tool:** Detects unsubscribe intent across languages (e.g., *"STOP"*, *"message mat bhejo"*) and marks the contact as opted out.
2. Attach these tools to the WhatsApp Assistant Agent with strict validation and audit logging on every tool execution.

### Benefits
- **Actionable Automation:** Converts passive chat replies into CRM updates (group segmentation, lead tagging, and opt-out compliance).
- **Better Customer Experience:** Smooth, context-aware escalation to human agents instead of brittle keyword matching.
- **Meta Compliance:** Automatic opt-out handling reduces spam reports and protects the WABA quality rating.

---

## Phase 4: AI Copilots for Templates & Inbox

### Current State
- **Templates (`/whatsapp/templates/create`):** Users manually write template copy, add `{{1}}`, `{{2}}` placeholders, and fill sample variable values for Meta review.
- **Conversations (`/whatsapp/conversations`):** Support agents manually scan inbound messages to separate real inquiries from reactions or noise, and type every reply manually.

### Planned Updates
1. **Template Copilot (`/whatsapp/templates/create`):**
   - Add a **"Generate with AI"** assistant panel where a user enters a short goal (e.g., *"event registration reminder in Hindi"*).
   - Use `laravel/ai` **Structured Output** to return:
     - Compliant template body with sequential `{{1}}`, `{{2}}` variables
     - Realistic `sample_variables` for Meta approval
     - Suggested category (`UTILITY` vs `MARKETING`) and a pre-submission warning if promotional language is detected inside a `UTILITY` template.
2. **Inbox Copilot (`/whatsapp/conversations`):**
   - Classify inbound messages automatically using Structured Output (`Lead / Inquiry`, `Support`, `Opt-out`, `Reaction / Noise`) so agents can filter high-priority conversations immediately.
   - Add a **1-Click "Draft Reply"** button in the chat view that reads recent conversation history and drafts a context-aware reply for the agent to review and send.

### Benefits
- **Higher Meta Approval Rate:** Fewer template rejections caused by missing variables, poor formatting, or category mismatches.
- **Faster Agent Response Time:** Agents spend time only on genuine leads/queries and reply in seconds instead of typing repetitive responses.

---

## Phase 5: Usage Guardrails, Cost Control & Automated Testing

### Current State
- `BotSettings` tracks `daily_reply_cap` per conversation, and `FakeLlmClient` exists for basic testing, but there is no token-level usage tracking, monthly tenant quota, or `laravel/ai` fake coverage.

### Planned Updates
1. Record input/output token counts and estimated cost per `organization_id` for every AI call (bot replies, embeddings, and copilots).
2. Enforce configurable monthly AI usage caps per organization with graceful fallback to human handoff or `fallback_message` when limits are reached.
3. Build a Pest feature test suite using `laravel/ai` `Agent::fake()` (and `FakeLlmClient`) covering:
   - Multi-tenant isolation for prompts and knowledge base search
   - Handoff, group tagging, and opt-out tool calls
   - Template Copilot structured output validation
   - Daily/monthly cap enforcement

### Benefits
- **Predictable Costs:** Prevents runaway API bills for high-volume tenants.
- **Production Reliability:** Automated regression tests run in CI without spending real LLM API credits.

---

## Recommended Rollout Order

| Priority | Phase | Estimated Effort | Primary Impact |
| :--- | :--- | :--- | :--- |
| 1 | **Phase 1: SDK Setup & Multi-Provider** | 1–2 days | Unlocks OpenAI, Claude & Gemini via one interface |
| 2 | **Phase 4.1: Template Copilot** | 1 day | Immediate daily productivity boost & fewer Meta rejections |
| 3 | **Phase 2: Knowledge Base (RAG)** | 2–3 days | Accurate, document-backed WhatsApp auto-replies |
| 4 | **Phase 3: Tools & Smart Handoff** | 2–3 days | Automated group tagging, opt-outs & smart escalation |
| 5 | **Phase 4.2 + Phase 5: Inbox Copilot & Guardrails** | 2–3 days | Agent triage speed, cost control & full test coverage |
