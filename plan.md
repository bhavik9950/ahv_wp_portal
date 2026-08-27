# AH&V Software — WhatsApp / WABA Management Portal

## Objective

Build a production-ready, secure, multi-tenant WhatsApp Business Platform for AH&V Software using the official Meta WhatsApp Cloud API.

The portal should allow AH&V/admin users and authorized business clients to manage their WhatsApp Business communication from one dashboard.

The system must NOT attempt to bypass Meta's WhatsApp policies, template approval requirements, messaging restrictions, rate limits, opt-in requirements, or anti-spam mechanisms.

Use only official Meta WhatsApp Cloud API endpoints and webhook mechanisms.

---

# 1. Recommended Technology Stack

Use the following architecture unless there is a strong technical reason to change it:

### Backend

* Laravel 12+
* PHP 8.3+
* REST API
* MySQL 8+
* Redis
* Laravel Queue / Horizon
* Laravel Scheduler
* Laravel Notifications where appropriate
* Laravel Sanctum for internal API authentication if required

### Frontend

Use the existing AH&V project frontend technology if available.

Otherwise:

* Laravel Blade + Livewire/Alpine OR
* Vue 3

Prefer consistency with the existing AH&V ecosystem instead of introducing unnecessary technologies.

### Infrastructure

* Nginx
* PHP-FPM
* Redis
* MySQL
* Supervisor/Horizon workers
* HTTPS mandatory
* Cloudflare-compatible deployment

---

# 2. IMPORTANT SECURITY REQUIREMENT

NEVER hard-code:

* WABA access tokens
* App secrets
* webhook secrets
* database passwords
* encryption keys
* API keys

All secrets must come from environment variables or a secure secrets manager.

The following credentials may exist in the development environment, but DO NOT copy them into source code, Git, database seeders, documentation, frontend JavaScript, logs, screenshots, or API responses.

Existing environment variables conceptually include:

WABA_DRIVER
WABA_BASE_URL
WABA_API_VERSION
WABA_ACCESS_TOKEN
WABA_PHONE_NUMBER_ID
WABA_DEFAULT_COUNTRY_CODE
WABA_USE_TEMPLATES
WABA_TEMPLATE_LANGUAGE
WABA_TEMPLATE_ORDER_DISPATCHED
WABA_TEMPLATE_ORDER_READY
WABA_TEMPLATE_ORDER_READY_PAYMENT_DUE
WABA_TEMPLATE_ORDER_DELAYED
WABA_TEMPLATE_ORDER_DELIVERED
WABA_WEBHOOK_VERIFY_TOKEN
WABA_APP_SECRET
WABA_LOG_CHANNEL

The currently exposed WABA access token MUST be considered compromised and rotated/revoked before production deployment.

---

# 3. Multi-Tenant Architecture

Design the application so AH&V can eventually provide WhatsApp services to multiple businesses.

Suggested structure:

users
organizations
organization_users
whatsapp_business_accounts
whatsapp_phone_numbers
whatsapp_templates
contacts
contact_groups
campaigns
campaign_recipients
messages
message_status_events
media
webhook_events
api_logs
audit_logs
opt_in_records
scheduled_messages

Every business-owned resource must have organization_id.

Implement strict tenant isolation.

A user belonging to Organization A must never be able to access:

* Organization B contacts
* Organization B campaigns
* Organization B messages
* Organization B WABA credentials
* Organization B reports

---

# 4. User Roles

Create RBAC.

Suggested roles:

### Super Admin

Full platform access.

### Organization Admin

Manage their organization, WhatsApp account, templates, contacts, campaigns and reports.

### Campaign Manager

Create and manage campaigns but cannot modify WABA credentials.

### Support Agent

Send individual messages and view conversations where permitted.

### Viewer

Read-only access to reports and messages.

Implement permission checks server-side.

Never rely only on frontend role restrictions.

---

# 5. WhatsApp Business Account Configuration

Create a secure WABA configuration section.

Allow authorized administrators to configure:

* Meta Business Account ID
* WhatsApp Business Account ID
* Phone Number ID
* API version
* Access token
* App ID if required
* App secret
* Webhook verify token
* Default country code

Sensitive credentials must be encrypted at rest.

Never return full access tokens through API responses.

Show masked credentials such as:

********abcd

Provide:

* Test Connection
* Validate Phone Number
* Validate WABA
* Check API permissions
* Check token expiry if available
* Check webhook configuration

---

# 6. Dashboard

Create a professional SaaS-style dashboard.

Show:

* Total messages
* Sent
* Delivered
* Read
* Failed
* Pending
* Scheduled
* Active campaigns
* Contacts
* Opted-in contacts
* Template statistics
* Delivery rate
* Read rate
* Failure rate

Add date filters:

* Today
* Yesterday
* Last 7 days
* Last 30 days
* Custom range

Charts should show message trends.

---

# 7. Template Management

Build a WhatsApp Template Management module.

Users should be able to:

* View templates
* Search templates
* Filter by status
* Filter by category
* Create template
* Edit where Meta permits
* Submit template to Meta
* Refresh template status
* View rejection reason
* Delete template where Meta permits

Template categories should support Meta-supported categories.

Template builder should support:

* Header
* Body
* Footer
* Buttons
* Variables
* Media header where supported

Example:

Hello {{1}}, your order {{2}} has been dispatched.

Provide a variable mapping UI.

Before submission:

* Validate template syntax
* Validate variables
* Validate button configuration
* Validate required fields
* Warn about potentially invalid content
* Show preview

Important:

Do NOT implement any mechanism that attempts to bypass Meta template approval.

Only approved templates should be available for applicable outbound business-initiated messages.

---

# 8. Template Status Synchronization

Synchronize template status from Meta.

Possible states should be represented according to Meta's current API response, for example:

* PENDING
* APPROVED
* REJECTED
* PAUSED
* DISABLED
* UNKNOWN

Do not hard-code assumptions if Meta introduces additional states.

Store Meta's raw status safely for troubleshooting.

---

# 9. Contact Management

Build a complete contact module.

Fields:

* Name
* Country code
* Phone number
* Email
* Tags
* Groups
* Custom fields
* Opt-in status
* Opt-in timestamp
* Opt-in source
* Opt-out timestamp
* Last message
* Last message timestamp

Features:

* Add contact
* Edit contact
* Delete contact
* Import CSV
* Export permitted data
* Bulk tagging
* Group assignment
* Search
* Filters
* Duplicate detection
* Invalid number detection where possible

Normalize phone numbers to E.164 format.

For India, support +91 but do not hard-code India-only behavior.

---

# 10. Opt-In / Opt-Out

This is mandatory.

Maintain explicit WhatsApp opt-in records.

Store:

* phone number
* consent status
* consent timestamp
* consent source
* campaign/source
* proof/reference if available

Support:

OPT-IN
OPT-OUT

If a contact has opted out, the campaign engine must prevent marketing messages from being sent to that contact.

Provide an easy unsubscribe/opt-out mechanism wherever applicable.

Do not design the system to facilitate spam or unsolicited bulk messaging.

---

# 11. Campaign Management

Create a Bulk Messaging / Campaign module.

Users should be able to:

1. Create campaign
2. Select audience
3. Select approved template
4. Map variables
5. Add media where template supports it
6. Preview messages
7. Test send
8. Schedule campaign
9. Start campaign
10. Pause campaign
11. Resume campaign
12. Cancel campaign
13. View results

Campaign statuses:

* Draft
* Scheduled
* Processing
* Paused
* Completed
* Cancelled
* Failed

---

# 12. Bulk Messaging Engine

Do NOT send thousands of messages inside a normal HTTP request.

Use:

Laravel Queue
+
Redis
+
Horizon

Each recipient should become an independent queued job or controlled batch.

Example architecture:

Campaign
→ Campaign Recipients
→ Queue Jobs
→ Meta API
→ Webhook
→ Message Status Update

The system must survive worker restart without duplicating messages.

Implement idempotency.

Every outbound message should have an internal unique identifier.

Store Meta's message ID:

wamid

when returned.

---

# 13. Configurable Sending Delay / Throttling

The campaign UI may provide configurable throttling such as:

* No custom delay
* 1 second
* 2 seconds
* 3 seconds
* 5 seconds
* 10 seconds
* Custom delay

But this must NOT be used to bypass Meta rate limits or anti-spam systems.

The actual sending engine must always respect:

* Meta API rate limits
* Meta messaging limits
* Account quality
* Template restrictions
* Platform errors
* Retry-after headers where available

Do not promise a fixed messages-per-second rate.

Implement adaptive throttling.

If Meta starts returning rate-limit errors:

* slow down
* retry with backoff
* respect Retry-After if available
* record the event
* avoid aggressive retries

---

# 14. Retry Strategy

Implement safe retry handling.

Retry temporary errors using exponential backoff.

Example:

1st retry → 5 seconds
2nd retry → 30 seconds
3rd retry → 2 minutes
4th retry → 10 minutes

Do not endlessly retry permanent errors.

Classify:

Temporary
Permanent
Rate Limited
Authentication Failure
Invalid Recipient
Template Failure
Media Failure
Unknown

Move permanently failed jobs to a failed state.

---

# 15. Message Types

Support Meta-supported message types as appropriate:

* Text
* Template
* Image
* Video
* Document
* Audio
* Location
* Interactive messages where supported

Do not implement unsupported message types merely by guessing API payloads.

Keep the Meta API integration behind a service layer.

Example:

WhatsAppService

Methods conceptually:

sendText()
sendTemplate()
sendImage()
sendVideo()
sendDocument()
sendAudio()
sendLocation()
sendInteractive()

---

# 16. Media Handling

Build media support.

Allow authorized users to upload media where supported.

Store media securely.

Prefer object storage such as:

Cloudflare R2 / S3-compatible storage

Do not expose private storage credentials.

Validate:

* MIME type
* file size
* file extension
* actual file signature

Never trust only the filename extension.

Generate secure temporary URLs when required.

Do not allow arbitrary executable files.

---

# 17. Message Status Tracking

Use Meta Webhooks as the source of truth for delivery status.

Support statuses such as:

* Sent
* Delivered
* Read
* Failed

Also maintain:

* Pending
* Queued
* Processing
* Cancelled

Store status history rather than overwriting everything.

Example:

QUEUED
→ SENT
→ DELIVERED
→ READ

or:

QUEUED
→ SENT
→ FAILED

Store:

* message ID
* Meta WAMID
* recipient
* timestamp
* status
* error code
* error message
* raw webhook reference

---

# 18. Webhook System

Implement a secure Meta webhook endpoint.

Example:

/api/webhooks/whatsapp

Support:

GET verification request

POST webhook events

Verify webhook authenticity according to Meta's current documentation.

Validate:

* Verify token
* App secret / signature where applicable
* Request structure
* Event source

Never trust webhook payload blindly.

Webhook processing should be asynchronous.

Flow:

Meta
→ Webhook endpoint
→ Validate
→ Store raw event
→ Queue processing job
→ Update message/contact/campaign
→ Dashboard

Webhook endpoint should return quickly.

---

# 19. Webhook Idempotency

Meta may retry webhook events.

Therefore:

Create a webhook_events table.

Store an event fingerprint / unique event identifier where available.

Before processing:

if already processed:
return success

Otherwise:

store event
process event
mark processed

Never create duplicate message status records because Meta sent the same webhook twice.

---

# 20. Real-Time Dashboard

Where practical, use:

* Laravel broadcasting
* WebSockets / compatible realtime technology

to update:

Sent
Delivered
Read
Failed

without refreshing the page.

If realtime infrastructure is unnecessary initially, implement polling as a fallback.

---

# 21. Campaign Report

Each campaign should have a detailed report.

Show:

Total recipients
Queued
Sent
Delivered
Read
Failed
Skipped
Opted out
Rate limited

Metrics:

Delivery %
Read %
Failure %

Include recipient-level details.

Allow CSV export for authorized users.

Do not expose data across tenants.

---

# 22. Individual Message / Conversation View

Create a message viewer.

Show:

Contact
Phone
Campaign
Template
Message
Media
Sent time
Delivered time
Read time
Failure information

Display status timeline.

Example:

Queued
↓
Sent
↓
Delivered
↓
Read

---

# 23. Scheduling

Allow campaigns to be scheduled.

Examples:

Send today at 5:00 PM
Send tomorrow
Custom date/time

Use Laravel Scheduler / queued jobs.

Store timezone per organization.

Never assume server timezone.

---

# 24. Campaign Pause / Resume

Implement safe pause/resume.

When paused:

* Do not enqueue new recipients
* Existing in-flight API requests may complete
* Queued jobs should check campaign status before sending

When resumed:

* Continue only pending recipients
* Never resend recipients already successfully processed

---

# 25. Test Send

Every campaign must have:

"Send Test Message"

Allow admin to enter one or more test numbers.

Test messages should clearly indicate that they are test messages where appropriate.

Do not accidentally send the entire campaign during testing.

---

# 26. API Architecture

Create a dedicated Meta integration service.

Example structure:

app/
Services/
WhatsApp/
MetaWhatsAppService.php
WhatsAppTemplateService.php
WhatsAppWebhookService.php
WhatsAppMediaService.php
WhatsAppRateLimiter.php

Do not scatter Meta API calls across controllers.

Controllers should call service classes.

---

# 27. API Version Management

Do not hard-code Meta API version throughout the code.

Use:

WABA_API_VERSION

from configuration.

The system should make upgrading the Meta Graph API version easier.

Example:

config/services.php

'whatsapp' => [
'base_url' => env('WABA_BASE_URL'),
'api_version' => env('WABA_API_VERSION'),
]

---

# 28. Logging

Create structured logs.

Log:

* Request ID
* Organization ID
* User ID
* Campaign ID
* Message ID
* Meta WAMID
* API response status
* Error category
* Duration

NEVER log:

* Access tokens
* App secrets
* Authorization headers
* Full sensitive credentials

Mask phone numbers where appropriate in application logs.

Use a dedicated WhatsApp log channel.

---

# 29. Security Requirements

Implement at minimum:

* HTTPS
* CSRF protection
* Authentication
* RBAC
* Authorization policies
* Tenant isolation
* Rate limiting
* Input validation
* Output escaping
* SQL injection protection
* XSS protection
* SSRF protection
* File upload validation
* Mass assignment protection
* Secure headers
* CORS restrictions
* Secure cookies
* Session expiration
* Login throttling
* Audit logs

Use Laravel's built-in security mechanisms wherever possible.

---

# 30. SSRF Protection

This is especially important for media URLs.

Never blindly fetch arbitrary user-provided URLs from the server.

If the application downloads media from URLs:

* Validate scheme
* Restrict allowed domains where possible
* Block localhost
* Block private IP ranges
* Block metadata endpoints
* Prevent redirects to private networks
* Apply connection timeout
* Apply download size limits

---

# 31. Database Security

Use proper indexes.

Important indexes:

organization_id
campaign_id
message_id
wamid
phone_number
status
created_at
scheduled_at

Add composite indexes based on query patterns.

Use foreign keys where appropriate.

Use UUID/ULID if consistent with the existing AH&V architecture.

---

# 32. Audit Logs

Track security-sensitive actions:

* Login
* Logout
* Failed login
* WABA configuration change
* Token change
* Template creation
* Template submission
* Template deletion
* Campaign creation
* Campaign launch
* Campaign pause
* Campaign cancellation
* Contact import
* Contact export
* Role changes

Audit log should include:

User
Organization
Action
Resource
Timestamp
IP
User agent
Result

Never store secrets in audit logs.

---

# 33. Admin Emergency Controls

Super Admin should have:

* Disable organization
* Disable WhatsApp sending
* Pause all campaigns
* Revoke integration
* Disable phone number
* View API health
* View webhook health

Implement a global emergency kill switch:

WHATSAPP_SENDING_ENABLED

If disabled, no outbound jobs should call Meta.

---

# 34. Meta API Error Handling

Create a normalized internal error system.

Do not expose raw Meta API errors directly to normal users.

Instead:

Internal:

Meta error code 131xxx...

User-facing:

"WhatsApp rejected this message because the selected template is not available for this recipient."

For admins, provide detailed diagnostic information.

---

# 35. API Health Monitor

Dashboard should show:

WhatsApp API:
Connected / Error

Token:
Valid / Invalid / Expired if detectable

Phone:
Available / Error

Webhook:
Healthy / No recent events

Queue:
Healthy / Delayed

Redis:
Healthy / Error

Database:
Healthy / Error

---

# 36. Security Testing

Before production, perform security testing against the entire application.

Test:

* SQL injection
* XSS
* CSRF
* IDOR
* Broken access control
* Tenant isolation
* SSRF
* File upload attacks
* Path traversal
* Command injection
* Authentication bypass
* Session fixation
* Brute force
* API abuse
* Rate-limit bypass
* Mass assignment
* Sensitive data exposure
* Secret leakage
* Log leakage
* Webhook forgery
* Replay attacks
* Duplicate webhook processing
* Queue duplication
* Race conditions
* Privilege escalation

Pay special attention to IDOR.

Example:

Organization A must not be able to change:

/campaigns/{organization-B-campaign-id}

by simply changing the ID.

---

# 37. Load Testing

The system should be designed for large campaigns.

Do not assume that sending 10,000 messages means 10,000 HTTP requests from one web request.

Test:

* 10,000 recipients
* 50,000 recipients
* 100,000 recipients

with queue workers.

Measure:

* Queue throughput
* DB performance
* Redis performance
* API response time
* Webhook processing
* Memory usage
* Failed job rate

The application must remain responsive while campaigns are running.

---

# 38. Race Condition Testing

Test scenarios such as:

Two workers process the same recipient simultaneously.

Expected:

Only one outbound message should be sent.

Use:

* unique constraints
* atomic state transitions
* locks where appropriate
* idempotency keys

---

# 39. Data Retention

Design configurable retention policies.

Allow Super Admin to configure:

* Message retention
* Webhook retention
* Audit retention
* Campaign retention

Do not delete data required for compliance/audit without explicit configuration.

---

# 40. Privacy

Minimize personal data.

Provide:

* Data export where appropriate
* Data deletion where legally appropriate
* Access control
* Retention controls

Do not expose contact databases to unauthorized users.

---

# 41. UI Pages

Build the following navigation:

Dashboard

WhatsApp
├── Overview
├── Phone Numbers
├── Templates
├── Contacts
├── Groups
├── Campaigns
├── Messages
├── Media
├── Reports
├── Webhooks
└── Settings

Admin

├── Organizations
├── Users
├── Roles & Permissions
├── Audit Logs
├── System Health
└── System Settings

---

# 42. Campaign Creation UX

Create a multi-step campaign wizard:

Step 1:
Campaign Name

Step 2:
Audience

Step 3:
Template

Step 4:
Variables

Step 5:
Media

Step 6:
Preview

Step 7:
Test Send

Step 8:
Schedule / Send

Step 9:
Confirmation

Before launching:

Show:

Recipients:
10,240

Estimated processing:
Based on current platform/API limits

Template:
order_dispatched_update

Media:
Image

Send mode:
Throttled

Require explicit confirmation before launch.

---

# 43. CSV Import

Support CSV import.

Validate:

* Phone number
* Name
* Duplicate
* Invalid rows
* Missing required fields

Show import preview before committing.

Example:

Valid: 9,842
Invalid: 123
Duplicates: 35

Allow download of invalid rows.

Process large imports asynchronously.

Do not process a 100,000-row CSV inside one HTTP request.

---

# 44. Queue Architecture

Suggested queues:

whatsapp-high
whatsapp-send
whatsapp-webhook
whatsapp-media
whatsapp-reports

Use Horizon for monitoring.

Workers should be independently scalable.

---

# 45. Scheduled Campaign Architecture

Do not create one gigantic delayed job for a huge campaign.

Use:

Campaign Scheduler
→ identify due campaign
→ create/dispatch controlled batches
→ queue recipient jobs

This allows pause/resume/cancellation.

---

# 46. Idempotency

Every outbound operation must have an internal idempotency key.

Example:

organization_id + campaign_id + recipient_id

Before sending:

Check whether successful outbound message already exists.

If yes:

do not send again.

This is extremely important for queue retries.

---

# 47. Environment Configuration

Create:

.env.example

with placeholders only.

Example:

WABA_DRIVER=mock
WABA_BASE_URL=https://graph.facebook.com
WABA_API_VERSION=
WABA_ACCESS_TOKEN=
WABA_PHONE_NUMBER_ID=
WABA_DEFAULT_COUNTRY_CODE=91
WABA_USE_TEMPLATES=true
WABA_TEMPLATE_LANGUAGE=en
WABA_WEBHOOK_VERIFY_TOKEN=
WABA_APP_SECRET=
WABA_LOG_CHANNEL=whatsapp_notifications

For development:

WABA_DRIVER=mock

For production:

WABA_DRIVER=meta_cloud_api

Never commit real credentials.

---

# 48. Mock Driver

Create a mock WhatsApp driver for development/testing.

Example:

MockWhatsAppDriver

It should simulate:

* Message accepted
* Sent
* Delivered
* Read
* Failed
* Rate limited
* Invalid number
* Template rejected

This allows development without repeatedly calling Meta.

---

# 49. Automated Tests

Create:

Unit tests
Feature tests
Integration tests
Webhook tests
Queue tests
Authorization tests
Tenant isolation tests

Critical tests:

1. User cannot access another organization's campaign.
2. Opted-out contact cannot receive marketing campaign.
3. Duplicate queue execution does not send duplicate message.
4. Duplicate webhook does not create duplicate status.
5. Invalid template cannot be launched.
6. Failed Meta API request is retried appropriately.
7. Permanent failure is not endlessly retried.
8. Rate limit causes backoff.
9. Paused campaign does not send new messages.
10. Cancelled campaign does not send new messages.
11. Secrets never appear in logs.
12. Unauthorized user cannot modify WABA configuration.
13. Uploaded malicious files are rejected.
14. SSRF attempts are blocked.
15. Webhook authenticity validation works.

---

# 50. Documentation

Create:

README.md

docs/

architecture.md
installation.md
environment.md
whatsapp-meta-setup.md
webhook-setup.md
templates.md
campaigns.md
security.md
deployment.md
troubleshooting.md

Explain exactly how to configure Meta Business Manager / WhatsApp Business Platform for the application.

Do not put real credentials in documentation.

---

# 51. Production Deployment

Prepare deployment documentation for:

* Nginx
* PHP-FPM
* MySQL
* Redis
* Supervisor
* Horizon
* Scheduler
* SSL
* Environment variables
* Queue workers
* Webhook endpoint

Add health checks.

Example:

/health

should verify basic application health without exposing secrets.

---

# 52. Monitoring

Implement monitoring for:

* Queue backlog
* Failed jobs
* API failures
* Rate limits
* Webhook failures
* Message failure rate
* Database errors
* Redis errors

Create admin alerts for abnormal failure rates.

---

# 53. Important Meta Compliance Rules

The application must be designed around Meta's current WhatsApp Business Platform rules.

Do NOT implement:

* Spam automation
* Unsolicited bulk messaging
* Template approval bypass
* Rate-limit bypass
* Account-ban evasion
* Fake engagement
* Automatic number harvesting
* Scraping WhatsApp users
* Sending to purchased/unknown lists without appropriate consent
* Any mechanism intended to circumvent Meta enforcement

The portal is a legitimate business communication platform, not a spam tool.

---

# 54. Architecture Principle

Use this high-level architecture:

```
                ┌────────────────────┐
                │    AH&V Portal     │
                │ Dashboard / Admin  │
                └─────────┬──────────┘
                          │
                          ▼
                ┌────────────────────┐
                │ Laravel Application│
                └─────────┬──────────┘
                          │
         ┌────────────────┼────────────────┐
         ▼                ▼                ▼
    ┌─────────┐      ┌─────────┐     ┌──────────┐
    │  MySQL  │      │  Redis  │     │ Object   │
    │         │      │ Queue   │     │ Storage  │
    └─────────┘      └────┬────┘     └──────────┘
                          │
                          ▼
                 ┌─────────────────┐
                 │ Queue Workers   │
                 │ Laravel Horizon │
                 └────────┬────────┘
                          │
                          ▼
                ┌────────────────────┐
                │ Meta Graph API     │
                │ WhatsApp Cloud API │
                └─────────┬──────────┘
                          │
                          ▼
                     WhatsApp
                          │
                          ▼
                ┌────────────────────┐
                │ Meta Webhook       │
                └─────────┬──────────┘
                          │
                          ▼
                 Laravel Webhook
                          │
                          ▼
                    Redis Queue
                          │
                          ▼
                 Message Status DB
                          │
                          ▼
                    AH&V Dashboard
```

---

# 55. Development Strategy

Do NOT try to build everything blindly in one pass.

Implement in phases.

## Phase 1 — Foundation

* Authentication
* Organization
* RBAC
* Database architecture
* WABA configuration
* Meta service layer
* Mock driver

## Phase 2 — WhatsApp Core

* Send test message
* Template synchronization
* Template management
* Webhook
* Message status tracking

## Phase 3 — Contacts

* Contacts
* Groups
* CSV import
* Opt-in/out

## Phase 4 — Campaigns

* Campaign builder
* Template variables
* Media
* Queue
* Throttling
* Scheduling
* Pause/resume
* Retry

## Phase 5 — Reporting

* Dashboard
* Campaign analytics
* Delivery/read/failure reports
* CSV exports

## Phase 6 — Security & Production

* Audit logs
* Security hardening
* Load testing
* Queue monitoring
* Health checks
* Deployment documentation

---

# 56. Coding Standards

Follow clean Laravel architecture.

Use:

* Form Requests
* Policies
* Service classes
* Jobs
* Events/listeners where appropriate
* Repositories only when genuinely useful
* DTOs where useful
* Enums
* API Resources
* Database transactions
* Proper exception handling

Avoid:

* Huge controllers
* Duplicate Meta API logic
* Raw SQL unless necessary
* Secrets in code
* Business logic in Blade
* Business logic in migrations
* N+1 queries

---

# 57. Final Acceptance Criteria

The implementation will be considered complete only when:

* A business can connect its WhatsApp Cloud API account.
* Admin can validate the connection.
* Templates can be synchronized.
* Templates can be created/submitted where Meta's API permits.
* Approved templates can be selected for campaigns.
* Contacts can be imported securely.
* Opt-out contacts are automatically excluded.
* Campaigns can be created.
* Campaigns can be scheduled.
* Campaigns can be paused/resumed.
* Messages are processed through Redis queues.
* Sending is throttled safely.
* Meta rate limits are respected.
* Media messages work where supported.
* Webhooks update statuses.
* Sent/delivered/read/failed statuses are visible.
* Duplicate webhook events do not duplicate records.
* Queue retries do not duplicate successful messages.
* Reports work.
* Tenant isolation is enforced.
* RBAC works.
* Audit logs work.
* Secrets are protected.
* No credentials are committed to Git.
* Security tests pass.
* Load tests are documented.
* Production deployment documentation exists.

# FINAL INSTRUCTION TO CODEX

First inspect the existing AH&V Laravel project architecture before creating files.

Reuse existing:

* authentication
* users
* roles
* permissions
* organization/tenant structure
* database conventions
* UI components
* logging
* storage
* queues

Do not unnecessarily replace existing architecture.

Before implementation, produce:

1. Architecture assessment
2. Database/ERD proposal
3. API integration plan
4. Queue architecture
5. Security threat model
6. Implementation phases

Then implement Phase 1 and Phase 2 first.

After implementation, run the project's test suite and static checks.

Fix errors before proceeding.

Do not mark the feature as production-ready until the security, webhook, queue, tenant-isolation, idempotency and load-testing requirements above have been addressed.
