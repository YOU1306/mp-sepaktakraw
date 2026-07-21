# Deployment Runbook

This is the step-by-step guide to take the portal from "runs on my laptop" to
"live in production" — matching the self-hosted, single-VPS plan in
[`ARCHITECTURE.md`](ARCHITECTURE.md) §8. Everything runs on **one server you
control**; the only external accounts needed are the VPS provider, your
domain registrar, an SMTP relay (for deliverable email), and Razorpay
(regulated — can't be self-hosted).

Code is already on GitHub: **https://github.com/YOU1306/mp-sepaktakraw**
(private repo, pushed from this machine).

Legend: 🧑 = something *you* must do manually (account/money/DNS). 🤖 = already
prepared for you in this repo (`deploy/`, `.github/workflows/`).

---

## 0. What's already done

- 🤖 App code committed and pushed to GitHub (private repo).
- 🤖 `deploy/server-setup.sh` — one-time VPS bootstrap (Nginx, PHP 8.3, MySQL,
  Redis, Certbot, firewall, Supervisor).
- 🤖 `deploy/nginx.conf.template`, `deploy/supervisor-worker.conf.template`.
- 🤖 `deploy/deploy.sh` — pulls latest code, installs deps, builds assets,
  migrates, restarts services. Used for every deploy after the first.
- 🤖 `.env.production.example` — production environment template.
- 🤖 `.github/workflows/tests.yml` — runs the test suite on every push.
- 🤖 `.github/workflows/deploy.yml` — auto-deploys `main` to the server over
  SSH once tests pass (or via manual trigger).

Everything below is what's left, in order.

---

## 1. 🧑 Create the VPS

You chose the "recommended" provider from our earlier discussion — **Hetzner
Cloud** (best price/performance; ~₹500–900/month for a 2 vCPU / 4 GB box).
Note: Hetzner has no India datacenter — pick **Singapore** for the lowest
latency to India (still self-hosted, just not in-country). If low latency to
India is more important to you than price, DigitalOcean's **Bangalore**
region or AWS Lightsail's **Mumbai** region are the alternatives — the
scripts below work identically on any Ubuntu 22.04/24.04 VPS regardless of
provider.

1. Sign up at [hetzner.com/cloud](https://www.hetzner.com/cloud).
2. Create a new **CX22** (or similar, 2 vCPU/4GB) server:
   - Image: **Ubuntu 24.04 LTS**
   - Location: **Singapore**
   - Add your SSH public key (generate one on your laptop if you don't have
     one: `ssh-keygen -t ed25519 -C "mp-sepaktakraw-deploy"`, add the printed
     `.pub` key in the Hetzner UI).
3. Note the server's public IPv4 address — you'll need it below.

## 2. 🧑 Point your domain at the server

You said you own a domain already (I've used the placeholder
`mpsepaktakraw.in` throughout — replace every occurrence with your real one).

At your domain registrar's DNS settings, add:

| Type | Name | Value |
|---|---|---|
| A | `@` | `<server public IP>` |
| A | `www` | `<server public IP>` |

DNS propagation can take a few minutes to a few hours. You can start step 3
while waiting.

## 3. 🧑 Run the one-time server bootstrap

```bash
ssh root@<server-ip>
```

Edit `deploy/server-setup.sh`'s **CONFIG** section first (domain, GitHub repo
URL) — easiest way is to download it directly:

```bash
curl -fsSL https://raw.githubusercontent.com/YOU1306/mp-sepaktakraw/main/deploy/server-setup.sh -o server-setup.sh
nano server-setup.sh   # set DOMAIN and GIT_REPO at the top
bash server-setup.sh
```

This installs Nginx, PHP 8.3-FPM, MySQL, Redis, Composer, Node.js, Certbot,
Supervisor, sets up the firewall (`ufw`) and `fail2ban`, creates a non-root
`deploy` user, and prints out the **auto-generated DB and Redis passwords at
the end — copy them somewhere safe immediately**, they are not shown again.

Partway through, since the repo is **private**, the script will generate an
SSH key for the `deploy` user and pause with:

```
ACTION NEEDED: add this as a GitHub Deploy Key ...
```

Copy the printed public key, go to **github.com/YOU1306/mp-sepaktakraw →
Settings → Deploy keys → Add deploy key**, paste it in (leave "Allow write
access" unticked — read-only is all a deploy needs), save, then press Enter
in the terminal to let the script continue and clone the repo.

> Since the deploy user was cloned via SSH from `root`'s `authorized_keys`,
> you should already be able to `ssh deploy@<server-ip>` with the same key.
> If not, copy your public key into `/home/deploy/.ssh/authorized_keys`
> manually.

## 4. 🧑 Configure the `.env` file

```bash
ssh deploy@<server-ip>
cd /var/www/mp-sepaktakraw
cp .env.production.example .env
nano .env
```

Fill in:
- `APP_URL` — your real domain, `https://...`
- `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` — from step 3's output
- `REDIS_PASSWORD` — from step 3's output
- `MAIL_*` — see step 5 (SMTP relay)
- `RAZORPAY_*` — see step 6 (test keys for now)
- `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD` — the very first Super Admin
  account, seeded on first deploy. **Change this password after first login.**

## 5. 🧑 Set up the SMTP relay (email deliverability)

You chose to set up a free-tier relay. **Brevo** (formerly Sendinblue) is
recommended — 300 free emails/day, good inbox deliverability:

1. Sign up at [brevo.com](https://www.brevo.com/) (free plan is fine).
2. Verify your sending domain (or sender email) per Brevo's instructions —
   this usually means adding a couple of DNS TXT/CNAME records at your
   registrar (same place as step 2).
3. In Brevo, go to **SMTP & API → SMTP** to get your credentials.
4. Put them in `.env`:
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=smtp-relay.brevo.com
   MAIL_PORT=587
   MAIL_USERNAME=<your Brevo login email>
   MAIL_PASSWORD=<your Brevo SMTP key>
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS="noreply@yourdomain.in"
   ```

## 6. ⚠️ Razorpay — current status, read before touching `.env`

**Important — this is a real gap, not just a config step.** Today the code
has a `PaymentService::isTestMode()` stub, not a finished Razorpay
integration:

- If `RAZORPAY_KEY` / `RAZORPAY_SECRET` are **left blank**, the payment page
  shows a "Test Mode" button that just marks the payment as paid — no
  gateway is called at all. This is fine for demoing the flow.
- The moment real Razorpay keys are put in `.env`, the code switches to a
  branch that expects a signed `razorpay_payment_id`/`razorpay_signature`
  from the browser — but **the Razorpay Checkout.js widget is not wired up
  in `resources/views/registration/payment.blade.php` yet, there's no
  signature-verification logic, and there's no webhook route/controller.**
  Adding real keys right now would break the payment step for real users.
- Separately, and why this hasn't bitten anyone yet: `Setting::fee(...)`
  defaults to **₹0** for both Federation and Club registrations
  (`SettingSeeder`), and the controllers **skip the payment page entirely
  when the fee is 0**. So right now, nobody is actually charged anything.

**What this means for launch:**
1. **Leave `RAZORPAY_KEY`/`RAZORPAY_SECRET`/`RAZORPAY_WEBHOOK_SECRET` blank
   in production `.env` for now**, and **keep the federation/club fees at
   ₹0** in Settings (admin panel) until the gateway integration below is
   finished. This lets you launch the site — content, registrations,
   approvals, all fully working — without the unfinished payment path being
   reachable.
2. Before you turn on any non-zero fee, the following needs to be built
   (flag this as a follow-up task, separate from server deployment):
   - Razorpay Checkout.js on the payment page (create the order server-side
     as already coded, then actually open the widget client-side).
   - Server-side HMAC-SHA256 signature verification in
     `PaymentController::process()` before calling `markPaid()`.
   - A `/webhooks/razorpay` route + controller verifying the webhook
     signature, so payments are recorded even if the browser tab closes
     mid-flow (this is the authoritative source of truth per
     `ARCHITECTURE.md` §6).
3. Once that's built, get **test** API keys from
   [dashboard.razorpay.com](https://dashboard.razorpay.com/) (Test Mode
   toggle, top-left → **Settings → API Keys**) and exercise a full test
   payment (Razorpay's test card `4111 1111 1111 1111`, any future
   expiry/CVV) before turning on real fees.
4. **Going live with real money later:** complete Razorpay KYC/business
   verification, switch the dashboard to Live Mode, generate live keys, and
   swap the three `RAZORPAY_*` values in `.env` — no further code changes
   needed at that point.

## 7. 🧑 First deploy

```bash
cd /var/www/mp-sepaktakraw
bash deploy/deploy.sh --first-run
```

This installs dependencies, builds frontend assets, runs migrations **and**
seeders (roles, districts, settings, the Super Admin account, and a couple
of starter news/notice items you can edit or delete from the admin panel),
and links storage.

## 8. 🧑 Issue the SSL certificate

Once DNS (step 2) has propagated:

```bash
sudo certbot --nginx -d yourdomain.in -d www.yourdomain.in -m admin@yourdomain.in --agree-tos --redirect
```

Certbot edits the Nginx config to serve HTTPS and auto-renews every 90 days
via a systemd timer it installs — no further action needed.

Visit `https://yourdomain.in` — the portal should now be live.

## 9. 🧑 Wire up GitHub Actions for auto-deploy

So every push to `main` (once tests pass) deploys automatically:

1. In your GitHub repo → **Settings → Environments** → create an environment
   named `production` (lets you optionally require manual approval later).
2. **Settings → Secrets and variables → Actions** → add these repository (or
   environment) secrets:

   | Secret | Value |
   |---|---|
   | `SSH_HOST` | your server's IP or domain |
   | `SSH_USER` | `deploy` |
   | `SSH_PRIVATE_KEY` | the **private** key matching the deploy user's `authorized_keys` (generate a dedicated deploy key, don't reuse your personal one — `ssh-keygen -t ed25519 -f deploy_key -C "github-actions"`, add `deploy_key.pub` to the server's `/home/deploy/.ssh/authorized_keys`, paste `deploy_key` contents here) |
   | `SSH_PORT` | `22` |
   | `APP_DIR` | `/var/www/mp-sepaktakraw` |

3. Push anything to `main` (or re-run the "Tests" workflow manually) — the
   "Deploy" workflow triggers automatically once tests pass, SSHes in, and
   runs `deploy/deploy.sh`.

From here on: **`git push origin main`** (after tests pass locally) is your
entire deploy process.

## 10. 🧑 Post-launch hardening checklist

- [ ] Log into the site as Super Admin and **change the seeded password**.
- [ ] Confirm `APP_DEBUG=false` and `APP_ENV=production` in `.env`.
- [ ] Set up daily backups (`crontab -e` as `deploy`):
  ```cron
  0 2 * * * mysqldump -u mp_sepaktakraw -p'<password>' mp_sepaktakraw | gzip > /var/backups/mp_sepaktakraw-$(date +\%F).sql.gz
  ```
  Copy backups off the box periodically (e.g. `rclone`/`scp` to another
  machine or free-tier object storage) — a backup that only lives on the
  server it's backing up isn't a real backup.
- [ ] Test the password-reset and registration-approval emails actually land
  in an inbox (not spam) — Brevo's dashboard shows delivery status.
- [ ] Confirm Federation/Club fees are still **₹0** in Settings until the
  Razorpay Checkout.js + signature verification + webhook work (step 6) is
  done — don't turn on real fees before that.
- [ ] Once that integration work is done: run a full Razorpay **test-mode**
  payment end to end (card `4111 1111 1111 1111`, any future expiry/CVV) to
  confirm registration → payment → webhook → approval works, before going
  live with real money.
- [ ] When ready for real payments: complete Razorpay KYC, flip to live
  keys, set the real fee amounts in Settings, and do one small real
  transaction yourself to confirm.
- [ ] Optional (Tier 1 in `ARCHITECTURE.md`): put the free tier of
  **Cloudflare** in front of the domain for CDN/WAF/DDoS protection once
  traffic grows — not required to launch.

---

## Redeploying / rolling back

- **Normal deploy:** `git push origin main` (CI runs tests, then deploys).
- **Manual deploy from the server:** `cd /var/www/mp-sepaktakraw && bash deploy/deploy.sh`
- **Rollback:** on the server, `git log --oneline` to find the last good
  commit, then `git checkout <sha> -- .` or `git reset --hard <sha>` followed
  by `bash deploy/deploy.sh` (skip `--first-run`; migrations are additive so
  this is safe unless the bad commit also included a destructive migration —
  check before rolling back across a migration boundary).
