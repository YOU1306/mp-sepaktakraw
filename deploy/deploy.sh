#!/usr/bin/env bash
#
# Deploy/redeploy the app on the server. Run as the deploy user, from the
# app directory (or let the GitHub Actions workflow run it over SSH):
#
#   cd /var/www/mp-sepaktakraw && bash deploy/deploy.sh
#
# First deploy on a brand new server (runs migrations WITH seeders, and
# links storage) — see docs/DEPLOYMENT.md step "First deploy":
#
#   cd /var/www/mp-sepaktakraw && bash deploy/deploy.sh --first-run

set -euo pipefail

FIRST_RUN=false
if [[ "${1:-}" == "--first-run" ]]; then
  FIRST_RUN=true
fi

PHP_BIN="${PHP_BIN:-php8.3}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${APP_DIR}"

if [ ! -f .env ]; then
  echo "ERROR: .env not found in ${APP_DIR}. Copy .env.production.example to .env and fill it in first." >&2
  exit 1
fi

echo "==> Pulling latest code"
git fetch origin
git reset --hard origin/main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing JS dependencies and building assets"
npm ci
npm run build

echo "==> Ensuring APP_KEY is set"
if ! grep -q '^APP_KEY=base64' .env; then
  ${PHP_BIN} artisan key:generate --force
fi

if [ "${FIRST_RUN}" = true ]; then
  echo "==> First run: migrating + seeding database"
  ${PHP_BIN} artisan migrate --force
  ${PHP_BIN} artisan db:seed --force
  ${PHP_BIN} artisan storage:link || true
else
  echo "==> Migrating database (no seeding)"
  ${PHP_BIN} artisan migrate --force
fi

echo "==> Setting storage/cache permissions"
chmod -R ug+rwX storage bootstrap/cache

echo "==> Caching config/routes/views"
${PHP_BIN} artisan optimize:clear
${PHP_BIN} artisan config:cache
${PHP_BIN} artisan route:cache
${PHP_BIN} artisan view:cache
${PHP_BIN} artisan event:cache

echo "==> Restarting PHP-FPM and queue worker"
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart mp-sepaktakraw-worker:* || true

echo "==> Deploy complete."
