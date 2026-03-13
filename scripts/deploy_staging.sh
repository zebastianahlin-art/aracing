#!/usr/bin/env bash
set -Eeuo pipefail

APP_NAME="a-racing-staging"
SRC_DIR="/home/administrator/a-racing"
BASE_DIR="/var/www/${APP_NAME}"
RELEASES_DIR="${BASE_DIR}/releases"
SHARED_DIR="${BASE_DIR}/shared"
CURRENT_LINK="${BASE_DIR}/current"
ENV_FILE="${SHARED_DIR}/.env"
PHP_FPM_SERVICE="php8.4-fpm"
NGINX_SERVICE="nginx"
SMOKE_BASE_URL_DEFAULT="http://81.88.25.152:8088"

usage() {
  cat <<'EOF'
Usage:
  bash scripts/deploy_staging.sh

Valfria env-variabler:
  RELEASE_NAME=20260313-5
  SRC_DIR=/home/administrator/a-racing
  SMOKE_BASE_URL=http://81.88.25.152:8088
  SKIP_SMOKE=1
  SKIP_SERVICES=1
EOF
}

log() {
  printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

fail() {
  printf '\n[ERROR] %s\n' "$*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "Kommando saknas: $1"
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

require_cmd rsync
require_cmd composer
require_cmd php
require_cmd readlink

RELEASE_NAME="${RELEASE_NAME:-$(date '+%Y%m%d-%H%M%S')}"
SRC_DIR="${SRC_DIR:-/home/administrator/a-racing}"
REL_DIR="${RELEASES_DIR}/${RELEASE_NAME}"
SMOKE_BASE_URL="${SMOKE_BASE_URL:-$SMOKE_BASE_URL_DEFAULT}"

[[ -d "$SRC_DIR" ]] || fail "SRC_DIR finns inte: $SRC_DIR"
[[ -f "${SRC_DIR}/composer.json" ]] || fail "composer.json saknas i SRC_DIR: $SRC_DIR"
[[ -f "${SRC_DIR}/public/index.php" ]] || fail "public/index.php saknas i SRC_DIR: $SRC_DIR"
[[ -f "$ENV_FILE" ]] || fail "Shared env-fil saknas: $ENV_FILE"

PREVIOUS_TARGET=""
if [[ -L "$CURRENT_LINK" ]]; then
  PREVIOUS_TARGET="$(readlink -f "$CURRENT_LINK" || true)"
fi

rollback() {
  local exit_code=$?
  if [[ $exit_code -ne 0 ]]; then
    printf '\n[ROLLBACK] Deploy misslyckades.\n' >&2
    if [[ -n "$PREVIOUS_TARGET" && -d "$PREVIOUS_TARGET" ]]; then
      sudo ln -sfn "$PREVIOUS_TARGET" "$CURRENT_LINK" || true
      printf '[ROLLBACK] current återställd till %s\n' "$PREVIOUS_TARGET" >&2
      if [[ "${SKIP_SERVICES:-0}" != "1" ]]; then
        sudo systemctl restart "$PHP_FPM_SERVICE" || true
        sudo systemctl reload "$NGINX_SERVICE" || true
      fi
    fi
  fi
  exit $exit_code
}
trap rollback ERR

log "Startar staging deploy"
log "Källkod: $SRC_DIR"
log "Release: $REL_DIR"

sudo mkdir -p "$RELEASES_DIR" "$SHARED_DIR"
sudo mkdir -p "$REL_DIR"

log "Kopierar filer till ny release"
sudo rsync -a --delete \
  --exclude=".git" \
  --exclude=".github" \
  --exclude="node_modules" \
  --exclude="vendor" \
  --exclude=".env" \
  "$SRC_DIR"/ "$REL_DIR"/

log "Installerar Composer dependencies"
cd "$REL_DIR"
composer install --no-interaction --prefer-dist

log "Skapar symlink till shared .env"
sudo ln -sfn "$ENV_FILE" "$REL_DIR/.env"

log "Skapar nödvändiga kataloger"
sudo mkdir -p "$REL_DIR/storage/cache"
sudo mkdir -p "$REL_DIR/storage/logs"
sudo mkdir -p "$REL_DIR/storage/imports"
sudo mkdir -p "$REL_DIR/storage/sessions"
sudo mkdir -p "$REL_DIR/public/uploads/product-images"

log "Sätter ägare och rättigheter"
sudo chown -R www-data:www-data "$REL_DIR/storage"
sudo chown -R www-data:www-data "$REL_DIR/public/uploads"
sudo find "$REL_DIR/storage" -type d -exec chmod 775 {} \;
sudo find "$REL_DIR/storage" -type f -exec chmod 664 {} \;
sudo find "$REL_DIR/public/uploads" -type d -exec chmod 775 {} \;
sudo find "$REL_DIR/public/uploads" -type f -exec chmod 664 {} \;

log "Pekar om current till ny release"
sudo ln -sfn "$REL_DIR" "$CURRENT_LINK"

if [[ "${SKIP_SERVICES:-0}" != "1" ]]; then
  log "Startar om PHP-FPM och reloadar Nginx"
  sudo systemctl restart "$PHP_FPM_SERVICE"
  sudo systemctl reload "$NGINX_SERVICE"
fi

log "Verifierar release"
readlink -f "$CURRENT_LINK"
php "$CURRENT_LINK/scripts/migrate.php" --status
sudo -u www-data php "$CURRENT_LINK/scripts/staging_doctor.php"

if [[ "${SKIP_SMOKE:-0}" != "1" ]]; then
  log "Kör smoke test mot $SMOKE_BASE_URL"
  SMOKE_BASE_URL="$SMOKE_BASE_URL" php "$CURRENT_LINK/scripts/staging_smoke_test.php"
fi

log "Deploy klar"
printf '\nNuvarande release: %s\n' "$(readlink -f "$CURRENT_LINK")"
