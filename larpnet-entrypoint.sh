#!/bin/sh
set -e

# The Friendica entrypoint only rsyncs /usr/src/friendica → /var/www/html on
# version upgrades. For larpnet-only redeployments (same Friendica version),
# the sync is skipped and stale files stay on the volume. This wrapper
# unconditionally copies our patched files on every container start.
if [ -f /var/www/html/index.php ]; then
  for f in \
    src/Protocol/ActivityPub/Transmitter.php \
    src/Worker/Notifier.php \
    src/Content/Item.php \
    src/Core/ACL.php \
    src/Model/Item.php \
    src/Object/Post.php \
    src/Module/Item/Compose.php \
    src/Module/Item/Display.php \
    src/Module/Post/Share.php \
    src/Module/Privacy/PermissionTooltip.php \
    src/Module/Manifest.php \
    src/App/Page.php \
    src/Worker/NtfyPush.php \
    src/Model/Subscription.php \
    src/Module/FriendSuggest.php \
    src/Module/Api/Mastodon/Accounts/UpdateCredentials.php \
    src/Module/Api/Mastodon/Conversations.php \
    src/Module/Api/Mastodon/Conversations/Read.php \
    src/Factory/Api/Mastodon/Conversation.php \
    src/Module/Api/Twitter/DirectMessagesEndpoint.php
  do
    cp "/usr/src/friendica/$f" "/var/www/html/$f"
  done

  for addon in larpnet_banner larpnet_calendar larpnet_wifi; do
    cp -r "/usr/src/friendica/addon/${addon}" "/var/www/html/addon/"
  done

  for theme in larpnet larpnet_notifications; do
    cp -r "/usr/src/friendica/view/theme/${theme}" "/var/www/html/view/theme/"
  done
fi

exec /entrypoint.sh "$@"
