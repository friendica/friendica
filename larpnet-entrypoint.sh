#!/bin/sh
set -e

# The Friendica entrypoint only rsyncs /usr/src/friendica → /var/www/html on
# version upgrades. For larpnet-only redeployments (same Friendica version),
# the sync is skipped and stale files stay on the volume. This wrapper
# unconditionally copies our patched files on every container start.
if [ -f /var/www/html/index.php ]; then
  for f in \
    src/Model/Item.php \
    src/Object/Post.php \
    src/Module/Item/Display.php \
    src/Module/Post/Share.php \
    src/Module/Privacy/PermissionTooltip.php \
    src/Worker/NtfyPush.php \
    src/Model/Subscription.php
  do
    cp "/usr/src/friendica/$f" "/var/www/html/$f"
  done
fi

exec /entrypoint.sh "$@"
