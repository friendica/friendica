(function () {
    'use strict';

    const cfg = window.LarpnetPush;
    if (!cfg) { console.warn('[push] LarpnetPush config missing'); return; }

    if (!('Notification' in window)) {
        console.warn('[push] Notifications not supported');
        return;
    }

    function urlBase64ToUint8Array(base64) {
        const pad = '='.repeat((4 - base64.length % 4) % 4);
        const b64 = (base64 + pad).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(b64);
        const arr = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    function toBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let bin = '';
        for (let i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    async function registerWithNtfy(sub) {
        const key  = sub.getKey('p256dh');
        const auth = sub.getKey('auth');

        // URL-based auth avoids CORS preflight issues with Authorization header
        const authSuffix = cfg.ntfyToken ? ('?auth=' + btoa('Bearer ' + cfg.ntfyToken)) : '';

        // ntfy v2 web push endpoint: POST /v1/webpush with flat fields
        const res = await fetch(cfg.ntfyUrl + '/v1/webpush' + authSuffix, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                endpoint: sub.endpoint,
                p256dh:   toBase64Url(key),
                auth:     toBase64Url(auth),
                topics:   [cfg.ntfyTopic],
            }),
        });

        if (!res.ok) {
            console.error('[push] ntfy subscription error', res.status, await res.text());
        } else {
            console.log('[push] Web Push registered with ntfy — background push active');
        }
    }

    function startSSE(reg) {
        // ntfy token auth via URL — no custom headers needed, no Google/FCM
        const authB64 = btoa(':' + cfg.ntfyToken);
        const url = cfg.ntfyUrl + '/' + cfg.ntfyTopic + '/sse?auth=' + authB64;
        const es  = new EventSource(url);

        es.addEventListener('open', function () {
            console.log('[push] SSE connected — in-tab notifications active');
        });

        es.addEventListener('message', async function (e) {
            try {
                const data = JSON.parse(e.data);
                if (data.event !== 'message') return;

                const title   = data.title   || 'larpnet';
                const options = {
                    body:  data.message || '',
                    icon:  data.icon    || '/images/friendica.svg',
                    badge: '/images/friendica.svg',
                    tag:   String(data.id || ''),
                    data:  { url: data.click || '/' },
                };

                if (reg) {
                    await reg.showNotification(title, options);
                } else {
                    new Notification(title, options);
                }
            } catch (_) {}
        });

        es.onerror = function () {
            console.warn('[push] SSE dropped — browser will auto-reconnect');
        };
    }

    function waitForActivation(reg) {
        if (reg.active) return Promise.resolve(reg);
        return new Promise(function (resolve, reject) {
            const sw = reg.installing || reg.waiting;
            if (!sw) { reject(new Error('no SW')); return; }
            const t = setTimeout(function () { reject(new Error('SW timeout')); }, 15000);
            sw.addEventListener('statechange', function () {
                if (sw.state === 'activated')  { clearTimeout(t); resolve(reg); }
                if (sw.state === 'redundant')  { clearTimeout(t); reject(new Error('SW redundant')); }
            });
        });
    }

    async function init() {
        console.log('[push] init');

        const perm = Notification.permission === 'default'
            ? await Notification.requestPermission()
            : Notification.permission;

        if (perm !== 'granted') {
            console.warn('[push] Permission not granted:', perm);
            return;
        }

        let reg = null;

        if ('serviceWorker' in navigator) {
            try {
                // sw.js at root — natural scope /, works in all browsers
                reg = await navigator.serviceWorker.register(cfg.swUrl);
                reg = await waitForActivation(reg);
                console.log('[push] SW active, scope=', reg.scope);
            } catch (e) {
                console.warn('[push] SW failed:', e.message);
                reg = null;
            }
        }

        // Try Web Push (Firefox: Mozilla Push, Chrome: FCM)
        if (reg && 'PushManager' in window) {
            try {
                const existing = await reg.pushManager.getSubscription();
                if (existing) {
                    console.log('[push] Existing Web Push sub — re-registering with ntfy');
                    await registerWithNtfy(existing);
                    return;
                }

                console.log('[push] Subscribing to Web Push...');
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly:      true,
                    applicationServerKey: urlBase64ToUint8Array(cfg.vapidKey),
                });
                await registerWithNtfy(sub);
                return; // Web Push active — no SSE needed
            } catch (e) {
                console.warn('[push] Web Push failed:', e.message, '— falling back to SSE');
            }
        }

        // SSE fallback: works in Brave and any browser where Web Push is blocked
        startSSE(reg);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
