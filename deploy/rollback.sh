#!/bin/bash
set -Eeuo pipefail
umask 027
ROOT="${ANARE_ROOT:-/home/anaj8418/anare-dev}"
SHA="${1:-}"
[[ "$SHA" =~ ^[a-f0-9]{40}$ ]] || { echo 'Usage: rollback.sh <40-character deployed commit>'; exit 1; }
TARGET="$ROOT/releases/$SHA"
[[ -f "$TARGET/artisan" && -f "$TARGET/public/deployment.json" && -L "$TARGET/.env" ]] || { echo 'Unknown or incomplete release'; exit 1; }
exec 9>"$ROOT/deploy.lock"
flock -n 9 || { echo 'Deployment already in progress'; exit 1; }
PREVIOUS="$(readlink "$ROOT/current")"
ln -s "$TARGET" "$ROOT/current.rollback"
mv -Tf "$ROOT/current.rollback" "$ROOT/current"
if ! curl --fail --silent --show-error --max-time 30 --resolve dev.anare.co.id:80:203.175.8.76 http://dev.anare.co.id/up >/dev/null; then
    ln -s "$PREVIOUS" "$ROOT/current.restore"
    mv -Tf "$ROOT/current.restore" "$ROOT/current"
    echo 'Rollback target failed health check; original pointer restored.'
    exit 1
fi
printf '%s\n' "$SHA" > "$ROOT/deployed-sha"
printf '%s Rollback %s previous=%s\n' "$(date -Is)" "$SHA" "$PREVIOUS" >> "$ROOT/deploy.log"
echo "Code switched to $SHA; database and persistent storage unchanged."
