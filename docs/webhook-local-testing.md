# Receiving webhooks locally (Cloudflare Tunnel)

Goal: let Meta deliver inbound messages + delivery/read statuses to the portal
running on your machine, so they show up under **WhatsApp → Messages** and
**Admin → Webhook Events**.

## One-time

1. Install `cloudflared` (Windows: `winget install --id Cloudflare.cloudflared`).
2. `cloudflared tunnel login` (opens browser, pick the `ahvsoftware.com` zone).
3. `cloudflared tunnel create ahv-wp` → note the tunnel ID.
4. Route the subdomain to the tunnel:
   `cloudflared tunnel route dns ahv-wp wp-ahv.ahvsoftware.com`
5. Create `~/.cloudflared/config.yml`:
   ```yaml
   tunnel: <tunnel-id>
   credentials-file: C:\Users\zilac\.cloudflared\<tunnel-id>.json
   ingress:
     - hostname: wp-ahv.ahvsoftware.com
       service: http://localhost:8000
     - service: http_status:404
   ```

## Each session

```bash
# 1. app
php artisan serve                       # http://localhost:8000

# 2. queue worker  ── REQUIRED, else events stay "received" and never process
php artisan queue:work --queue=whatsapp-webhook,whatsapp-high,whatsapp-send,whatsapp-media,default

# 3. tunnel
cloudflared tunnel run ahv-wp
```

`.env` while the tunnel is up:

```
APP_URL=https://wp-ahv.ahvsoftware.com
TRUSTED_PROXIES=*
```
then `php artisan config:clear`.

## Point Meta at it

Meta ▸ App Dashboard ▸ **WhatsApp ▸ Configuration ▸ Webhook**:

- **Callback URL:** `https://wp-ahv.ahvsoftware.com/api/webhooks/whatsapp`
- **Verify token:** `tms-waba-webhook-verify`  (`WABA_WEBHOOK_VERIFY_TOKEN`)
- Click **Verify and save** → Meta sends a GET; you should get a green tick.
- **Webhook fields → Manage →** subscribe **`messages`** (covers inbound
  messages *and* message status updates).

`WABA_APP_SECRET` must be set (it is) — POST deliveries are rejected without a
valid `X-Hub-Signature-256`.

## Verify it works

1. From a personal WhatsApp, send a message to **+91 81040 55511**.
2. **Admin → Webhook Events** — a row appears within a second or two:
   - `kind` = `inbound: text`, `signature` = valid, `status` = processed.
   - If it's stuck at `received`: the queue worker isn't running.
   - If `signature invalid`: `WABA_APP_SECRET` mismatch.
   - If nothing appears: tunnel/Meta URL wrong, or `messages` not subscribed.
3. **WhatsApp → Messages** (filter Direction = Inbound) — the message is there.
4. Send yourself a test from **WhatsApp → Test Send**; its row should now move
   `sent → delivered → read` as the phone reports back (previously stuck at
   `sent` because those updates also come via webhook).

## Notes

- The Cloud API number has no phone inbox — this portal *is* the inbox.
- Replying back from the portal: **Test Send** (free text, within the 24h
  service window). A proper conversation view + AI auto-reply is Phase 7
  (`docs/whatsapp-chatbot-plan.md`).
- Meta retries failed deliveries for a while; a wrong URL isn't permanently lost
  if you fix it within the retry window.
