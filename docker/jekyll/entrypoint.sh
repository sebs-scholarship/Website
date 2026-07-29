#!/bin/bash
#
# Remap the baked-in `jekyll` user to the caller's UID/GID so files Jekyll
# writes onto the bind-mounted source are owned by the host user, then drop
# privileges and run the requested command.
set -e

# Default to the jekyll user's own IDs when the caller doesn't override them.
JEKYLL_UID="${JEKYLL_UID:-$(id -u jekyll)}"
JEKYLL_GID="${JEKYLL_GID:-$(id -g jekyll)}"

current_uid="$(id -u jekyll)"
current_gid="$(id -g jekyll)"

if [ "$JEKYLL_GID" != "$current_gid" ]; then
  groupmod -o -g "$JEKYLL_GID" jekyll
fi

if [ "$JEKYLL_UID" != "$current_uid" ]; then
  usermod -o -u "$JEKYLL_UID" jekyll
fi

# Make the bundle cache and site data dir writable by the (possibly remapped)
# user. BUNDLE_HOME is a named volume that starts empty, so the recursive chown
# is cheap on first run and idempotent thereafter.
chown -R "$JEKYLL_UID:$JEKYLL_GID" "$BUNDLE_HOME" 2>/dev/null || true
chown "$JEKYLL_UID:$JEKYLL_GID" "$JEKYLL_DATA_DIR" 2>/dev/null || true

exec su-exec "$JEKYLL_UID:$JEKYLL_GID" "$@"
