#!/usr/bin/env bash
set -euo pipefail

# You choose the Railway Volume mount path in the Railway UI.
# Mount it to /data (recommended) and keep this default.
: "${RAILWAY_VOLUME_PATH:=/data}"

WP_ROOT="/var/www/html"
WP_CONTENT="${WP_ROOT}/wp-content"
PERSIST_CONTENT="${RAILWAY_VOLUME_PATH}/wp-content"

mkdir -p "${RAILWAY_VOLUME_PATH}"

# If wp-content isn't persisted yet, seed it from the image (first run)
if [ ! -d "${PERSIST_CONTENT}" ]; then
  mkdir -p "${PERSIST_CONTENT}"
  if [ -d "${WP_CONTENT}" ]; then
    cp -a "${WP_CONTENT}/." "${PERSIST_CONTENT}/" || true
  fi
fi

# Replace wp-content with a symlink to the persisted location
if [ -e "${WP_CONTENT}" ] && [ ! -L "${WP_CONTENT}" ]; then
  rm -rf "${WP_CONTENT}"
fi
ln -sfn "${PERSIST_CONTENT}" "${WP_CONTENT}"

# Ensure Apache can write uploads/plugins/themes
chown -R www-data:www-data "${PERSIST_CONTENT}" || true

# Hand off to the official WordPress entrypoint (sets up wp-config.php, etc.)
exec docker-entrypoint.sh "$@"
