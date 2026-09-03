# Production Deployment — AH&V WhatsApp Portal

Target: **Hostinger KVM 4 / KVM 8**, Ubuntu 22.04 / 24.04, root SSH.
Domain: **`wp-ahv.ahvsoftware.com`** (webhook + portal on the same host).
Stack: Nginx · PHP-FPM 8.3 · MySQL 8 · Supervisor · Certbot. Redis optional (see §11).

> Do the **security items** in §4 — they are release blockers, not optional.
> Full findings: `tests/AHV_Security_Review_Result.md`.

---

## 0. Before you start

- [ ] KVM provisioned, you have `root` SSH (`ssh root@<KVM_IP>`).
- [ ] Access to the DNS for `ahvsoftware.com` (to add an A record).
- [ ] The production `WABA_*` secrets to hand (access token, app secret, ids).
- [ ] Local repo pushed to GitHub (`git push`), or a deploy key ready.

---

## 1. DNS

Add an **A record**: `wp-ahv` → `<KVM_IP>`, proxy/CDN **off** for now (grey cloud) so
Certbot's HTTP-01 challenge works. Wait for it to resolve:

```bash
dig +short wp-ahv.ahvsoftware.com   # must return the KVM IP
```

---

## 2. Server preparation (run as root, once)

```bash
apt update && apt upgrade -y
apt install -y software-properties-common curl git unzip supervisor ufw \
    nginx mysql-server redis-server ffmpeg

# PHP 8.3
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-fileinfo

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Node 20 (for the asset build)
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Firewall
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
```

### MySQL

```bash
mysql_secure_installation      # set a root password, remove test db, disallow remote root

mysql -u root -p <<'SQL'
CREATE DATABASE ahv_waba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ahv_waba'@'localhost' IDENTIFIED BY 'CHANGE-ME-strong-random';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON ahv_waba.* TO 'ahv_waba'@'localhost';
FLUSH PRIVILEGES;
SQL
```

> The app user gets DML + DDL (needs DDL for `php artisan migrate`). It does **not**
> get `ALL PRIVILEGES` and is **not** root — finding **DB least-privilege**.

### Deploy user

```bash
adduser --disabled-password --gecos "" deploy
usermod -aG www-data deploy
```

---

## 3. Get the code

```bash
su - deploy
cd /home/deploy
git clone https://github.com/<org>/ahv_wp_portal.git portal
cd portal
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # produces public/build/
```

---

## 4. `.env` for production  ⚠ security-critical

```bash
cp .env.example .env
php artisan key:generate         # FRESH key — never reuse the .env.example one (finding H-1)
nano .env
```

Set these (everything not listed keeps its `.env.example` default):

```dotenv
APP_NAME="AH&V WhatsApp Portal"
APP_ENV=production                 # finding L-2
APP_DEBUG=false                    # finding L-2 — no stack traces / env dump
APP_URL=https://wp-ahv.ahvsoftware.com
APP_TIMEZONE=UTC                   # keep UTC; logs show IST via LOG_TIMEZONE
LOG_TIMEZONE=Asia/Kolkata

# Behind Nginx on the same box → trust only localhost (finding M-1). NEVER "*".
TRUSTED_PROXIES=127.0.0.1

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ahv_waba
DB_USERNAME=ahv_waba
DB_PASSWORD=CHANGE-ME-strong-random

# Simplest: database-backed. Switch to redis later (see §11).
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true         # finding L-2 — HTTPS only cookie
CACHE_STORE=database
QUEUE_CONNECTION=database

# WhatsApp / WABA — production secrets
WABA_DRIVER=meta_cloud_api
WHATSAPP_SENDING_ENABLED=true
WABA_BASE_URL=https://graph.facebook.com
WABA_API_VERSION=v22.0
WABA_ACCESS_TOKEN=<production token>
WABA_PHONE_NUMBER_ID=<...>
WABA_BUSINESS_ACCOUNT_ID=<...>
WABA_META_BUSINESS_ID=<...>
WABA_APP_ID=<...>
WABA_APP_SECRET=<...>
WABA_WEBHOOK_VERIFY_TOKEN=<pick a long random string>
WABA_WEBHOOK_REQUIRE_SIGNATURE=true
WABA_DEFAULT_COUNTRY_CODE=91
WABA_LOG_CHANNEL=whatsapp
WABA_MEDIA_DISK=local             # switch to r2 + fill R2_* when ready

MAIL_MAILER=smtp                  # for password-reset emails — fill a real SMTP
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-reply@ahvsoftware.com"
```

> **Rotate the WABA access token** before going live (outstanding from the earlier
> GitHub leak). Generate a fresh one in Meta ▸ System Users.

---

## 5. Build the app

```bash
php artisan migrate --force
php artisan db:seed --class=DemoDataSeeder --force   # creates admin@ahv.test / password — CHANGE the password immediately after first login
php artisan waba:setup                                # pulls phone numbers + templates, runs 6 connection checks
php artisan storage:link

# Production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Permissions
sudo chown -R deploy:www-data /home/deploy/portal
sudo chmod -R 775 storage bootstrap/cache
```

Verify: `php artisan about` and `php artisan waba:setup` output — all 6 checks OK.

---

## 6. Nginx

`/etc/nginx/sites-available/wp-ahv`:

```nginx
server {
    listen 80;
    server_name wp-ahv.ahvsoftware.com;
    root /home/deploy/portal/public;

    index index.php;
    charset utf-8;

    client_max_body_size 110M;          # media / template samples up to 100M

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Never serve app internals (finding: web root exposure)
    location ~ /\.(?!well-known).* { deny all; }
    location ~* \.(env|log|sqlite|md|lock|json|yml|yaml)$ { deny all; }
    location ^~ /storage/ { deny all; }   # private media is served via signed app routes
}
```

```bash
ln -s /etc/nginx/sites-available/wp-ahv /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

---

## 7. HTTPS

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d wp-ahv.ahvsoftware.com --redirect --agree-tos -m admin@ahvsoftware.com --no-eff-email
```

Certbot rewrites the vhost to add `listen 443 ssl`, the cert paths and the
80→443 redirect. Auto-renew is installed as a systemd timer — check with
`systemctl list-timers | grep certbot`.

Test: `curl -sI https://wp-ahv.ahvsoftware.com` → `HTTP/2 200` with
`strict-transport-security`, `content-security-policy` headers present.

---

## 8. Queue worker + scheduler (Supervisor)

`/etc/supervisor/conf.d/ahv-portal.conf`:

```ini
[program:ahv-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /home/deploy/portal/artisan queue:work --queue=whatsapp-webhook,whatsapp-high,whatsapp-send,whatsapp-media,whatsapp-reports,default --sleep=1 --tries=3 --max-time=3600 --timeout=120
autostart=true
autorestart=true
user=deploy
numprocs=2
redirect_stderr=true
stdout_logfile=/home/deploy/portal/storage/logs/worker.log
stopwaitsecs=130

[program:ahv-scheduler]
command=bash -c 'while true; do php /home/deploy/portal/artisan schedule:run --verbose --no-interaction; sleep 60; done'
autostart=true
autorestart=true
user=deploy
redirect_stderr=true
stdout_logfile=/home/deploy/portal/storage/logs/scheduler.log
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl status
```

> The `ahv-scheduler` loop replaces a system cron entry. If you prefer cron:
> `* * * * * cd /home/deploy/portal && php artisan schedule:run >> /dev/null 2>&1`
> in `deploy`'s crontab.

---

## 9. Meta webhook (stable URL at last)

Meta ▸ App Dashboard ▸ **WhatsApp → Configuration → Webhook → Edit**:

| Field | Value |
|---|---|
| Callback URL | `https://wp-ahv.ahvsoftware.com/api/webhooks/whatsapp` |
| Verify token | the `WABA_WEBHOOK_VERIFY_TOKEN` from your prod `.env` |

**Verify and save** → green tick. Then **Webhook fields → Manage** → subscribe
**`messages`** and **`message_template_status_update`**.

No more tunnel. This URL doesn't change.

---

## 10. Post-deploy verification

- [ ] `https://wp-ahv.ahvsoftware.com/login` loads over HTTPS, no mixed content.
- [ ] Log in as `admin@ahv.test`, **change the password**, then create real users
      via the seeder / an admin flow.
- [ ] `Settings` → **Run connection checks** → all 6 pass.
- [ ] `Templates` → **Sync from Meta** → templates appear.
- [ ] Send a message to the WABA number from a personal WhatsApp →
      **Admin → Webhook Events** shows it (`inbound: text`, signature `valid`,
      status `processed`).
- [ ] **Test Send** a template → **Messages** shows `sent → delivered → read`.
- [ ] `curl -s https://wp-ahv.ahvsoftware.com/health` → `{"status":"ok"...}`.
- [ ] `supervisorctl status` → both programs `RUNNING`.
- [ ] Trigger a deliberate 404 → generic page, **no stack trace** (confirms
      `APP_DEBUG=false`).

---

## 11. Optional: Redis (recommended once traffic grows)

```bash
# already installed in §2
nano .env
```
```dotenv
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```
```bash
php artisan config:cache && supervisorctl restart all
```

Horizon (queue dashboard + metrics) is documented for later — it is **not**
wired yet. Keep the plain `queue:work` above until then.

---

## 12. Deploying updates

```bash
su - deploy && cd /home/deploy/portal
php artisan down --render="errors::503"
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
supervisorctl restart ahv-queue:*
php artisan up
```

The Vite assets carry `data-turbo-track="reload"` — open browser tabs pick up the
new build automatically on the next navigation.

---

## 13. Backups & logs

- **DB:** nightly `mysqldump` to an off-box location (Hostinger snapshots are a
  fallback, not a substitute). Example cron for `deploy`:
  ```
  15 2 * * * mysqldump -u ahv_waba -p'...' ahv_waba | gzip > /home/deploy/backups/ahv_waba-$(date +\%F).sql.gz && find /home/deploy/backups -mtime +14 -delete
  ```
- **Restore drill:** actually restore a dump into a scratch DB once — an untested
  backup is not a backup.
- **Logs:** `storage/logs/laravel.log` (IST timestamps), `storage/logs/whatsapp-*.log`
  (redacted, 30-day rotation), `worker.log`, `scheduler.log`, Nginx
  `/var/log/nginx/`. Consider `logrotate` for `laravel.log`.
- **Media:** on `local` disk under `storage/app/private/media` — include in backups,
  or switch `WABA_MEDIA_DISK=r2` and fill `R2_*` so it lives in Cloudflare R2.

---

## 14. Security checklist for this deploy (from `tests/AHV_Security_Review_Result.md`)

- [x] **H-1** fresh `APP_KEY` (§4), `.env.example` no longer ships a real key.
- [x] **M-1** `TRUSTED_PROXIES=127.0.0.1`, not `*` (§4).
- [x] **L-2** `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true` (§4).
- [x] **L-1** CSV exports formula-escaped (in code).
- [x] **L-3** password routes throttled (in code).
- [ ] **WABA token rotated** in Meta before go-live.
- [ ] Admin default password changed on first login.
- [ ] DB user is least-privilege, not root (§2).
- [ ] `/storage/`, dotfiles, `.env`, `*.log` denied by Nginx (§6).
- [ ] **Do NOT set `TENANT_MODE=multi`** — findings M-2 / M-3 must be fixed first.
- [ ] `config/cors.php` still framework-default — fine while only the webhook lives
      under `/api`; publish + restrict before adding any authenticated API.
```
