#!/usr/bin/env bash
#
# Classybazar — local setup
#
# Installs and configures both halves of the stack. Safe to re-run: it never
# overwrites an existing .env, and it will not wipe a database that already
# has data unless you pass --fresh.
#
#   ./setup.sh            first run, or to repair an install
#   ./setup.sh --fresh    drop the database and reseed from scratch
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$ROOT/api"
CLIENT_DIR="$ROOT/client"
FRESH=false

[[ "${1:-}" == "--fresh" ]] && FRESH=true

# ── output helpers ────────────────────────────────────────────────────────────
BOLD=$'\033[1m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'; RED=$'\033[31m'; OFF=$'\033[0m'
step() { printf "\n${BOLD}▶ %s${OFF}\n" "$1"; }
ok()   { printf "  ${GREEN}✓${OFF} %s\n" "$1"; }
warn() { printf "  ${YELLOW}!${OFF} %s\n" "$1"; }
die()  { printf "\n  ${RED}✗ %s${OFF}\n\n" "$1" >&2; exit 1; }

# ── prerequisites ─────────────────────────────────────────────────────────────
step "Checking prerequisites"

command -v php >/dev/null      || die "PHP not found. Install Laravel Herd (https://herd.laravel.com) or run: brew install php"
command -v composer >/dev/null || die "Composer not found. Install it with: brew install composer"
command -v node >/dev/null     || die "Node.js not found. Install it with: brew install node"
command -v npm >/dev/null      || die "npm not found — it ships with Node.js."

PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
  || die "PHP $PHP_VERSION found, but Laravel 12 needs PHP 8.2+."
ok "PHP $PHP_VERSION"

NODE_MAJOR=$(node -p 'process.versions.node.split(".")[0]')
[ "$NODE_MAJOR" -ge 20 ] || die "Node $(node -v) found, but Next.js 16 needs Node 20+."
ok "Node $(node -v)"

# pdo_sqlite is the one extension people are actually missing.
php -m | grep -qi '^pdo_sqlite$' \
  || die "PHP is missing the pdo_sqlite extension. With Herd it is built in; with Homebrew PHP, reinstall with: brew reinstall php"
ok "pdo_sqlite enabled"

# ── backend ───────────────────────────────────────────────────────────────────
step "Setting up the API (Laravel)"
cd "$API_DIR"

if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist
  ok "Composer dependencies installed"
else
  ok "Composer dependencies already present"
fi

if [ ! -f .env ]; then
  cp .env.example .env
  ok ".env created from .env.example"
else
  ok ".env already exists (left untouched)"
fi

grep -q '^APP_KEY=base64:' .env || { php artisan key:generate --ansi; ok "Application key generated"; }

# SQLite means no MySQL server, no credentials, no Docker.
touch database/database.sqlite
ok "SQLite database file ready"

if [ "$FRESH" = true ]; then
  warn "--fresh: dropping all tables and reseeding"
  php artisan migrate:fresh --seed --no-interaction --ansi
else
  php artisan migrate --seed --no-interaction --ansi
fi

[ -L public/storage ] || php artisan storage:link --ansi
ok "Storage symlink in place"

# Publishes the admin panel's CSS/JS into public/. composer install normally
# does this via filament:upgrade, but running it explicitly costs nothing and
# saves an unstyled panel if that hook was skipped.
php artisan filament:assets --ansi > /dev/null
ok "Admin panel assets published"

php artisan optimize:clear >/dev/null 2>&1 || true

# ── frontend ──────────────────────────────────────────────────────────────────
step "Setting up the storefront (Next.js)"
cd "$CLIENT_DIR"

if [ ! -d node_modules ]; then
  npm install --no-audit --no-fund
  ok "npm dependencies installed"
else
  ok "npm dependencies already present"
fi

if [ ! -f .env.local ]; then
  cp .env.example .env.local
  ok ".env.local created"
else
  ok ".env.local already exists (left untouched)"
fi

# ── done ──────────────────────────────────────────────────────────────────────
step "Setup complete"
cat <<INFO

  Start everything with:   ${BOLD}./start.sh${OFF}

  Storefront   http://localhost:3000
  API          http://127.0.0.1:8000/api

  Admin        admin@classybazar.com / password
  Customer     customer@classybazar.com / password

INFO
