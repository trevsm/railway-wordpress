#!/usr/bin/env bash
set -e

# wp-content persistence is handled in apache2-foreground-railway, which runs after the
# official entrypoint finishes unpacking WordPress (that unpack would otherwise replace a
# wp-content symlink with a real, non-persistent folder).

# IMPORTANT: call the real WordPress entrypoint by absolute path
exec /usr/local/bin/docker-entrypoint.sh "$@"
