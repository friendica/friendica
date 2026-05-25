/* Service Worker – larpnet push notifications */

self.addEventListener('install', function (event) { self.skipWaiting(); });
self.addEventListener('activate', function (event) { event.waitUntil(clients.claim()); });

self.addEventListener('push', function (event) {
    if (!event.data) return;

    let data = {};
    try { data = event.data.json(); } catch (e) { return; }

    // ntfy web push payload: { event: "message", subscription_id: "...", message: {...} }
    if (data.event === 'subscription_expiring') {
        // ntfy signals that the subscription will expire soon — silently skip
        return;
    }

    const msg = data.message || data;

    const title   = msg.title || 'larpnet';
    const options = {
        body:  msg.message || msg.body || '',
        icon:  msg.icon   || '/images/friendica.svg',
        badge: '/images/friendica.svg',
        tag:   String(msg.id || ''),
        data:  { url: msg.click || msg.url || '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const target = (event.notification.data || {}).url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
            for (const client of list) {
                if (client.url.startsWith(self.location.origin) && 'focus' in client) {
                    client.navigate(target);
                    return client.focus();
                }
            }
            return clients.openWindow(target);
        })
    );
});
