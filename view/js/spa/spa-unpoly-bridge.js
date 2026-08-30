// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// @type {module}

/**
 * Optional bridge for Unpoly's compiler-based DOM enhancement.
 *
 * This keeps the existing Friendica SPA routing intact while allowing Unpoly to
 * initialize elements that are inserted into the DOM later. The bridge is a no-op
 * unless the global `up` API is available on the page.
 */
function initUnpolyEnhancements() {
  if (!window.up || typeof window.up.compiler !== 'function') {
    return false;
  }

  if (window.__friendica_unpoly_initialized) {
    return true;
  }

  const triggerInitializer = (element) => {
    if (!element || typeof element.getAttribute !== 'function') {
      return;
    }

    // These data attributes are checked for future compatibility
    // Currently no elements in view/ or src/ use these attributes, but they are
    // reserved for future use when modules need to initialize elements via Unpoly
    const candidateNames = [
      element.getAttribute('data-init')
    ].filter(Boolean);

    for (const name of candidateNames) {
      const callback = window[name];
      if (typeof callback !== 'function') {
        continue;
      }

      try {
        callback(element);
      } catch (error) {
        console.warn('[Unpoly bridge] initializer failed:', name, error);
      }
    }
  };

  // Compilers for data-init attribute (reserved for future use)
  const selectors = [
    '[data-init]'
  ];

  selectors.forEach((selector) => {
    window.up.compiler(selector, function(element) {
      triggerInitializer(element);
    });
  });

  window.__friendica_unpoly_initialized = true;

  return true;
}

function bindUnpolyBridgeEvents() {
  if (!window.addEventListener) {
    return;
  }

  if (window.__friendica_unpoly_bridge_bound) {
    return;
  }

  const refresh = () => {
    if (window.up && typeof window.up.compiler === 'function') {
      initUnpolyEnhancements();
    }
  };

  window.addEventListener('load', refresh);
  window.__friendica_unpoly_bridge_bound = true;
}

export {
  initUnpolyEnhancements,
  bindUnpolyBridgeEvents
};
