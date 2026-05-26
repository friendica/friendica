FROM friendica:2026.05-fpm
ARG LARPNET_VERSION=2

# Custom themes
COPY view/theme/larpnet               /usr/src/friendica/view/theme/larpnet
COPY view/theme/larpnet_notifications /usr/src/friendica/view/theme/larpnet_notifications

# Custom addons
COPY addon/larpnet_banner    /usr/src/friendica/addon/larpnet_banner
COPY addon/larpnet_calendar  /usr/src/friendica/addon/larpnet_calendar

# Core patches (NtfyPush worker + Subscription.php one-line patch)
COPY src/Worker/NtfyPush.php      /usr/src/friendica/src/Worker/NtfyPush.php
COPY src/Model/Subscription.php   /usr/src/friendica/src/Model/Subscription.php
