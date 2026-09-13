// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// @type {module}

/**
 * SPA navigation for Friendica, built on Unpoly's fragment updates.
 *
 * Unpoly handles link interception, history (push/pop) and DOM swapping
 * natively. This module only configures Unpoly to match Friendica's page
 * structure and wires the small pieces of Friendica-specific behavior that
 * have no Unpoly equivalent (auth redirects, the deploy-version safety net,
 * the loading indicator, and re-triggering existing lifecycle events so
 * `main.js` and theme code do not need to change).
 *
 * Plain links keep working without any of this: Unpoly only upgrades
 * navigation for browsers that load this module. GET forms (search/filter)
 * are submitted via SPA too, same as a link click. POST forms are not
 * auto-intercepted - see the submitSelectors comment in configureUnpoly().
 */

import { showTimeoutModal, cleanupTooltips } from './spa-ui-helpers.js';

const MAIN_TARGETS = [
  'nav#topbar-first',
  'div#topbar-second',
  'main',
  '#content-section',
  '#aside-section',
  '#right-aside-section',
];

// Every target normally must exist or Unpoly falls back to a full page load.
// nav#topbar-first is left as the one required target on purpose: a response
// without it isn't a normal page (error page, redirect, minimal layout), so a
// full load is the right fallback. The theme-specific ones are marked ":maybe"
// so a container missing on the current theme doesn't block swapping the ones
// that do exist.
const NAVIGATE_TARGET =
  'nav#topbar-first, div#topbar-second:maybe, main:maybe, ' +
  '#content-section:maybe, #aside-section:maybe, #right-aside-section:maybe';

const pendingScriptSyncs = new Map();
let lastScriptSync = Promise.resolve();

/**
 * Configure Unpoly's link matching and fragment targets to match the
 * containers Friendica's SPA mode has always swapped.
 */
function configureUnpoly() {
  // mainTargets is a fallback list (Unpoly swaps only the first match it
  // finds), used e.g. by up.reload(). NAVIGATE_TARGET below is what actually
  // makes plain navigation update all matching containers at once.
  up.layer.config.any.mainTargets = MAIN_TARGETS;
  up.layer.config.root.mainTargets = MAIN_TARGETS;
  up.fragment.config.navigateOptions.target = NAVIGATE_TARGET;

  up.fragment.config.navigateOptions.cache = false;
  up.fragment.config.navigateOptions.scroll = 'top';

  // Don't let Unpoly manage its own progress bar. loading-indicator.js drives
  // the same up.ProgressBar for every request via the up:request:load hook in
  // bindLoadingIndicatorHooks() (plus non-Unpoly actions like comment reloads),
  // so leaving this on would stack a second bar on SPA navigations.
  up.network.config.progressBar = false;

  // Download links (CSV/JSON exports etc.) return a non-HTML content type.
  // Checking event.response.isHTML() in an up:fragment:loaded listener and
  // calling event.skip() is not enough to stop Unpoly's default
  // renderableResponse() from throwing up.CannotParse - it runs right after
  // that event regardless of skip(). skipResponse runs *before* that check,
  // so it avoids the throw entirely and hands off to a plain browser
  // navigation instead, same as a non-SPA link.
  const defaultSkipResponse = up.fragment.config.skipResponse;
  up.fragment.config.skipResponse = function (props) {
    if (!props.response.isHTML()) {
      console.warn(
        '[spa-unpoly-nav] Non-HTML response for a SPA-followed link - add the `download` attribute to the link that requested',
        props.response.url,
        'to avoid this extra round-trip.'
      );
      window.location.href = props.response.url;
      return true;
    }
    return defaultSkipResponse(props);
  };

  up.link.config.followSelectors.push('a[href]');
  up.form.config.submitSelectors.push('form[method="get" i]');

  // Links Unpoly must not intercept. Fancybox and onclick links carry their
  // own behavior; anything else needing an exception uses up-follow="false"
  // on the link itself (e.g. the delegation links in Nav.php's templates).
  up.link.config.noFollowSelectors.push(
    '.modal-open',
    '[data-fancybox]',
    '[onclick]'
  );
}

/**
 * React to the raw response before Unpoly renders it: auth redirects,
 * timeouts, and a deploy-version safety net.
 */
function bindResponseHooks() {
  up.on('up:fragment:loaded', function (event) {
    if (event.renderOptions.navigate === false) {
      return;
    }

    const response = event.response;

    if (response.status === 401) {
      event.skip();
      window.location.href = (window.baseurl || '') + '/login?return_path=' + encodeURIComponent(response.url);
      return;
    }

    if (response.status === 504) {
      event.skip();
      showTimeoutModal();
      return;
    }

    const newDoc = new DOMParser().parseFromString(response.text, 'text/html');

    const serverVersion = newDoc.querySelector('[data-spa-version]')?.getAttribute('data-spa-version');
    if (serverVersion && window.__spa_router_version && serverVersion !== window.__spa_router_version) {
      event.skip();
      window.location.href = response.url;
      return;
    }

    syncHeadState(newDoc);
    syncStylesheets(newDoc);

    // Superseded navigations never get consumed in bindNavigationCompleted(),
    // so drop stale entries before they accumulate.
    if (pendingScriptSyncs.size > 8) {
      pendingScriptSyncs.clear();
    }
    lastScriptSync = syncOutOfBandScripts(newDoc);
    pendingScriptSyncs.set(response.url, lastScriptSync);

    if (typeof showProcessing === 'function') {
      showProcessing();
    }
  });
}

function resolveUrl(url) {
  return new URL(url, document.baseURI).toString();
}

/**
 * Adds elements the new page has that the document doesn't yet, and removes
 * ones the document has that the new page no longer needs, matching by the
 * resolved absolute URL in `urlAttr` ('href' or 'src'). Shared by
 * syncStylesheets and syncOutOfBandScripts, which both sync external
 * resources living outside Unpoly's swapped fragments and so have to manage
 * them by hand.
 *
 * `currentElements` is the full set to match against, so a URL already
 * present anywhere isn't added a second time. `removableElements` (defaults
 * to `currentElements`) is the subset this call may delete - callers pass a
 * narrower list to keep it away from elements Unpoly manages inside its
 * swapped containers. `addClone(newElement)` is called for each URL missing
 * from the document; its return value is collected and returned, letting
 * callers await any load promises it produces.
 */
function syncExternalResources(currentElements, urlAttr, newElements, addClone, removableElements) {
  removableElements = removableElements || currentElements;

  const currentUrls = new Set(
    currentElements
      .filter((el) => el.getAttribute(urlAttr))
      .map((el) => resolveUrl(el.getAttribute(urlAttr)))
  );

  const newUrls = new Set();
  const addResults = [];

  newElements.forEach((el) => {
    const url = el.getAttribute(urlAttr);
    if (!url) {
      return;
    }

    const absoluteUrl = resolveUrl(url);
    newUrls.add(absoluteUrl);

    if (!currentUrls.has(absoluteUrl)) {
      addResults.push(addClone(el));
    }
  });

  removableElements.forEach((el) => {
    const url = el.getAttribute(urlAttr);
    if (!url || el.hasAttribute('data-spa-persistent')) {
      return;
    }
    if (!newUrls.has(resolveUrl(url))) {
      el.remove();
    }
  });

  return addResults;
}

/**
 * Page-specific stylesheets registered via App\Page::registerStylesheet()
 * (e.g. fullcalendar.min.css for the calendar) render as <link> tags in
 * <head>, same as *_head.tpl scripts - Unpoly never touches <head>, so a
 * page navigated to via SPA is missing its stylesheet until this runs.
 * Stylesheets the new page no longer needs (e.g. leaving /calendar) are
 * removed so they don't accumulate for the rest of the session.
 */
function syncStylesheets(newDoc) {
  if (!newDoc.head) {
    return;
  }

  const newLinks = Array.from(newDoc.head.querySelectorAll('link[rel="stylesheet"]'));
  const currentLinks = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));

  syncExternalResources(currentLinks, 'href', newLinks, (link) => {
    const clone = document.createElement('link');
    Array.from(link.attributes).forEach((attr) => clone.setAttribute(attr.name, attr.value));
    document.head.appendChild(clone);
  });
}

/**
 * Unpoly only swaps the three body containers (nav#topbar-first,
 * div#topbar-second, main) and never touches <head> or content outside
 * them. Friendica loads page-specific scripts in both places: *_head.tpl
 * includes render into <head> (e.g. photos_head.tpl's mod_photos.js), and
 * App\Page::registerFooterScript() renders into the end of <body>, in
 * footer.tpl (e.g. Calendar/Show.php's fullcalendar.js/moment.js). Neither
 * ever loads when navigating to that page via SPA for the first time in a
 * session. Load any external <script src> found outside the swapped
 * containers that isn't already present, then re-run inline scripts;
 * scripts no longer needed by the new page are removed. Scripts inside the
 * swapped containers are left alone - Unpoly already reruns those itself.
 */
function syncOutOfBandScripts(newDoc) {
  if (!newDoc.head && !newDoc.body) {
    return Promise.resolve();
  }

  const headScripts = newDoc.head ? Array.from(newDoc.head.querySelectorAll('script')) : [];
  const bodyScripts = newDoc.body
    ? Array.from(newDoc.body.querySelectorAll('script')).filter(
        (script) => !script.closest(MAIN_TARGETS.join(', '))
      )
    : [];

  const externalScripts = [];
  const inlineScripts = [];

  [...headScripts, ...bodyScripts].forEach(function (script) {
    if (script.getAttribute('src')) {
      externalScripts.push(script);
      return;
    }

    const content = script.textContent.trim();
    if (content) {
      inlineScripts.push(content);
    }
  });

  const currentScripts = Array.from(document.querySelectorAll('script[src]'));
  const outOfBandScripts = currentScripts.filter(
    (script) => !script.closest(MAIN_TARGETS.join(', '))
  );

  const externalLoadPromises = syncExternalResources(currentScripts, 'src', externalScripts, (script) => {
    const clone = document.createElement('script');
    Array.from(script.attributes).forEach((attr) => clone.setAttribute(attr.name, attr.value));
    clone.async = false;
    const loadPromise = new Promise((resolve) => {
      clone.addEventListener('load', resolve);
      clone.addEventListener('error', () => {
        console.warn('[spa-unpoly-nav] Failed to load an out-of-band script:', clone.src);
        resolve();
      });
    });
    document.head.appendChild(clone);
    return loadPromise;
  }, outOfBandScripts);

  // One <script> element per source block. A SyntaxError is a parse error of
  // the whole element, so concatenating would let one malformed block stop
  // every other one from running, with nothing logged. The `try` wrapper also
  // gives each block its own scope, so a top-level `let`/`const`/`class` does
  // not clash with the same declaration from an earlier navigation (`var` and
  // function declarations hoist out and stay safe to repeat).
  const runInlineScripts = () => {
    inlineScripts.forEach((content) => {
      const scriptEl = document.createElement('script');
      scriptEl.textContent = `try {\n${content}\n} catch (e) { console.error('Error executing out-of-band script:', e); }`;
      document.head.appendChild(scriptEl);
      document.head.removeChild(scriptEl);
    });
  };

  return Promise.all(externalLoadPromises).then(runInlineScripts);
}

/**
 * `updateContent` and `localUser` are only rendered into <head>, which
 * Unpoly does not touch on navigation, but their values can differ per page
 * (e.g. whether polling is active). The synced marker element in head.tpl
 * carries them as data attributes; read those so code relying on
 * `window.updateContent`/`window.localUser` keeps seeing the right ones
 * after a navigation.
 */
function syncHeadState(newDoc) {
  const marker = newDoc.querySelector('[data-spa-version]');
  if (!marker) {
    return;
  }

  const updateContent = marker.getAttribute('data-update-content');
  if (updateContent !== null) {
    window.updateContent = JSON.parse(updateContent);
  }

  const localUser = marker.getAttribute('data-local-user');
  if (localUser !== null) {
    window.localUser = JSON.parse(localUser);
  }
}

function focusContentAfterNavigation() {
  const contentElement = document.getElementById('content') || document.getElementById('content-section');

  if (contentElement) {
    up.focus(contentElement, { force: true, preventScroll: true });
  }
}

/**
 * Fires once per completed navigation (including browser back/forward), by
 * dispatching spa:navigate and friends so main.js's onDocumentReady/
 * onWindowLoad helpers and theme.js's spa:navigate listener keep working.
 *
 * Hooks up:fragment:inserted rather than up:location:changed, which fires
 * *before* Unpoly inserts the new fragments. up:fragment:inserted fires
 * once per swapped fragment (three per navigation), so it is debounced via
 * a microtask to run once per navigation.
 */
function bindNavigationCompleted() {
  let microtaskScheduled = false;
  let isFirstBurst = true;

  function runCompletion(path, scriptSyncPromise) {
    Promise.resolve(scriptSyncPromise).then(() => {
      cleanupTooltips();

      focusContentAfterNavigation();

      window.dispatchEvent(new CustomEvent('spa:navigate', { detail: { path } }));
      window.dispatchEvent(new CustomEvent('spa:initInfiniteScroll'));

      if (typeof initInfiniteScroll === 'function') {
        initInfiniteScroll();
      }
      if (typeof NavUpdate === 'function') {
        NavUpdate();
      }
      if (typeof hideLoading === 'function') {
        hideLoading();
      }
    });
  }

  up.on('up:fragment:inserted', function () {
    if (microtaskScheduled) {
      return;
    }
    microtaskScheduled = true;
    const path = window.location.pathname;
    const url = window.location.href;

    queueMicrotask(() => {
      microtaskScheduled = false;

      if (isFirstBurst) {
        isFirstBurst = false;
        return;
      }

      const scriptSyncPromise = pendingScriptSyncs.get(url) || lastScriptSync;
      pendingScriptSyncs.delete(url);

      runCompletion(path, scriptSyncPromise);
    });
  });
}

function bindLoadingIndicatorHooks() {
  up.on('up:request:load', function () {
    if (typeof showFetching === 'function') {
      showFetching();
    }
  });

  up.on('up:request:aborted', function () {
    if (typeof hideLoading === 'function') {
      hideLoading();
    }
  });

  up.on('up:request:offline', function (event) {
    if (typeof hideLoading === 'function') {
      hideLoading();
    }

    if (event.request.method === 'GET') {
      console.warn('[spa-unpoly-nav] Network error for a SPA navigation - falling back to a full page load:', event.request.url);
      window.location.href = event.request.url;
    }
  });
}

/**
 * spa:document:ready/spa:window:load previously fired once on initial page
 * load via jQuery's ready/load handlers. Keep emitting them so lifecycle
 * consumers registered through window.onDocumentReady/onWindowLoad
 * (view/js/main.js) do not need to change.
 */
function bindInitialLifecycleEvents() {
  const fireDocumentReady = () => window.dispatchEvent(new CustomEvent('spa:document:ready'));

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fireDocumentReady);
  } else {
    fireDocumentReady();
  }

  window.addEventListener('load', () => window.dispatchEvent(new CustomEvent('spa:window:load')));
}

function initSPANavigation() {
  if (window.__friendica_unpoly_nav_initialized) {
    return;
  }

  if (!window.up || typeof up.link !== 'object') {
    console.warn('[spa-unpoly-nav] Unpoly is not loaded - SPA navigation is disabled.');
    return;
  }
  window.__friendica_unpoly_nav_initialized = true;

  configureUnpoly();
  bindResponseHooks();
  bindNavigationCompleted();
  bindLoadingIndicatorHooks();
  bindInitialLifecycleEvents();
}

initSPANavigation();
