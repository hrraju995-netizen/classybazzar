#!/usr/bin/env bash
#
# Classybazar — stop anything left running on the project ports.
# Only needed if a server was backgrounded or start.sh was killed uncleanly.
#
set -uo pipefail

API_PORT="${API_PORT:-8000}"
WEB_PORT="${WEB_PORT:-3000}"

for port in "$API_PORT" "$WEB_PORT"; do
  pids=$(lsof -nP -tiTCP:"$port" -sTCP:LISTEN 2>/dev/null || true)
  if [ -n "$pids" ]; then
    echo "  Stopping port $port (pid: $pids)"
    # shellcheck disable=SC2086
    kill -TERM $pids 2>/dev/null || true
    sleep 1
    pids=$(lsof -nP -tiTCP:"$port" -sTCP:LISTEN 2>/dev/null || true)
    # shellcheck disable=SC2086
    [ -n "$pids" ] && kill -9 $pids 2>/dev/null || true
  else
    echo "  Port $port already free"
  fi
done

echo "  Done."
