#!/usr/bin/env bash
set -e

: "${RAILWAY_VOLUME_PATH:=/data}"

WP_ROOT=/var/www/html
WP_CONTENT=$WP_ROOT/wp-content
PERSIST_CONTENT=$RAILWAY_VOLUME_PATH/wp-content

mkdir -p "$PERSIST_CONTENT"

if [ ! -L "$WP_CONTENT" ]; then
  rm -rf "$WP_CONTENT"
  ln -s "$PERSIST_CONTENT" "$WP_CONTENT"
fi

chown -R www-data:www-data "$PERSIST_CONTENT"

# IMPORTANT: call the real WordPress entrypoint by absolute path
exec /usr/local/bin/docker-entrypoint.sh "$@"
