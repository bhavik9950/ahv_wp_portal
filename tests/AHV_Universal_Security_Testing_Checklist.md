# AH&V Universal Security Testing Checklist

## Scope
Use this checklist for every authorized AH&V project: web frontend, Laravel/PHP backend, REST APIs, admin/staff/POS panels, PWA, Android/iOS apps, databases, storage, CI/CD and infrastructure.

> **Authorization:** Test only systems owned by AH&V/client or explicitly authorized. Prefer local/staging. Production testing must be controlled and non-destructive. Never perform DoS/DDoS, destructive actions, real-payment manipulation, mass brute force, or data deletion.

---

## 1. Project Inventory
- [ ] Domains/subdomains
- [ ] Frontend
- [ ] Backend/API
- [ ] Admin/staff/POS
- [ ] Mobile Android/iOS
- [ ] Database
- [ ] Storage/CDN
- [ ] Webhooks
- [ ] Payment gateways
- [ ] Third-party integrations
- [ ] Staging/local environment
- [ ] User roles
- [ ] Sensitive data
- [ ] Deprecated/old API versions
- [ ] Debug/test endpoints

---

# 2. Frontend Security

## Secrets & exposure
- [ ] No API secrets/private keys in JS bundles
- [ ] No DB/SMTP/payment secrets in frontend
- [ ] No `.env`, backups or config files publicly accessible
- [ ] No source maps exposing sensitive source in production
- [ ] No sensitive data in HTML comments/local JS
- [ ] No internal server paths unnecessarily exposed

## Client-side controls
- [ ] Backend does not trust hidden buttons/UI restrictions
- [ ] Role restrictions are enforced by backend
- [ ] Client-side validation is not the only validation
- [ ] User cannot alter frontend state to gain privileges

## XSS
Test authorized user-controlled fields:
- [ ] Search
- [ ] Names/profile fields
- [ ] Comments/reviews
- [ ] Complaint descriptions
- [ ] Product descriptions
- [ ] Rich text
- [ ] URL/query parameters
- [ ] Stored XSS
- [ ] Reflected XSS
- [ ] DOM XSS
- [ ] HTML injection

## Frontend dependencies
- [ ] npm/dependency audit
- [ ] Outdated/vulnerable packages reviewed
- [ ] Lock file present
- [ ] Dev/debug dependencies not exposed in production

---

# 3. Backend & API Security

## Route inventory
- [ ] GET/POST/PUT/PATCH/DELETE routes reviewed
- [ ] API versions reviewed
- [ ] Undocumented endpoints reviewed
- [ ] Deprecated endpoints removed/protected
- [ ] Admin/internal endpoints protected
- [ ] Webhooks identified

## Authentication
- [ ] Login
- [ ] Registration
- [ ] Logout
- [ ] Password reset
- [ ] Email verification
- [ ] OTP
- [ ] MFA
- [ ] Google/Apple/OAuth
- [ ] Token refresh
- [ ] Session expiration
- [ ] Brute-force/rate limiting
- [ ] No unsafe user enumeration
- [ ] Reset tokens expire and are single-use
- [ ] OTP expires and has attempt limits
- [ ] Authentication cannot be bypassed by modifying requests

---

# 4. Authorization / RBAC / IDOR / BOLA

**Treat this as a top-priority test for every API.**

Create authorized test accounts such as:
- Citizen A / Citizen B
- Shop Owner A / Shop Owner B
- Staff
- Manager
- Admin

Test:
- [ ] A cannot read B's records
- [ ] A cannot update B's records
- [ ] A cannot delete B's records
- [ ] A cannot download B's files
- [ ] Tenant/shop/organization isolation works
- [ ] Staff cannot call manager/admin endpoints
- [ ] Manager cannot call admin-only endpoints
- [ ] Normal user cannot call admin APIs
- [ ] Changing IDs does not expose another user's object
- [ ] HTTP method changes do not bypass authorization
- [ ] Direct API calls are protected even when UI hides the feature
- [ ] Mass assignment cannot set privileged fields
- [ ] Ownership is checked on every sensitive operation

Check objects such as:
`users/{id}`, `orders/{id}`, `complaints/{id}`, `shops/{id}`, `products/{id}`, `payments/{id}`, `files/{id}`, `reports/{id}`.

Look for:
- [ ] IDOR
- [ ] BOLA
- [ ] Broken Function Level Authorization
- [ ] Horizontal privilege escalation
- [ ] Vertical privilege escalation

---

# 5. Input Validation

For every API input:
- [ ] Required fields validated server-side
- [ ] Data types validated
- [ ] Length limits
- [ ] Numeric/range limits
- [ ] Enum allowlists
- [ ] Unexpected fields rejected/ignored safely
- [ ] Null/empty values handled
- [ ] Unicode/special characters handled
- [ ] Large request bodies limited
- [ ] Nested JSON validated
- [ ] Arrays/pagination have limits

---

# 6. Injection Testing

Only in authorized environments and with safe/non-destructive tests.

## SQL injection
- [ ] Login
- [ ] Search
- [ ] Filters
- [ ] IDs
- [ ] Sort fields
- [ ] Reports
- [ ] JSON fields
- [ ] Raw SQL reviewed
- [ ] Dynamic column/order values are allowlisted
- [ ] Parameterized queries/query builder used

## Other injection where applicable
- [ ] NoSQL injection
- [ ] Command injection
- [ ] Template injection
- [ ] LDAP injection
- [ ] XPath/XML injection
- [ ] Unsafe deserialization

---

# 7. File Uploads & Downloads

- [ ] Authentication required where appropriate
- [ ] Authorization checked
- [ ] Extension allowlist
- [ ] MIME/content validation
- [ ] File size/count limits
- [ ] Filename sanitization
- [ ] Path traversal protection
- [ ] Uploaded files cannot execute server-side code
- [ ] Private files are not publicly accessible
- [ ] Download authorization checked
- [ ] SVG/HTML uploads handled safely
- [ ] Image/document processing libraries updated
- [ ] Malware scanning considered for high-risk documents

Test safely for:
- [ ] Path traversal
- [ ] Extension bypass
- [ ] MIME mismatch
- [ ] Unauthorized download

Never upload actual malware.

---

# 8. Path Traversal / Local File Access
- [ ] `../` traversal blocked
- [ ] Encoded traversal blocked
- [ ] Absolute paths rejected
- [ ] Download endpoints enforce ownership
- [ ] `.env` not downloadable
- [ ] Logs/config/source/keys not downloadable
- [ ] Backup files not web-accessible

---

# 9. Business Logic

Do not limit testing to scanners.

- [ ] Operations cannot be repeated when they should be one-time
- [ ] Workflow order cannot be bypassed
- [ ] Cancelled/completed records cannot be improperly modified
- [ ] Approval workflows cannot be bypassed
- [ ] Limits cannot be bypassed
- [ ] Negative quantities/amounts rejected
- [ ] Duplicate transactions prevented
- [ ] Server calculates trusted prices/amounts
- [ ] User cannot change role/status/owner fields
- [ ] Sensitive state transitions are server-controlled

---

# 10. Payment Security

For Stripe/PayPal/etc.:
- [ ] Secret keys only on backend
- [ ] Client cannot set trusted payment status
- [ ] Payment response verified server-side
- [ ] Webhook signature verified
- [ ] Webhooks are idempotent
- [ ] Duplicate webhook cannot duplicate order/payment
- [ ] Amount calculated server-side
- [ ] Currency validated
- [ ] Order ownership validated
- [ ] Refund permissions protected
- [ ] Failed payment cannot become successful through request editing
- [ ] Expired/cancelled payment cannot complete order
- [ ] Sandbox/test environment used for manipulation testing
- [ ] Card/CVV data is not unnecessarily stored
- [ ] Payment secrets/data are not logged

**Never manipulate real payments in production.**

---

# 11. Rate Limiting & Abuse

Check:
- [ ] Login
- [ ] OTP
- [ ] Password reset
- [ ] Registration
- [ ] Search
- [ ] Upload
- [ ] Notifications
- [ ] Expensive reports
- [ ] Payment creation
- [ ] Public APIs

Verify:
- [ ] Rate limits exist
- [ ] Limits are appropriate
- [ ] Server enforces limits
- [ ] Alternate endpoints cannot trivially bypass limits
- [ ] Pagination/page size is bounded
- [ ] Request body size is bounded

No high-volume/DoS testing against production.

---

# 12. CORS / CSRF / Sessions

## CORS
- [ ] Only required origins allowed
- [ ] No unnecessary wildcard
- [ ] Credentials + wildcard configuration avoided
- [ ] Methods/headers restricted
- [ ] Staging/internal origins not allowed in production

## CSRF
For cookie-authenticated web apps:
- [ ] State-changing requests protected
- [ ] Tokens validated
- [ ] SameSite reviewed

## Sessions/tokens
- [ ] Secure cookies
- [ ] HttpOnly where appropriate
- [ ] SameSite appropriate
- [ ] Session expiry
- [ ] Logout invalidation
- [ ] Session rotation where appropriate
- [ ] Password change invalidates old sessions where appropriate
- [ ] JWT signature/expiry/claims validated
- [ ] Tokens not exposed in URLs/logs

---

# 13. Security Headers & HTTPS

- [ ] Content-Security-Policy
- [ ] Strict-Transport-Security
- [ ] X-Content-Type-Options
- [ ] Referrer-Policy
- [ ] Permissions-Policy
- [ ] Frame protections / CSP frame-ancestors
- [ ] Cache-Control for sensitive pages
- [ ] HTTPS everywhere appropriate
- [ ] HTTP redirects correctly
- [ ] No mixed content
- [ ] Secure cookies
- [ ] TLS/certificate configuration reviewed

---

# 14. Error Handling

Trigger controlled invalid requests:
- [ ] No stack traces in production
- [ ] No SQL queries exposed
- [ ] No filesystem paths
- [ ] No environment variables
- [ ] No internal credentials
- [ ] No debug pages
- [ ] Safe generic responses
- [ ] Detailed errors only in protected logs

Laravel:
- [ ] `APP_DEBUG=false` in production
- [ ] `.env` inaccessible
- [ ] Logs inaccessible publicly

---

# 15. Secrets & Configuration

Search repository/build artifacts for:
- [ ] API keys
- [ ] Private keys
- [ ] JWT secrets
- [ ] DB passwords
- [ ] SMTP passwords
- [ ] Cloud/R2/S3 credentials
- [ ] Payment secret keys
- [ ] OAuth secrets
- [ ] Firebase/service-account credentials
- [ ] SSH private keys
- [ ] `.env`
- [ ] Backups

- [ ] Secrets not committed to Git
- [ ] Leaked secrets rotated
- [ ] Dev/prod secrets separated
- [ ] Least privilege

---

# 16. Database

- [ ] DB not publicly exposed unnecessarily
- [ ] Application DB user has least privilege
- [ ] Root DB account not used by app
- [ ] Strong credentials
- [ ] TLS where appropriate
- [ ] Backups protected
- [ ] Backup restore tested
- [ ] Sensitive fields protected
- [ ] SQL injection reviewed
- [ ] DB errors hidden

---

# 17. Laravel Security

- [ ] `APP_DEBUG=false`
- [ ] `.env` protected
- [ ] CSRF configured
- [ ] Policies/Gates reviewed
- [ ] Form Requests/server-side validation
- [ ] `$fillable`/`$guarded` reviewed
- [ ] Raw SQL/`DB::raw()` reviewed
- [ ] Upload validation
- [ ] Storage/public files reviewed
- [ ] Signed URLs protected
- [ ] Rate limiting
- [ ] Sanctum/Passport configuration
- [ ] Queues/scheduler protected
- [ ] Telescope/Horizon/dev tools protected
- [ ] Logs protected
- [ ] Dependencies audited

---

# 18. Admin / Staff / POS

- [ ] Admin authentication
- [ ] Staff authentication
- [ ] Role-based permissions
- [ ] Price changes restricted
- [ ] Discounts restricted
- [ ] Refunds restricted
- [ ] Payment status restricted
- [ ] Order deletion restricted
- [ ] Reports/exports restricted
- [ ] Customer data restricted
- [ ] Inventory restricted
- [ ] Audit logs for sensitive actions
- [ ] Idle session timeout considered
- [ ] Direct API access tested separately from UI

---

# 19. GMS / VMS / DMS / Government Projects

- [ ] Citizen cannot access another citizen's record
- [ ] Complaint ownership enforced
- [ ] Citizen cannot alter official status
- [ ] Citizen cannot assign complaint
- [ ] Staff jurisdiction/department restrictions enforced
- [ ] Admin-only operations protected
- [ ] Attachments private where required
- [ ] Personal data minimized/protected
- [ ] Search does not leak records
- [ ] Export/report authorization
- [ ] Audit trail
- [ ] OTP/verification protection
- [ ] Public tracking exposes minimum necessary information
- [ ] Bulk APIs protected

---

# 20. Mobile App — Android/iOS

## Local storage
- [ ] No passwords stored
- [ ] No API secrets
- [ ] Tokens stored using secure platform storage
- [ ] Sensitive SQLite/preferences data protected
- [ ] Sensitive logs removed
- [ ] Clipboard/screenshot exposure considered

## Network
- [ ] HTTPS only
- [ ] Certificate validation
- [ ] No plaintext production API
- [ ] Debug network settings disabled
- [ ] Tokens protected
- [ ] Logout tested

## Android
- [ ] Release build tested
- [ ] Debuggable disabled
- [ ] Backup configuration reviewed
- [ ] Exported components reviewed
- [ ] Deep links/app links reviewed
- [ ] WebView reviewed
- [ ] JavaScript/file access only when necessary
- [ ] Intent inputs validated
- [ ] Sensitive data not exposed through intents
- [ ] Signing/keystore secrets protected

## iOS
- [ ] Release configuration
- [ ] ATS reviewed
- [ ] Keychain for sensitive credentials
- [ ] URL schemes/deep links reviewed
- [ ] Universal Links reviewed
- [ ] WebView reviewed
- [ ] Sensitive logs removed
- [ ] Signing/provisioning secrets protected

---

# 21. Mobile → API Testing

**The mobile app itself is not the security boundary.**

Use an authorized test account and inspect API traffic:
- [ ] Change user/object IDs
- [ ] Remove authorization header
- [ ] Expired token
- [ ] Another authorized test user's token
- [ ] Change role fields
- [ ] Add unexpected fields
- [ ] Replay safe requests
- [ ] Call API directly without UI
- [ ] Verify server-side authorization

---

# 22. Multi-Tenant Security

For SaaS/multi-shop/organization systems:
- [ ] Tenant determined securely
- [ ] User cannot switch tenant via request parameter
- [ ] Every query enforces tenant isolation
- [ ] Reports isolated
- [ ] Files isolated
- [ ] Notifications isolated
- [ ] Background jobs isolated
- [ ] Exports isolated
- [ ] Admin APIs respect tenant boundaries

---

# 23. WebSocket / Notifications / PWA

## WebSocket
- [ ] Authentication
- [ ] Channel authorization
- [ ] Private channels cannot be subscribed to by other users
- [ ] Sensitive events not broadcast publicly
- [ ] Resource/connection limits

## Notifications
- [ ] Sending permission enforced
- [ ] Rate limiting
- [ ] User cannot impersonate another sender
- [ ] Sensitive data minimized
- [ ] Provider credentials backend-only

## PWA
- [ ] Service worker does not cache private authenticated responses
- [ ] Cache does not mix users
- [ ] Offline storage reviewed
- [ ] Push subscriptions authorized
- [ ] Old caches invalidated appropriately

---

# 24. SSRF / Redirects / XML

If applicable:
- [ ] User-provided URL fetching restricted
- [ ] Internal/private addresses protected
- [ ] Cloud metadata access protected
- [ ] Redirect destinations validated
- [ ] Allowed protocols restricted
- [ ] Open redirects prevented
- [ ] XML external entities disabled
- [ ] Unsafe deserialization avoided

---

# 25. Caching & Sensitive Data

- [ ] Private responses not publicly cached
- [ ] CDN does not cache private user data
- [ ] Shared cache cannot mix users
- [ ] Private files are protected
- [ ] Search/API responses expose minimum fields

---

# 26. Search / Pagination / Reports

- [ ] Authorization applies to search
- [ ] Pagination limits
- [ ] Maximum page size
- [ ] Sorting fields allowlisted
- [ ] Filters cannot bypass tenant/ownership rules
- [ ] Expensive queries protected
- [ ] Reports require appropriate permissions
- [ ] Exports require appropriate permissions

---

# 27. Queues / Cron / Background Jobs

- [ ] Sensitive jobs cannot be triggered by unauthorized users
- [ ] Job payloads avoid secrets
- [ ] Job ownership/authorization validated
- [ ] Duplicate jobs handled safely
- [ ] Scheduler endpoints protected
- [ ] Queue dashboards protected
- [ ] Failed jobs do not expose secrets

---

# 28. Logging / Audit

- [ ] Authentication events
- [ ] Privilege changes
- [ ] Payment/refund actions
- [ ] Admin changes
- [ ] Sensitive actions
- [ ] Passwords never logged
- [ ] Tokens never logged
- [ ] Sensitive personal data minimized
- [ ] Logs protected
- [ ] Audit logs cannot be modified by normal users
- [ ] Retention appropriate

---

# 29. Dependencies / Supply Chain

Backend:
- [ ] `composer audit`
- [ ] PHP supported version
- [ ] Laravel supported version
- [ ] Vulnerable dependencies reviewed

Frontend:
- [ ] `npm audit`
- [ ] Lock file
- [ ] Vulnerable dependencies reviewed

Flutter/mobile:
- [ ] Dart/Flutter dependencies reviewed
- [ ] Android dependencies reviewed
- [ ] iOS dependencies reviewed

- [ ] Unused dependencies removed
- [ ] Upgrades regression-tested

---

# 30. Git / CI/CD

- [ ] `.env` not committed
- [ ] Private keys not committed
- [ ] DB dumps not committed
- [ ] Secrets not in Git history
- [ ] GitHub/GitLab access reviewed
- [ ] Deploy keys reviewed
- [ ] CI/CD secrets stored securely
- [ ] Build logs do not print secrets
- [ ] Production deployment permissions restricted
- [ ] Dependency/security scanning considered

---

# 31. Server / CloudPanel / Infrastructure

- [ ] OS updated
- [ ] PHP/runtime updated
- [ ] Web server updated
- [ ] DB updated
- [ ] Firewall configured
- [ ] Only required ports exposed
- [ ] SSH secured
- [ ] Root/password SSH restricted appropriately
- [ ] SSH private keys protected
- [ ] CloudPanel/admin panel protected
- [ ] Admin interfaces restricted
- [ ] Backups configured
- [ ] Restore tested
- [ ] File permissions reviewed
- [ ] Web root does not expose application internals
- [ ] Logs/storage protected
- [ ] Monitoring/alerts configured

---

# 32. Cloud Storage / R2 / S3

- [ ] Buckets private unless intentionally public
- [ ] Least-privilege access keys
- [ ] Public listing disabled
- [ ] Private files require authorization
- [ ] Signed URLs expire
- [ ] Signed URLs cannot access unrelated objects
- [ ] Credentials never in frontend
- [ ] CORS reviewed

---

# 33. Automated Security Checks

Run appropriate checks in authorized environments:
- [ ] Composer audit
- [ ] npm audit
- [ ] Secret scanning
- [ ] SAST
- [ ] DAST
- [ ] Container scanning if applicable
- [ ] Infrastructure scanning
- [ ] TLS review

Automated scanners do NOT replace authorization/business-logic testing.

---

# 34. Burp Suite Manual Testing

Use Burp only against authorized targets.

## Proxy
- [ ] Intercept login
- [ ] Inspect requests/responses
- [ ] Identify APIs
- [ ] Identify cookies/tokens
- [ ] Identify hidden parameters

## Repeater
- [ ] Change object IDs
- [ ] Test ownership boundaries
- [ ] Remove/alter auth headers
- [ ] Change HTTP methods
- [ ] Modify JSON fields
- [ ] Test validation
- [ ] Test role/status/price controls in staging

## Intruder
- [ ] Small controlled input-boundary tests
- [ ] Low-volume rate-limit verification

Never use Burp for production DoS, real payment manipulation, destructive actions, or mass data extraction.

---

# 35. Finding Severity

## Critical
- Remote code execution
- Major authentication bypass
- Full admin takeover
- Large-scale sensitive-data access
- Major payment bypass

## High
- IDOR/BOLA exposing sensitive records
- Privilege escalation
- Account takeover
- SQL injection
- Significant stored XSS
- Unauthorized refund/payment manipulation
- Private files accessible without authorization

## Medium
- Limited data exposure
- Weak rate limiting on lower-risk endpoints
- Important misconfiguration
- Limited-impact CSRF

## Low/Info
- Minor information disclosure
- Defensive header improvement
- Low practical impact configuration issue

---

# 36. Evidence Template

For every finding:

```text
Finding ID:
Title:
Severity:
Affected Project:
Affected URL/API:
HTTP Method:
Affected Role:
Prerequisites:
Steps to Reproduce:
Expected Result:
Actual Result:
Security Impact:
Business Impact:
Evidence:
Request:
Response:
Recommended Fix:
Status:
Retest Result:
```

Redact passwords, tokens, API keys, payment data and personal information.

---

# 37. Final Release Gate

- [ ] No known Critical vulnerabilities
- [ ] High vulnerabilities fixed or formally accepted
- [ ] Authentication tested
- [ ] Authorization tested
- [ ] IDOR/BOLA tested
- [ ] API tested
- [ ] Input validation tested
- [ ] File uploads tested
- [ ] Business logic tested
- [ ] Payment sandbox tested where applicable
- [ ] Frontend reviewed
- [ ] Mobile reviewed where applicable
- [ ] Secrets reviewed
- [ ] Dependencies reviewed
- [ ] Production configuration reviewed
- [ ] Admin/POS permissions reviewed
- [ ] Logging/audit reviewed
- [ ] Fixes retested

---

# 38. Codex Security Review Prompt

Copy this prompt into Codex for an authorized project:

```text
Perform an AUTHORIZED internal security review of this project.

IMPORTANT SAFETY RULES:
- Inspect and report first; do not automatically modify application code.
- Do not delete data.
- Do not perform destructive actions.
- Do not perform DoS/DDoS.
- Do not perform high-volume brute force.
- Do not manipulate real production payments.
- Do not mass-download real user data.
- Do not expose secrets in your output.
- Redact credentials, tokens, personal data and payment information.
- Prefer local/staging testing.
- If production, use only controlled non-destructive verification.

PROJECT:
[PROJECT NAME]

ENVIRONMENT:
[LOCAL / STAGING / PRODUCTION]

STACK:
[Laravel/PHP/Flutter/etc.]

Perform a comprehensive review of:

1. Frontend security
2. Backend/API security
3. Authentication
4. Authorization/RBAC
5. IDOR/BOLA
6. Privilege escalation
7. Input validation
8. SQL/NoSQL/command/template injection
9. XSS
10. CSRF
11. Session/JWT/token security
12. File uploads/downloads
13. Path traversal
14. SSRF
15. CORS
16. Security headers
17. Rate limiting
18. Business logic
19. Payment logic/webhooks if present
20. Sensitive-data exposure
21. Secrets/configuration
22. Laravel-specific security
23. Database security
24. Storage/CDN security
25. Admin/POS permissions
26. Multi-tenant isolation
27. WebSocket/realtime features
28. Notifications
29. PWA/service worker
30. Android/iOS security if mobile app exists
31. Mobile-to-API security
32. Dependency vulnerabilities
33. Git/CI/CD secrets
34. Server/deployment configuration
35. Logging/auditing
36. Backup security
37. OWASP web/API risks

Pay SPECIAL attention to:
- object ownership
- role boundaries
- user-controlled IDs
- mass assignment
- price/status/role manipulation
- payment verification
- webhook signatures/idempotency
- private file access
- tenant isolation
- sensitive data exposure
- endpoints that are hidden in the UI but not protected by backend

For every finding report:
- ID
- Title
- Severity
- Component
- File/route/function
- Why vulnerable
- Security impact
- Business impact
- Safe reproduction steps
- Evidence
- Recommended remediation
- Frontend/backend/mobile/infrastructure classification

Also report:
A. Executive summary
B. Critical/High findings
C. Medium/Low findings
D. Existing security controls
E. Missing controls
F. Prioritized remediation plan
G. Security release recommendation
H. Files/routes/functions reviewed
I. Tests that still require manual Burp Suite verification
J. Testing limitations

Do not claim the project is secure merely because no issue was found.
```

---

# 39. Recommended Workflow

```text
Scope & authorization
        ↓
Asset inventory
        ↓
Frontend review
        ↓
Authentication
        ↓
Authorization / IDOR / BOLA
        ↓
API testing
        ↓
Input & injection testing
        ↓
File upload testing
        ↓
Business logic
        ↓
Payment sandbox
        ↓
Mobile testing
        ↓
Secrets/configuration
        ↓
Dependencies
        ↓
Infrastructure
        ↓
Codex/static/automated review
        ↓
Burp manual verification
        ↓
Fix
        ↓
Retest
        ↓
Final security report
```

## Core rule

**Frontend is untrusted. Mobile app is untrusted. Client-side validation is untrusted.**

The backend must enforce:
- Authentication
- Authorization
- Ownership
- Role permissions
- Trusted prices/statuses
- Payment verification
- File access
- Tenant isolation

A secure UI does not guarantee a secure API, and a secure mobile app does not guarantee a secure backend.
