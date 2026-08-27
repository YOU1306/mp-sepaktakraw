# MP Sepaktakraw Federation Portal

State-level (Madhya Pradesh) sports federation website with role-based administration,
player registrations, and secure online fee payment. Inspired by
https://sepaktakrawindia.com/ but rebuilt as a modern, scalable, low-data-friendly app.

## Roles
- **Super Admin** — full control, manages Admins, global settings, audit log.
- **Admin** — manages site content (news/notices/results/events), media, intake openings,
  and Executives; can review any registration.
- **Executive (per district)** — reviews & approves player registrations for their own
  district only; maintains the district player list.
- **User (public)** — browses content, registers for intake openings, pays the fee online,
  tracks status and downloads receipts.

## Tech Stack (self-hosted, minimal third-party dependency)
Single VPS running Nginx + PHP-FPM · **Laravel (PHP)** app · Blade + Livewire (low-JS,
low-bandwidth) · **Filament** admin panel · **MySQL** + Eloquent · self-hosted auth (Fortify) +
RBAC (spatie) + 2FA (TOTP) · local filesystem media (WebP via Intervention Image) · Redis
(cache/queue) · Let's Encrypt SSL · self-managed cron backups.

Everything runs on one server you control. The **only** external dependency is the **payment
gateway** (Razorpay/PhonePe/Cashfree) — card/UPI processing is regulated and cannot be self-hosted.
Cloudflare (free CDN/WAF) and an SMTP relay are optional add-ons, not requirements.

## Documentation
See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for the full end-to-end architecture,
workflows (browsing, registration + secure payment, executive review, admin management),
data model, security, deployment, cost estimate, and delivery roadmap.

## Local development setup

**Company laptop / no admin?** Use the **native path** (no Docker):

| Step | Command |
|---|---|
| 1. Scoop (user-level, no admin) | `Set-ExecutionPolicy RemoteSigned -Scope CurrentUser; irm get.scoop.sh \| iex` |
| 2. PHP + Composer | `scoop install php composer` |
| 3. Fix corporate SSL (company proxy) | `.\scripts\fix-corporate-ssl.ps1` |
| 4. Bootstrap Laravel | `.\scripts\setup-native.ps1` |
| 5. Run dev server | `php artisan serve` → http://127.0.0.1:8000 |

Local dev uses **SQLite** (already configured). Production VPS uses MySQL + Redis — same Laravel code.

**If IT grants Docker later:** use `.\scripts\setup.ps1` instead (Sail + MySQL + Redis).

**External accounts to create (can be done in parallel):**

1. **Razorpay** — [dashboard.razorpay.com](https://dashboard.razorpay.com/) — test keys + webhook secret
2. **SMTP relay** — for receipts & invites
3. **Domain + VPS** — deferred until pre-launch

District seed data (55 MP districts): [`data/mp-districts.json`](data/mp-districts.json)

## Deployment

Code is on GitHub: **https://github.com/YOU1306/mp-sepaktakraw** (private).
Full step-by-step production deployment (VPS, DNS, SSL, SMTP, GitHub Actions
auto-deploy, backups, going-live checklist) is documented in
[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md). Deployment tooling lives in
[`deploy/`](deploy/) — `server-setup.sh` (one-time VPS bootstrap) and
`deploy.sh` (every deploy after that).

**Razorpay status:** Checkout.js, server-side HMAC signature verification, and
the `/webhooks/payment` endpoint are wired. Leave `RAZORPAY_*` blank for local
test-mode (one-click simulate pay). Put **test** keys in `.env` to exercise the
real gateway before go-live; switch to **live** keys only after Razorpay KYC.
See [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) §6.

**Other known gaps:**
- **SMS (MSG91):** wired via `SmsService`/`OtpService`, but falls back to a
  test-mode stub (code logged, and returned in the API response for local
  testing) until `MSG91_AUTH_KEY` is set in `.env`.
- **Aadhaar offline e-KYC:** `AadhaarOfflineKycService` parses and extracts
  data from the uploaded Offline e-KYC ZIP/XML immediately. Cryptographic
  signature verification against UIDAI's certificate only activates once the
  certificate file is placed at `AADHAAR_UIDAI_CERT_PATH` — until then,
  applications are flagged "not verified — manual check required" for the
  district federation reviewer.

## Status
Public site, auth/RBAC, admin panel, and individual/federation registration
flows (Club registration has been removed — a Club's former responsibilities
now sit with the District Federation / Admin / Super Admin) are built and
tested locally. Individual registration covers
Player/Team Manager/Coach/Referee/Scorer/Official in one flow, with phone +
email OTP verification, offline Aadhaar e-KYC, and quarterly/half-yearly/
yearly billing with automatic reminders + access lockout on missed renewal.
Razorpay order/verify/webhook integration is complete. Next: add live MSG91 +
UIDAI certificate credentials, run an end-to-end Razorpay **test-mode**
payment, then follow [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) to go live.
