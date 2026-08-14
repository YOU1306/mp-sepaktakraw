# MP Sepaktakraw Federation — Architecture & Complete Workflow Plan

State-level (Madhya Pradesh) sports federation portal, modeled on
https://sepaktakrawindia.com/ but rebuilt as a modern, role-based, payment-enabled web app.

**Design philosophy (v2): self-hosted, minimal third-party dependency.**
Like the reference site, everything runs on our **own server**. The only unavoidable external
service is the **payment gateway** (regulatory — card/UPI processing cannot be self-hosted).

- **Scope:** public content site + registrations + secure fee payment + role-based admin panel
- **Users:** many normal users, 1+ executive **per district** (~52 MP districts), 1+ admin
- **Goals:** low budget, low-data friendly (rural mobile), scalable under spikes, secure, self-contained

---

## 0. Reference Site Stack (sepaktakrawindia.com) — what we take from it

| Layer | Reference site | What we keep / change |
|---|---|---|
| CMS / app | WordPress 7.0 (PHP) | Keep **PHP**, but a real app framework (Laravel) instead of plugin soup |
| Database | MySQL | Keep **MySQL** (self-hosted, same server) |
| Web server | Apache/Nginx | Keep **Nginx + PHP-FPM** (self-hosted) |
| Media | Local `/wp-content/uploads` | Keep **local filesystem** storage (no cloud bucket) |
| Slider/gallery | Plugin (cached JPGs) | Replace with our own optimized WebP slider |
| Hosting | Single origin server | Keep **single VPS**, self-managed |
| External services | None | Only add **one**: the payment gateway |

Takeaway: the reference is a self-contained PHP + MySQL single-server site. We stay in that model,
just modernized and secured, and add the role/registration/payment features it lacks.

---

## 1. User Roles & Permissions

Four roles (RBAC). "Super Admin" is a hardened top role so one admin can't lock others out.

| Capability | Super Admin | Admin | Executive (per district) | User (public) |
|---|:---:|:---:|:---:|:---:|
| View public content (news/notices/results/events) | Yes | Yes | Yes | Yes |
| Create / edit / delete news, articles, notices, results | Yes | Yes | No | No |
| Upload / delete media (images, PDFs) | Yes | Yes | Only for own district | No |
| Create / open intake registrations (tournament trials) | Yes | Yes | Propose only | No |
| Manage site settings, menus, banners/slider | Yes | Yes | No | No |
| Add / remove **Executives** | Yes | Yes | No | No |
| Add / remove **Admins** | Yes | No | No | No |
| Assign executive to a district | Yes | Yes | No | No |
| Review & approve player registrations | Yes | Yes (any dist) | Yes (own district only) | No |
| Maintain district player list | Yes | Yes | Yes (own district) | No |
| Register for an intake opening + pay fee | — | — | — | Yes |
| View own registration status / receipt | — | — | — | Yes |
| Audit log access | Yes | Yes (read) | No | No |

**Key rule:** an Executive is *scoped to one district*. Every action is filtered by `district_id`,
mirroring govt portals where a district officer only acts within their jurisdiction.

---

## 2. Tech Stack (self-hosted, low third-party dependency)

Everything below runs on **one VPS you control**. No Vercel, no managed DB, no cloud storage bucket,
no external auth/email SaaS required.

| Layer | Choice | Self-hosted? | Why |
|---|---|:---:|---|
| Server | **1 VPS** (Hetzner / DigitalOcean / Linode / Lightsail), Ubuntu LTS | Yes | Single origin, full control, ~₹500–1200/mo |
| Web server | **Nginx + PHP-FPM** | Yes | Same family as reference, fast, free |
| App framework | **Laravel (PHP)** | Yes | PHP lineage like WP, batteries-included: auth, RBAC, queues, ORM |
| UI rendering | **Blade + Livewire** (server-rendered, minimal JS) | Yes | Tiny payloads = great on 2G/3G; no heavy SPA needed |
| Admin panel | **Filament** (open-source Laravel admin) | Yes | Govt-style CRUD/dashboards without building from scratch |
| Database | **MySQL** (or MariaDB/PostgreSQL) on same VPS | Yes | Same DB family as reference |
| ORM / migrations | **Eloquent + Laravel migrations** | Yes | Type-safe schema, versioned |
| Auth | **Laravel Fortify/Breeze** — sessions, bcrypt/argon2 | Yes | Fully self-hosted, no external identity provider |
| RBAC | **spatie/laravel-permission** | Yes | Roles + row-level district scoping |
| 2FA (admin/exec) | **Fortify TOTP** (authenticator app) | Yes | Hardens privileged accounts, no SMS dependency |
| Media storage | **Local filesystem** on VPS (optional self-hosted MinIO) | Yes | Like `/wp-content/uploads`; no cloud bucket |
| Image optimization | **Intervention Image** → WebP, server-side | Yes | Compress/convert on upload, self-hosted |
| Cache / queue / sessions | **Redis** on same VPS | Yes | Page/object cache + background jobs (emails, image processing) |
| Full-page cache | **Nginx FastCGI cache** | Yes | Serve cached HTML without hitting PHP/DB |
| Search | MySQL full-text (optional self-hosted Meilisearch) | Yes | No external search SaaS |
| Email | **SMTP** (self-hosted Postfix, or any SMTP relay) | Mostly | See note ¹ |
| SMS/OTP | **Optional** — app works without it (email + TOTP) | — | Only add a provider if OTP is truly required |
| CDN / WAF | **Cloudflare free tier — OPTIONAL** | External(free) | Not required; add later only if you want edge cache/DDoS |
| **Payment gateway** | **Razorpay** (or PhonePe / Cashfree) | **No — unavoidable** | See note ² |
| Backups | **cron** `mysqldump` + rsync to offsite/second disk | Yes | Self-managed daily backups |
| Deploy | **Git pull + `artisan migrate`** or GitHub Actions → SSH | Yes | No Vercel; simple, reproducible |
| TLS/HTTPS | **Let's Encrypt (Certbot)** on Nginx | Yes | Free auto-renewing SSL, no third party |
| Monitoring | **Self-hosted** (Uptime Kuma) + Laravel logs / Sentry-self-hosted | Yes | No mandatory SaaS |

> ¹ **Email note:** running your own mail server (Postfix) is 100% self-hosted but deliverability
> (inbox vs spam) is hard. Pragmatic middle ground: send via an SMTP relay. This is the *one soft*
> external touchpoint and is easily swappable — the app only needs SMTP credentials.
>
> ² **Payment note:** online card/UPI/netbanking processing is regulated and **cannot** be
> self-hosted. A gateway (Razorpay/PhonePe/Cashfree) is the **single required external service**.
> We keep our exposure minimal: we only call its order/verify/webhook APIs; no card data touches us.

---

## 2A. Dependency Policy & Scale Tiers

**Guiding principle:** minimize third-party dependencies — but **never sacrifice functionality,
reliability, or the production quality we are targeting just to avoid one.** Self-host by default;
adopt a managed service *deliberately* when it clearly improves scale/reliability, and keep it
**swappable** (code to an interface, not a vendor) so we're never locked in.

Every external service must pass this test before adoption:
1. Does self-hosting it hurt functionality, reliability, or user experience at our target scale?
2. Is it swappable behind our own abstraction (so we can move off it)?
3. Does the cost/benefit make sense for a state-federation budget?

We organize dependencies into tiers. **Tier 0 is where we start and stay for a long time.**

| Tier | When | What runs where | Third-party used |
|---|---|---|---|
| **Tier 0 — Self-hosted core** (default / launch) | Now → state-federation scale | Everything on one VPS: Nginx, Laravel, MySQL, Redis, local media, Let's Encrypt | **Only the payment gateway** |
| **Tier 1 — Add free/cheap resilience** (first spikes) | Announcement-day spikes | Same VPS + edge in front | + **Cloudflare (free)** CDN/WAF/DDoS; + **SMTP relay** for email deliverability |
| **Tier 2 — Offload heavy/at-risk pieces** (steady growth) | Reads/writes climbing | Split concerns off the single box | + **Object storage (S3/R2/Spaces)** for media at volume; + managed **backups**; + **error/uptime SaaS** (or self-host Sentry/Uptime Kuma) |
| **Tier 3 — True large scale** (national / very high traffic) | Millions of users / HA required | Multi-node, HA | + **Managed MySQL w/ replicas + failover**; + **load balancer**; + multiple app nodes; + managed Redis |

**What is allowed to become a dependency (in priority order), because self-hosting it would hurt
quality at scale:**
- **CDN/WAF (Cloudflare)** — best way to absorb spikes + block attacks; free tier; add early.
- **Object storage** — media grows unbounded; a bucket is more durable/scalable than one disk.
- **Managed database (with replicas/HA)** — DB is the hardest thing to run reliably at scale;
  managed HA is worth it once uptime SLAs matter. Kept behind Eloquent so it's swappable.
- **Email relay** — deliverability (inbox vs spam) is genuinely hard to self-host well.
- **Error/uptime monitoring** — SaaS is convenient; self-hosted (Sentry/Uptime Kuma) also fine.

**What stays self-hosted essentially forever** (self-hosting these does NOT hurt quality):
application code, business logic, auth/RBAC/2FA, sessions, caching (Redis), the web tier (Nginx).

**Hard rule:** the **payment gateway** is the only dependency required from day one; everything
else in Tiers 1–3 is added **only when the scale actually demands it**, and each is isolated behind
our own service interface (`StorageService`, `MailService`, `PaymentService`, DB via Eloquent) so
swapping providers — or moving back to self-hosted — is a config change, not a rewrite.

---

## 3. High-Level Architecture (single self-hosted server)

```
                         ┌───────────────────────────────────────────────┐
                         │  (OPTIONAL) Cloudflare free — CDN/WAF/SSL       │
                         │  can be added later; NOT required               │
                         └───────────────────────┬───────────────────────┘
                                                 │
                         ┌───────────────────────▼───────────────────────┐
                         │              YOUR VPS  (Ubuntu)                 │
                         │                                                 │
                         │   Nginx  (TLS via Let's Encrypt, FastCGI cache) │
                         │      │                                          │
                         │      ▼                                          │
                         │   PHP-FPM  ──  Laravel app                      │
                         │      • Public site (Blade + Livewire)           │
                         │      • Admin/Exec/User dashboards (Filament)    │
                         │      • Auth + RBAC + 2FA                        │
                         │      • Registration + Payment logic + Webhooks  │
                         │      │                    │            │        │
                         │      ▼                    ▼            ▼        │
                         │   MySQL            Redis (cache/     Local FS   │
                         │   (data)            queue/session)   (media,    │
                         │                                       WebP,PDF) │
                         │                                                 │
                         │   cron: mysqldump backups + image jobs          │
                         └───────────────────────┬─────────────────────────┘
                                                 │  (HTTPS API calls only)
                                                 ▼
                              ┌──────────────────────────────────┐
                              │ Payment Gateway (Razorpay/etc.)   │  ← ONLY external dependency
                              │ order create + signature + webhook │
                              └──────────────────────────────────┘
```

Everything except the payment gateway lives on one box you own. No managed database, no cloud
storage, no external hosting platform, no third-party auth.

---

## 4. Data Model (core entities)

```
User        (id, name, email, phone, password_hash, role, district_id?, is_2fa, created_at)
District    (id, name, code)
Content     (id, type[news|notice|result|event|page], title, slug, body, status[draft|published],
             author_id, district_id?, published_at)
Media       (id, path, type[image|pdf], alt, uploaded_by, district_id?, content_id?)
IntakeOpening(id, title, description, district_id?, fee_amount, form_schema, opens_at, closes_at, status)
Registration(id, intake_id, user_id, district_id, form_data(json), status[submitted|paid|
             under_review|approved|rejected], reviewed_by, review_note, created_at)
Payment     (id, registration_id, user_id, amount, currency, gateway_order_id,
             gateway_payment_id, gateway_signature, status[created|paid|failed|refunded], created_at)
PlayerListEntry(id, district_id, user_id, intake_id, added_by, added_at)
AuditLog    (id, actor_id, action, entity, entity_id, meta(json), ip, created_at)
```

`Executive` = a `User` with role=executive and a `district_id`.

---

## 5. Complete Workflows (end to end)

### 5.1 Public visitor — reading the site (READ path)
```
User (mobile/3G) → Nginx
   ├─ FastCGI full-page cache hit? YES → serve cached HTML + local WebP images (no PHP, no DB)
   └─ NO → PHP-FPM/Laravel renders from MySQL → Nginx caches it → next visitors get the cached copy
```
Server-rendered Blade pages + WebP images + Nginx cache keep pages fast and DB hits low, even on
one server. (If Cloudflare is later added, these cached pages also live at the edge.)

### 5.2 User registration for an intake opening + SECURE payment (WRITE path)
```
1. User opens an active Intake Opening (e.g. "District Trials — Bhopal 2026")
2. User signs up / logs in  (Laravel auth — self-hosted; optional email verify)
3. User fills the registration form (fields from IntakeOpening.form_schema)
4. Submit → server creates Registration (status = submitted) in MySQL
5. Server calls gateway "Create Order" API (amount read from DB — never trust the client)
      → returns gateway_order_id; Payment row saved (status = created)
6. Browser opens gateway Checkout (UPI / card / netbanking / wallet) — no card data touches us
7. User pays → gateway returns {payment_id, order_id, signature} to the browser
8. Browser posts those to our /payments/verify
9. SERVER verifies the HMAC-SHA256 signature with the gateway secret
      valid   → Payment.status = paid, Registration.status = under_review
      invalid → reject, mark failed
10. ALSO: gateway Webhook (payment.captured) hits /webhooks/payment (signed) →
      authoritative source of truth; reconciles even if the browser closed
11. User gets email receipt + on-screen confirmation + downloadable PDF receipt
```
Security: amount always from DB, signature verified server-side, webhook is source of truth,
secrets server-only, idempotent by order_id (no double-charge/double-record).

### 5.3 Executive review & approval (per district)
```
1. Executive logs in (2FA) → dashboard auto-filtered to their district_id
2. Sees Registrations where status = under_review AND district = theirs
3. Opens one: form data + payment status (must be "paid") + uploaded docs
4. Decision:
      Approve → status = approved → PlayerListEntry created → user notified →
                appears in district player list
      Reject  → status = rejected + review_note → user notified (with reason);
                refund via gateway refund API per policy
5. Every action written to AuditLog (who/what/when/ip)
```
Executive cannot see/act on other districts, cannot edit site content, cannot manage users.

### 5.4 Admin — content management
```
Admin logs in (2FA) → Filament admin panel
   • Create/edit/delete News, Notices, Results, Events, static Pages (rich text + media)
   • Upload images/PDFs → stored on local disk, auto-converted to WebP (queued job)
   • Manage homepage slider/banners, menus, partner logos
   • Publish → clears the affected Nginx/Redis cache → live immediately
   • Open/close Intake Openings, set fee amount + form fields
   • Review any district's registrations (override)
All writes are audit-logged.
```

### 5.5 Admin / Super Admin — user & role management (govt-style)
```
Admin:
   • Add Executive → create User(role=executive) + assign district → invite email to set password
   • Remove/suspend Executive, reassign district
Super Admin (only):
   • Add/remove Admins
   • Manage global settings, view full audit log, rotate secrets
Least privilege: admins can't create admins (needs super admin) → prevents privilege escalation
and lock-outs (standard for government portals).
```

### 5.6 Authentication & authorization (every request)
```
Login → Laravel session (secure httpOnly cookie) carrying {userId, role, district_id}
Every protected route → middleware checks:
   1. authenticated?
   2. role allowed for this action? (spatie RBAC)
   3. if executive → does the record's district_id match theirs? (row-level scope)
Privileged roles (admin/exec) require 2FA (TOTP).
```

---

## 6. Payment Flow — Security Deep Dive

- **Order created server-side** with amount from DB; client never sends the price.
- **Gateway Checkout** handles all card/UPI data → PCI scope stays with the gateway, not us.
- **Signature verification**: `HMAC_SHA256(order_id + "|" + payment_id, key_secret)` checked before
  trusting success.
- **Webhook** (`/webhooks/payment`, own signature check) = authoritative confirmation; handles the
  user closing the tab after paying.
- **Idempotency** on `order_id` so retries/duplicate callbacks don't double-record.
- **Refunds** via gateway Refund API for rejected registrations (per federation policy).
- Secrets in server-side `.env` only; keys rotated periodically; all payment actions audit-logged.

---

## 7. Security & Compliance (govt-adjacent, self-hosted)

- HTTPS via **Let's Encrypt** (auto-renew), HSTS, secure httpOnly SameSite cookies
- Server firewall (ufw), SSH key-only login, fail2ban, Nginx rate limiting
- RBAC + row-level district scoping + 2FA for privileged roles
- Passwords hashed (argon2/bcrypt); secrets in `.env` (never committed)
- Input validation + output escaping (Blade auto-escapes → XSS safe); Eloquent = SQL-injection safe
- CSRF tokens on all state-changing requests; upload validation (type/size) + WebP re-encode strips payloads
- Audit log for every privileged/state-changing action
- **Self-managed backups**: daily `mysqldump` + media rsync to a second disk / offsite; test restores
- Regular OS + dependency updates (unattended-upgrades + composer audit)

---

## 8. Deployment & Ops (no external platform)

```
Developer → git push
   → GitHub Actions (or manual) SSH into VPS:
        git pull  →  composer install  →  php artisan migrate --force
        →  build assets  →  php artisan optimize + cache clear  →  reload PHP-FPM
   → zero managed-platform lock-in; rollback = git checkout previous tag + migrate:rollback
Services on the VPS: nginx, php-fpm, mysql, redis, cron (backups + queue worker via supervisor)
Environments: staging + production (separate DBs, gateway test vs live keys)
```

---

## 9. Indicative Monthly Cost (self-hosted)

| Item | Est. cost |
|---|---|
| Single VPS (2 vCPU / 4 GB, incl. MySQL + Redis + app + media) | ₹500 – ₹1,200 / mo |
| Domain (.in / .org.in) | ~₹800 / year |
| SSL (Let's Encrypt) | Free |
| Cloudflare (optional) | Free |
| SMTP relay (Brevo free tier, 300 emails/day) | ₹0 |
| SMS OTP + notifications (MSG91, pay-as-you-go, ~₹0.15–0.20/SMS) | ~₹50 – ₹200 / mo (volume-dependent) |
| SMS DLT registration (one-time, sender ID + templates, regulatory — not annual) | ~₹6,000 one-time |
| Aadhaar Offline e-KYC verification (UIDAI signing certificate) | Free (public cert) |
| Payment gateway (Razorpay) | per-transaction % only (no fixed cost) |
| **Total recurring** | **~₹550 – ₹1,400 / month** (+ ~₹6,000 one-time SMS DLT registration in year 1) |

Almost everything is one VPS bill plus a small, usage-based SMS wallet. No large per-service SaaS stacking.

---

## 10. Scaling Path (tiered — self-hosted first, dependencies added only as needed)

Maps directly to the tiers in §2A. Each step is incremental; nothing is rewritten. Managed
services are introduced only when scale demands, and each stays swappable behind our own interface.

**Tier 0 → Tier 1 (first spikes, cheap/free):**
1. Turn on **Nginx FastCGI cache** aggressively (already in the stack).
2. Put **Cloudflare (free)** in front → edge caching + WAF + DDoS absorbs announcement-day spikes.
3. Route email through an **SMTP relay** for reliable delivery.

**Tier 1 → Tier 2 (steady growth):**
4. **Vertical scale** the VPS (more CPU/RAM) — one click on any provider.
5. Move media to **object storage (S3 / R2 / Spaces)** via `StorageService` — durable, unbounded.
6. Split tiers: separate **MySQL** box and **Redis** box from the app box.
7. Add proper **monitoring/error tracking** (self-host Sentry + Uptime Kuma, or SaaS).

**Tier 2 → Tier 3 (national / high-availability):**
8. **Horizontal scale**: multiple stateless Laravel app nodes behind a **load balancer**
   (sessions already in Redis, so this is straightforward).
9. **Managed MySQL with read replicas + automatic failover** (behind Eloquent → swappable).
10. **Managed Redis** cluster; CDN handles the static/read load at the edge.

Result: start at ~₹500–1,700/month on one box, and grow to a load-balanced, replicated,
HA deployment for very large scale — adding each dependency only when it earns its place.

---

## 11. Phased Delivery Roadmap

1. **Phase 0 – Server + app setup (2–3 days):** VPS, Nginx+PHP-FPM+MySQL+Redis, Laravel, Certbot, domain.
2. **Phase 1 – Public site (1 week):** home/slider, news, notices, results, events, rules, history, contact (Blade + Nginx cache).
3. **Phase 2 – Auth & RBAC (3–4 days):** login/signup, roles, 2FA, district scoping, audit log.
4. **Phase 3 – Admin panel (1 week):** Filament content + media CRUD (WebP), intake openings, user/role management.
5. **Phase 4 – Registrations + Payments (1 week):** intake forms, gateway order/verify/webhook, PDF receipts.
6. **Phase 5 – Executive review (3–4 days):** district-scoped approvals, player lists, notifications.
7. **Phase 6 – Hardening & launch (3–4 days):** firewall/fail2ban, backups, monitoring, load test, go live.

Total: ~4–5 weeks for a full self-hosted production build.

---

## 12. Proposed Repository Structure (Laravel)

```
mp-sepaktakraw/
├─ docs/                       # this plan + design docs
├─ app/
│  ├─ Models/                  # User, District, Content, IntakeOpening, Registration, Payment...
│  ├─ Http/
│  │  ├─ Controllers/          # public, registration, payment, webhook controllers
│  │  └─ Middleware/           # auth, role, district-scope
│  ├─ Livewire/                # interactive public components (forms, search)
│  ├─ Filament/                # admin + executive panels (resources, pages)
│  ├─ Services/                # PaymentService, RegistrationService, ReviewService, AuditService
│  └─ Jobs/                    # image->WebP, emails, backups
├─ database/
│  ├─ migrations/              # schema
│  └─ seeders/                 # districts, roles, first super admin
├─ resources/
│  ├─ views/                   # Blade templates (public site)
│  └─ css|js/                  # minimal assets
├─ routes/                     # web.php, api.php (webhooks)
├─ storage/app/media/          # local uploaded media (WebP/PDF)
├─ .env.example
├─ composer.json
└─ README.md
```
```
```
