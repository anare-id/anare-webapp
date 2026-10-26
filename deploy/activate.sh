#!/bin/bash
set -Eeuo pipefail
umask 027
INBOX="${ANARE_INBOX:-/home/anaj8418/.deploy-anare}"
ROOT="${ANARE_ROOT:-/home/anaj8418/anare-dev}"
PHP="${ANARE_PHP:-/usr/local/bin/ea-php84}"
PUBLIC="${ANARE_PUBLIC:-/home/anaj8418/public_html/anare-dev/current/public}"
mkdir -p "$ROOT/releases" "$ROOT/shared/storage/app/public" "$ROOT/shared/storage/framework/"{cache/data,sessions,views} "$ROOT/shared/storage/logs"
chmod 0711 "$ROOT" "$ROOT/shared" "$ROOT/shared/storage" "$ROOT/shared/storage/app"
chmod 0755 "$ROOT/releases" "$ROOT/shared/storage/app/public"
exec 9>"$ROOT/deploy.lock"
flock -n 9 || exit 0
exec >>"$ROOT/deploy.log" 2>&1
[[ -f "$INBOX/ready.json" ]] || exit 0
INFO="$("$PHP" -r '$r=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);if(!preg_match("/^[a-f0-9]{40}$/D",$r["sha"]??"")||!preg_match("/^[a-f0-9]{64}$/D",$r["checksum"]??""))exit(1);echo $r["sha"]." ".$r["checksum"];' "$INBOX/ready.json")"
read -r SHA CHECKSUM <<< "$INFO"
ARCHIVE="$INBOX/$SHA.tar.gz"
[[ "$(cat "$ROOT/deployed-sha" 2>/dev/null || true)" != "$SHA" ]] || exit 0
[[ -f "$ARCHIVE" ]] || exit 0
printf '%s  %s\n' "$CHECKSUM" "$ARCHIVE" | sha256sum --check --status
RELEASE="$ROOT/releases/$SHA"
[[ ! -e "$RELEASE" ]] || { echo "Release already exists; refusing overwrite: $SHA"; exit 1; }
mkdir "$RELEASE"
if tar -tzf "$ARCHIVE" | grep -Eq '(^/|(^|/)\.\.(/|$))'; then echo 'Unsafe archive path'; exit 1; fi
chmod 0755 "$RELEASE"
tar -xzf "$ARCHIVE" --no-same-owner -C "$RELEASE"
find "$RELEASE/public" -type d -exec chmod 0755 {} +
find "$RELEASE/public" -type f -exec chmod 0644 {} +
[[ -f "$RELEASE/artisan" && -f "$RELEASE/vendor/autoload.php" && -f "$ROOT/shared/.env" ]] || { echo 'Missing release files/environment'; exit 1; }
ln -s "$ROOT/shared/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage"
ln -s "$ROOT/shared/storage" "$RELEASE/storage"
ln -s "$ROOT/shared/storage/app/public" "$RELEASE/public/storage"
mkdir -p "$RELEASE/bootstrap/cache"
cd "$RELEASE"
"$PHP" artisan migrate --force --no-interaction
if [[ ! -f "$ROOT/initialized" ]]; then
    "$PHP" artisan db:seed --force --no-interaction
    "$PHP" "$INBOX/provision.php"
    touch "$ROOT/initialized"
fi
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache
printf '{"sha":"%s"}\n' "$SHA" > public/deployment.json
chmod 0644 public/deployment.json
PREVIOUS="$(readlink "$ROOT/current" || true)"
if [[ -d "$ROOT/current" && ! -L "$ROOT/current" ]]; then rmdir "$ROOT/current/public"; rmdir "$ROOT/current"; fi
ln -s "$RELEASE" "$ROOT/current.next"
mv -Tf "$ROOT/current.next" "$ROOT/current"
if [[ ! -L "$PUBLIC" ]]; then
    mv "$PUBLIC" "$ROOT/public-before-laravel-$(date +%Y%m%d%H%M%S)"
    ln -s "$ROOT/current/public" "$PUBLIC"
fi
if ! curl --fail --silent --show-error --max-time 30 --resolve dev.anare.co.id:80:203.175.8.76 http://dev.anare.co.id/up >/dev/null; then
    if [[ -n "$PREVIOUS" ]]; then ln -s "$PREVIOUS" "$ROOT/current.rollback"; mv -Tf "$ROOT/current.rollback" "$ROOT/current"; fi
    echo "Health check failed; previous code restored. Database migrations are not automatically reversed."
    exit 1
fi
printf '%s\n' "$SHA" > "$ROOT/deployed-sha"
printf '%s Deployed %s previous=%s\n' "$(date -Is)" "$SHA" "$PREVIOUS"
rm -f "$INBOX/ready.json" "$ARCHIVE"
