FROM friendica:2026.05-fpm

# Wrapper entrypoint: copies larpnet-patched files on every start,
# since the Friendica entrypoint only rsyncs on version upgrades.
COPY larpnet-entrypoint.sh /larpnet-entrypoint.sh
RUN chmod +x /larpnet-entrypoint.sh
ENTRYPOINT ["/larpnet-entrypoint.sh"]
CMD ["php-fpm"]

# Custom themes
COPY view/theme/larpnet               /usr/src/friendica/view/theme/larpnet
COPY view/theme/larpnet_notifications /usr/src/friendica/view/theme/larpnet_notifications

# Custom addons
COPY addon/larpnet_banner    /usr/src/friendica/addon/larpnet_banner
COPY addon/larpnet_calendar  /usr/src/friendica/addon/larpnet_calendar

# Core patches
COPY src/Worker/NtfyPush.php                      /usr/src/friendica/src/Worker/NtfyPush.php
COPY src/Model/Subscription.php                   /usr/src/friendica/src/Model/Subscription.php
COPY src/Model/Item.php                           /usr/src/friendica/src/Model/Item.php
COPY src/Object/Post.php                          /usr/src/friendica/src/Object/Post.php
COPY src/Module/Item/Display.php                  /usr/src/friendica/src/Module/Item/Display.php
COPY src/Module/Post/Share.php                    /usr/src/friendica/src/Module/Post/Share.php
COPY src/Module/Privacy/PermissionTooltip.php     /usr/src/friendica/src/Module/Privacy/PermissionTooltip.php
