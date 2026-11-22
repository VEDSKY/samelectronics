#!/bin/sh
# ---------------------------------------------------------------
# sentinel.sh — the script that puts the site back, no matter what
# was done to the sandbox.
#
# It trusts nothing: every run it wipes the live sandbox tree and the
# sandbox sqlite database and copies the pristine snapshot over them.
# Run it on a loop (or via `php artisan sentinel`) and the terminal's
# "hackable" surface is restored on a fixed interval.
#
# Usage:
#   scripts/sentinel.sh                 # loop, 8s interval
#   scripts/sentinel.sh --once          # single restore
#   INTERVAL=3 scripts/sentinel.sh      # custom interval
# ---------------------------------------------------------------
set -eu

DIR="$(cd "$(dirname "$0")/.." && pwd)"
PRISTINE="$DIR/storage/app/sandbox.pristine"
LIVE="$DIR/storage/app/sandbox"
DB="$DIR/storage/app/sandbox.db"
DB_PRISTINE="$DIR/storage/app/sandbox.db.pristine"
LOG="$DIR/storage/app/sandbox.intrusion.log"
INTERVAL="${INTERVAL:-8}"

stamp() { date '+%H:%M:%S'; }

restore() {
    [ -d "$PRISTINE" ] || { echo "$(stamp) sentinel: no pristine snapshot; run 'php artisan terminal:sandbox install'"; return 1; }

    rm -rf "$LIVE"
    mkdir -p "$LIVE"
    cp -R "$PRISTINE/." "$LIVE/"
    if [ -f "$DB_PRISTINE" ]; then
        cp "$DB_PRISTINE" "$DB"
    fi

    FILES=$(find "$LIVE" -type f | wc -l | tr -d ' ')
    echo "$(stamp) integrity restored by sentinel.sh · $FILES files" | tee -a "$LOG"
}

if [ "${1:-}" = "--once" ]; then
    restore
    exit 0
fi

echo "sentinel.sh online · interval ${INTERVAL}s · ctrl-c to stop"
while :; do
    restore
    sleep "$INTERVAL"
done
