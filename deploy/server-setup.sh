#!/usr/bin/env bash
#
# One-time production server bootstrap for the MP Sepaktakraw Federation portal.
#
# Target: fresh Ubuntu 22.04/24.04 LTS VPS (Hetzner Cloud, DigitalOcean, etc.)
# Run as root (or with sudo) exactly ONCE per server:
#
#   ssh root@<server-ip>
#   curl -fsSL https://raw.githubusercontent.com/<you>/mp-sepaktakraw/main/deploy/server-setup.sh -o server-setup.sh
#   nano server-setup.sh   # edit the CONFIG section below first!
#   bash server-setup.sh
#
# See docs/DEPLOYMENT.md for the full step-by-step walkthrough (including the
# manual steps this script cannot do for you: buying the VPS, DNS, Razorpay,
# SMTP relay signup, and GitHub secrets).

set -euo pipefail

########################################################################
# CONFIG — edit these before running
########################################################################
DOMAIN="mpsepaktakraw.in"                              # your real domain (or leave as placeholder, add later)
GIT_REPO="git@github.com:YOU1306/mp-sepaktakraw.git"   # SSH remote of your GitHub repo
DEPLOY_USER="deploy"                                    # non-root user that owns the app + runs deploys
APP_DIR="/var/www/mp-sepaktakraw"
PHP_VERSION="8.4"
DB_NAME="mp_sepaktakraw"
DB_USER="mp_sepaktakraw"
DB_PASS="$(openssl rand -base64 24 | tr -d '=+/')"      # auto-generated; printed at the end — save it
REDIS_PASS="$(openssl rand -base64 24 | tr -d '=+/')"   # auto-generated; printed at the end — save it
LETSENCRYPT_EMAIL="admin@${DOMAIN}"                     # used for Let's Encrypt renewal notices
########################################################################

echo "==> Updating system packages"
apt-get update -y && apt-get upgrade -y

echo "==> Installing base tools"
apt-get install -y software-properties-common curl git unzip ufw fail2ban supervisor

echo "==> Adding PHP ${PHP_VERSION} (ondrej/php PPA)"
add-apt-repository -y ppa:ondrej/php
apt-get update -y

echo "==> Installing Nginx + PHP-FPM + extensions"
apt-get install -y nginx \
  php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-common \
  php${PHP_VERSION}-mysql php${PHP_VERSION}-redis php${PHP_VERSION}-curl \
  php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip \
  php${PHP_VERSION}-gd php${PHP_VERSION}-intl php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-sqlite3

echo "==> Raising PHP upload limits (default 2M is too small for Aadhaar e-KYC ZIPs and Rules & Regulations PDFs)"
for ini in /etc/php/${PHP_VERSION}/fpm/php.ini /etc/php/${PHP_VERSION}/cli/php.ini; do
  sed -i \
    -e "s/^upload_max_filesize.*/upload_max_filesize = 20M/" \
    -e "s/^post_max_size.*/post_max_size = 25M/" \
    "${ini}"
done
systemctl restart php${PHP_VERSION}-fpm

echo "==> Installing MySQL"
apt-get install -y mysql-server
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> Installing Redis"
apt-get install -y redis-server
sed -i "s/^# requirepass foobared/requirepass ${REDIS_PASS}/" /etc/redis/redis.conf
sed -i "s/^requirepass .*/requirepass ${REDIS_PASS}/" /etc/redis/redis.conf
systemctl restart redis-server
systemctl enable redis-server

echo "==> Installing Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> Installing Node.js LTS (for Vite asset builds)"
curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
apt-get install -y nodejs

echo "==> Installing Certbot (Let's Encrypt)"
apt-get install -y certbot python3-certbot-nginx

echo "==> Creating deploy user"
if ! id -u "${DEPLOY_USER}" >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" "${DEPLOY_USER}"
  usermod -aG www-data "${DEPLOY_USER}"
  mkdir -p /home/${DEPLOY_USER}/.ssh
  cp /root/.ssh/authorized_keys /home/${DEPLOY_USER}/.ssh/ 2>/dev/null || true
  chown -R ${DEPLOY_USER}:${DEPLOY_USER} /home/${DEPLOY_USER}/.ssh
  chmod 700 /home/${DEPLOY_USER}/.ssh
  chmod 600 /home/${DEPLOY_USER}/.ssh/authorized_keys 2>/dev/null || true
fi

echo "==> Allowing ${DEPLOY_USER} to reload PHP-FPM / supervisor without a password (needed for automated deploys)"
cat > /etc/sudoers.d/${DEPLOY_USER}-deploy <<SUDOERS
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php${PHP_VERSION}-fpm
SUDOERS
chmod 440 /etc/sudoers.d/${DEPLOY_USER}-deploy

echo "==> Firewall (ufw)"
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

echo "==> Setting up ${DEPLOY_USER}'s SSH key for cloning the (private) GitHub repo"
DEPLOY_SSH_DIR="/home/${DEPLOY_USER}/.ssh"
DEPLOY_KEY="${DEPLOY_SSH_DIR}/id_ed25519"
if [ ! -f "${DEPLOY_KEY}" ]; then
  sudo -u "${DEPLOY_USER}" ssh-keygen -t ed25519 -N "" -f "${DEPLOY_KEY}" -C "${DEPLOY_USER}@$(hostname)"
fi
sudo -u "${DEPLOY_USER}" ssh-keyscan -H github.com >> "${DEPLOY_SSH_DIR}/known_hosts" 2>/dev/null
chown "${DEPLOY_USER}:${DEPLOY_USER}" "${DEPLOY_SSH_DIR}/known_hosts"

if [ ! -d "${APP_DIR}/.git" ]; then
  echo ""
  echo "############################################################"
  echo "  ACTION NEEDED: add this as a GitHub Deploy Key"
  echo "  (repo is private) -> github repo -> Settings -> Deploy keys"
  echo "  -> Add deploy key -> paste the line below -> DO NOT tick"
  echo "  'Allow write access' (read-only is enough to clone/pull)."
  echo "############################################################"
  cat "${DEPLOY_KEY}.pub"
  echo "############################################################"
  read -rp "Press Enter once the deploy key has been added on GitHub... "
fi

echo "==> Cloning application repository"
mkdir -p "$(dirname "${APP_DIR}")"
if [ ! -d "${APP_DIR}/.git" ]; then
  sudo -u "${DEPLOY_USER}" git clone "${GIT_REPO}" "${APP_DIR}"
fi

echo "==> Nginx site config"
sed -e "s/__DOMAIN__/${DOMAIN}/g" -e "s/__PHP_VERSION__/${PHP_VERSION}/g" -e "s#__APP_DIR__#${APP_DIR}#g" \
  "${APP_DIR}/deploy/nginx.conf.template" > "/etc/nginx/sites-available/${DOMAIN}"
ln -sf "/etc/nginx/sites-available/${DOMAIN}" "/etc/nginx/sites-enabled/${DOMAIN}"
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "==> Supervisor queue worker"
sed -e "s#__APP_DIR__#${APP_DIR}#g" -e "s/__DEPLOY_USER__/${DEPLOY_USER}/g" -e "s/__PHP_VERSION__/${PHP_VERSION}/g" \
  "${APP_DIR}/deploy/supervisor-worker.conf.template" > /etc/supervisor/conf.d/mp-sepaktakraw-worker.conf
supervisorctl reread
supervisorctl update

echo ""
echo "############################################################"
echo "  Base server setup complete. Remaining MANUAL steps:"
echo "############################################################"
echo "1. As ${DEPLOY_USER}, create the .env file:"
echo "     su - ${DEPLOY_USER}"
echo "     cd ${APP_DIR}"
echo "     cp .env.production.example .env"
echo "   ...then fill in these values (generated for you just now):"
echo "     DB_DATABASE=${DB_NAME}"
echo "     DB_USERNAME=${DB_USER}"
echo "     DB_PASSWORD=${DB_PASS}"
echo "     REDIS_PASSWORD=${REDIS_PASS}"
echo "   ...plus APP_URL, MAIL_*, RAZORPAY_* (see docs/DEPLOYMENT.md)."
echo ""
echo "2. Then run the first deploy (as ${DEPLOY_USER}):"
echo "     cd ${APP_DIR} && bash deploy/deploy.sh --first-run"
echo ""
echo "3. Once DNS for ${DOMAIN} points at this server's IP, issue SSL:"
echo "     certbot --nginx -d ${DOMAIN} -d www.${DOMAIN} -m ${LETSENCRYPT_EMAIL} --agree-tos --redirect"
echo ""
echo "SAVE THESE CREDENTIALS NOW — they are not printed again:"
echo "  DB_PASSWORD=${DB_PASS}"
echo "  REDIS_PASSWORD=${REDIS_PASS}"
echo "############################################################"
