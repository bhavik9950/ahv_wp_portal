# Comprehensive Architectural & Regulatory Guide: Building an SMS Gateway and AI Voice Calling Platform

---

## 1. Executive Summary

Just as your current platform operates as a multi-tenant WhatsApp Business Solution Provider (BSP / Tech Provider) on Meta's Cloud API, expanding into **SMS Gateway** and **AI Voice Telephony** requires understanding two layers:
1. **The Telecommunications & Regulatory Layer** (Operator connectivity, TRAI / DLT compliance, DoT regulations).
2. **The Software & Media Layer** (SMPP protocol engines, SIP PBX switching, real-time Audio streaming, and conversational AI pipelines).

Depending on your capital and timeline, there are **two distinct operational paths**:
* **Route A (Aggregator / CPaaS API Partner — Recommended to Start):** You build the complete multi-tenant UI, campaign engine, AI voice brain, and billing portal, but route actual SMS and calls through wholesale CPaaS APIs (e.g., Exotel, Route Mobile, ValueFirst, Airtel IQ, Twilio). You operate as the "3rd party provider" to your clients with zero telecom hardware overhead.
* **Route B (Direct Telecom Operator / Telemarketer):** You register as a licensed Telemarketer with TRAI, obtain direct SMPP / SIP interconnects from Tier-1 operators (Airtel, Jio, Vi, Tata Tele), host your own SMPP gateway clusters and FreeSWITCH PBX servers, and handle regulatory scrubbing in-house.

---

## 2. SMS Gateway Architecture & Requirements

```
[Your Client / Tenant]
         │ (HTTP REST API / Web Portal)
         ▼
[AHV Multi-Tenant SMS Gateway Engine]
         │
         ├── Credit / Wallet Verification & DLT Template Validation
         ├── Queue & Priority Router (OTP > Transactional > Promotional)
         ▼
[SMPP Connector / Transceiver (Jasmin / Kannel / Custom Go SMPP)]
         │ (SMPP v3.4 over TLS)
         ▼
[Telecom Operator SMSC (Airtel / Jio / Vi / Aggregator)]
         │ (SS7 / GSM Network)
         ▼
[Recipient Handset] ──(DLR: Delivered)──> [Webhook / Status Callback]
```

### A. Regulatory & Legal Requirements (India - TRAI DLT Framework)
In India, commercial SMS is governed strictly by the **TRAI TCCCPR 2018 (Telecom Commercial Communications Customer Preference Regulations)** via **DLT (Distributed Ledger Technology)** blockchain portals (Vilpower/Jio, Airtel DLT, Vodafone DLT).

1. **Telemarketer (TM) Registration:**
   - To sell SMS services to multiple businesses, your company (**AH&V Software**) must register as an **Authorized Telemarketer (Aggregator)** on DLT portals (e.g., Jio DLT or Airtel DLT).
   - **Entity Type:** Telemarketer - Delivery (TMD) or Telemarketer - Aggregator (TMA).
   - One-time registration fee: approx. ₹5,000 to ₹10,000 + GST.
2. **Client Onboarding (Principal Entity - PE):**
   - Each client organization on your portal must have an approved **Principal Entity (PE) ID**.
   - Your portal binds their PE ID, Headers (Sender IDs), and Content Template IDs.
3. **Header / Sender ID Types:**
   - **Transactional / Service Implicit:** 6-character alpha headers (e.g., `AHVSFT`, `FITGNR`) for OTPs, account alerts, order receipts (allowed 24x7).
   - **Service Explicit / Promotional:** 6-digit numeric headers (e.g., `561611`) or prefixed 140/160 series for offers and marketing (allowed only **10:00 AM to 9:00 PM**, strictly scrubbed against TRAI DND/NCPR list).
4. **Template Scrubbing (Chain Binding):**
   - Every SMS sent must pass:
     `PE ID` + `Header ID` + `Registered Template ID` + Exact variable placeholders (`{#var#}`).
   - Non-compliant messages are rejected immediately by operator SMSCs with DLT error codes (e.g., `ERR 101: Template Mismatch`).

### B. Technical Infrastructure & Stack
To operate your own SMS Gateway service:

1. **Protocol Engine (SMPP v3.4 / v5.0):**
   - **SMPP (Short Message Peer-to-Peer):** The global standard protocol used to talk to telecom SMSCs.
   - **Open-Source SMS Gateways:**
     - **Jasmin SMS Gateway:** High-performance, Python/Twisted + Redis/RabbitMQ based, supports SMPP client/server, HTTP APIs, and billing.
     - **Kannel:** Classic C-based WAP/SMS gateway for extreme throughput.
     - **Custom SMPP Engine:** Written in Go (`fiorix/go-smpp`) or Java (`ch-smpp`) for microsecond routing.
2. **SMS Delivery Queue & Prioritization:**
   - **OTP Lane (Queue priority 1):** Sub-second delivery guarantee. Bypass promotional batching.
   - **Transactional Lane (Queue priority 2):** Order updates, receipts.
   - **Bulk Promotional Lane (Queue priority 3):** High-throughput rate-limited batching (e.g., 500–2,000 SMS/sec).
3. **Delivery Receipts (DLR) Processing:**
   - Two-way SMPP Transceiver handles incoming `deliver_sm` packets from operators.
   - Status updates mapped to: `DELIVRD`, `EXPIRED`, `UNDELIV`, `REJECTD`, `UNKNOWN`.
   - Real-time webhooks fired back to tenants.

---

## 3. Voice Calling & AI Calling Agent Architecture

```
[Customer Phone]
      │ (PSTN / Mobile Network)
      ▼
[Telecom SIP Trunk / PRI Line (Airtel / Tata / Exotel)]
      │ (SIP Signaling + G.711 / Opus RTP Audio Stream)
      ▼
[Telephony Switch (FreeSWITCH / Asterisk / LiveKit SIP)]
      │
      ├── [Inbound / Outbound Call Handler (Audio Forking over WebSocket)]
      ▼
[Real-Time AI Voice Pipeline (< 600ms End-to-End Latency)]
   │
   ├── 1. STT (Speech-to-Text): Deepgram Nova-2 / Whisper Realtime
   │      Converts user voice into text in chunks.
   │
   ├── 2. LLM Orchestrator: Gemini 1.5 Flash / Groq Llama 3.3
   │      Maintains prompt, persona, business tools, CRM lookup.
   │
   ├── 3. TTS (Text-to-Speech): Cartesia Sonic / ElevenLabs / Sarvam AI (Hindi)
   │      Converts generated response to low-latency streaming PCM audio.
   │
   └── 4. Barge-in / Interruption Engine:
          Instantly cuts off TTS audio when user starts speaking.
```

### A. Telephony Infrastructure
To place and receive telephone calls programmatically:

1. **SIP Trunking / Direct PRI Lines:**
   - **Option 1 (Direct Telco Interconnect):** Lease SIP trunks from Tata Communications, Airtel Business, or Jio Enterprise. Requires a **DoT OSP (Other Service Provider)** registration and leased line/static IP infrastructure.
   - **Option 2 (Programmable SIP Provider):** Twilio SIP, Exotel Voice API, Plivo, or Sinch.
2. **Telephony Server (Softswitch / Media Server):**
   - **FreeSWITCH:** Industry standard for telecom carriers. Supports high concurrent channels (1,000+ simultaneous calls per node), WebRTC, and SIP trunking.
   - **Asterisk:** Easy PBX setup for dialplans and IVR menus.
   - **LiveKit / Pipecat:** Modern WebRTC/SIP bridge designed specifically for AI voice agents.
3. **Call Progress Analysis (CPA / AMD):**
   - **Answering Machine Detection (AMD):** Detects whether a human answered (`HUMAN`), an automated voicemail picked up (`MACHINE`), or the number was busy/unreachable.
   - Critical for outbound calling to avoid wasting AI tokens on voicemails.

### B. AI Calling Agent Pipeline (Voice AI Stack)
To build conversational human-like agents (like Bolna, Retell, Vapi):

1. **Audio Streaming Layer:**
   - Telephony converts 8kHz `PCMA / PCMU` audio to WebSocket streaming buffers.
2. **Speech-to-Text (STT):**
   - **Deepgram Nova-2:** Ultra-fast (<150ms transcription delay), supports English and Indian English.
   - **Sarvam AI / Bhashini:** Best for Indian regional languages (Hindi, Punjabi, Gujarati, Tamil, etc.).
3. **Conversational Brain (LLM):**
   - Ultra-low time-to-first-token (TTFT) models:
     - **Gemini 1.5 Flash:** Best balance of speed, intelligence, and price.
     - **Groq Llama 3.3 70B:** Extreme speed (<120ms first token).
4. **Text-to-Speech (TTS):**
   - Streaming chunk-by-chunk playback:
     - **Cartesia Sonic:** ~100ms latency, natural accents.
     - **ElevenLabs Turbo v2.5:** High expressiveness and emotional nuance.
     - **Sarvam AI (Bulbul):** Authentic Indian Hindi/Hinglish tones.
5. **Turn-Taking & Interruption (Barge-In):**
   - When Voice Activity Detection (VAD) detects the user speaking while the AI is talking, immediately cancel the current audio playback queue and update the LLM context.

### C. Indian Telephony Regulations for Voice
- **Calling Hours:** Outbound automated promotional calls are strictly prohibited outside **9:00 AM to 9:00 PM**.
- **CLI (Caller Line Identification):**
  - Promotional calls must display assigned **140-series** or **160-series** numbers.
  - Transactional / service calls can use dedicated virtual landline/mobile numbers.
- **DND Scrubbing:** Numbers registered on National Do Not Call (NDNC/NCPR) must be scrubbed automatically prior to triggering promotional campaigns.

---

## 4. Platform Integration: Adding SMS & Voice to AHV Portal

Your current Laravel platform already has excellent foundations:
* Multi-tenant architecture (`OrganizationScope`, `TenantContext`).
* Role-based permissions & credit/subscription mechanisms.
* Asynchronous queuing (`ProcessWhatsAppWebhookJob`, scheduled campaigns).

### A. Proposed Database Entities

```
organizations
  ├── sms_configurations (sender_ids, DLT entity_id, smpp_credentials)
  ├── sms_templates (dlt_template_id, body, variables, status)
  ├── sms_campaigns (audience_count, sent, delivered, failed)
  ├── sms_messages (direction, to_phone, status, dlr_code, cost)
  │
  ├── voice_trunks (sip_server, username, max_channels)
  ├── voice_agents (prompt, language, voice_id, temperature, tools)
  ├── voice_campaigns (concurrency_limit, retry_policy, schedule)
  └── voice_call_logs (call_sid, duration, recording_url, transcript, sentiment)
```

### B. Multi-Tenant Wallet & Billing Engine
Unlike WhatsApp which charges per 24h conversation template window:
* **SMS Billing:** Deducted per **160-character segment** (GSM-7) or **70-character segment** (Unicode / Hindi).
* **Voice Calling Billing:** Deducted per **pulse (30-sec or 60-sec intervals)** + **LLM/STT/TTS token usage per minute**.

---

## 5. Implementation Roadmap & Recommendation

| Phase | Milestone | Focus Area | Recommended Tech Stack |
|---|---|---|---|
| **Phase 1** | **CPaaS Aggregated SMS (1-2 Weeks)** | Reseller model for SMS. Connect via HTTP/REST to a wholesale Indian SMS aggregator. Implement DLT template management and multi-tenant campaign dispatch. | Laravel Queues, Exotel/Route Mobile HTTP API, DLT Validation Engine |
| **Phase 2** | **AI Voice Agent MVP (2-3 Weeks)** | Build Inbound/Outbound AI Voice Agent using WebRTC and streaming API. Test automated lead qualification calls. | Twilio/Exotel SIP + LiveKit/Pipecat + Deepgram + Gemini Flash + Cartesia/ElevenLabs |
| **Phase 3** | **Self-Hosted FreeSWITCH PBX (4-6 Weeks)** | Replace 3rd-party voice APIs with self-hosted FreeSWITCH clusters and direct SIP trunking from Airtel/Tata to cut per-minute costs by 70%. | FreeSWITCH, Kamailio, Redis, WebSockets, Python FastAPI audio pipeline |
| **Phase 4** | **Direct SMPP Gateway (6-8 Weeks)** | Obtain official TRAI Telemarketer License and connect direct SMPP v3.4 binds to Telecom SMSCs. | Jasmin SMS Gateway, Redis, PostgreSQL, Direct Operator Binds |

---

## 6. Checklist: What You Need Immediately

1. **Company Documents for DLT:**
   - Certificate of Incorporation / GST Certificate of AH&V Software.
   - PAN card of the company & authorized signatory.
   - Letter of authorization on company letterhead.
2. **Telecom / API Accounts:**
   - **For SMS:** Telemarketer Account on Jio DLT (`telemarketer.jio.com`) or Airtel DLT.
   - **For Voice:** Programmable Voice Trunk account (Exotel, Tata Tele Smartflo, or Twilio).
   - **For AI Voice:** Deepgram API key (STT) + ElevenLabs / Sarvam API key (TTS) + Gemini API key (LLM).
