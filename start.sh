#!/usr/bin/env bash
#
# Classybazar — start both servers
#
#   ./start.sh          development (Next.js dev server, hot reload)
#   ./start.sh --prod   production build served locally
#
# Ctrl+C stops both. Logs are written to logs/.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$ROOT/api"
CLIENT_DIR="$ROOT/client"
LOG_DIR="$ROOT/logs"
API_PORT="${API_PORT:-8000}"
WEB_PORT="${WEB_PORT:-3000}"
MODE="dev"

[[ "${1:-}" == "--prod" ]] && MODE="prod"

BOLD=$'\033[1m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'; RED=$'\033[31m'; OFF=$'\033[0m'
ok()   { printf "  ${GREEN}✓${OFF} %s\n" "$1"; }
warn() { printf "  ${YELLOW}!${OFF} %s\n" "$1"; }
die()  { printf "\n  ${RED}✗ %s${OFF}\n\n" "$1" >&2; exit 1; }

mkdir -p "$LOG_DIR"

[ -d "$API_DIR/vendor" ]      || die "API dependencies missing. Run ./setup.sh first."
[ -d "$CLIENT_DIR/node_modules" ] || die "Storefront dependencies missing. Run ./setup.sh first."

# Refuse to start on an occupied port rather than failing confusingly later.
port_busy() { lsof -nP -iTCP:"$1" -sTCP:LISTEN >/dev/null 2>&1; }
port_busy "$API_PORT" && die "Port $API_PORT is already in use. Stop it, or run: API_PORT=8001 ./start.sh"
port_busy "$WEB_PORT" && die "Port $WEB_PORT is already in use. Stop it, or run: WEB_PORT=3001 ./start.sh"

PIDS=()
cleanup() {
  printf "\n  Shutting down...\n"
  for pid in "${PIDS[@]:-}"; do
    # Kill the whole process group — `next dev` spawns children.
    kill -TERM -"$pid" 2>/dev/null || kill -TERM "$pid" 2>/dev/null || true
  done
  wait 2>/dev/null || true
  printf "  Stopped.\n\n"
}
trap cleanup EXIT INT TERM

printf "\n${BOLD}Starting Classybazar (%s)${OFF}\n\n" "$MODE"

# ── API ───────────────────────────────────────────────────────────────────────
cd "$API_DIR"
set -m
php artisan serve --port="$API_PORT" > "$LOG_DIR/api.log" 2>&1 &
PIDS+=($!)
set +m

for i in $(seq 1 30); do
  if curl -sf -o /dev/null "http://127.0.0.1:$API_PORT/api/settings"; then
    ok "API        http://127.0.0.1:$API_PORT/api"
    break
  fi
  [ "$i" -eq 30 ] && { cat "$LOG_DIR/api.log"; die "API failed to start. See logs/api.log"; }
  sleep 1
done

# ── storefront ────────────────────────────────────────────────────────────────
cd "$CLIENT_DIR"

if [ "$MODE" = "prod" ]; then
  printf "  Building production bundle (this takes a minute)...\n"
  npm run build > "$LOG_DIR/build.log" 2>&1 || { tail -30 "$LOG_DIR/build.log"; die "Build failed. See logs/build.log"; }
  ok "Production build complete"
  set -m
  npm run start -- --port "$WEB_PORT" > "$LOG_DIR/web.log" 2>&1 &
  PIDS+=($!)
  set +m
else
  set -m
  npm run dev -- --port "$WEB_PORT" > "$LOG_DIR/web.log" 2>&1 &
  PIDS+=($!)
  set +m
fi

for i in $(seq 1 90); do
  if curl -sf -o /dev/null "http://127.0.0.1:$WEB_PORT"; then
    ok "Storefront http://localhost:$WEB_PORT"
    break
  fi
  [ "$i" -eq 90 ] && { tail -30 "$LOG_DIR/web.log"; die "Storefront failed to start. See logs/web.log"; }
  sleep 1
done

cat <<INFO

  ${BOLD}Both servers are running.${OFF}

  Storefront   http://localhost:$WEB_PORT
  API          http://127.0.0.1:$API_PORT/api
  Admin panel  http://127.0.0.1:$API_PORT/admin
  Logs         logs/api.log, logs/web.log

  Admin        admin@classybazar.com / password
  Customer     customer@classybazar.com / password

  Press Ctrl+C to stop both.

INFO

wait
