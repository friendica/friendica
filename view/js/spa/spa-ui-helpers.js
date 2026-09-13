// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// @type {module}

// Tracks the currently open timeout overlay so a second 504 doesn't stack
// another modal on top of it.
let timeoutLayer = null;

/**
 * Show a timeout modal via Unpoly's overlay system.
 * Used for 504 responses, which come from the reverse proxy rather than
 * Friendica and therefore aren't valid Unpoly fragments to render normally.
 */
function showTimeoutModal() {
  // hideLoading() is a global defined by loading-indicator.js, a classic
  // (non-module) script loaded before this one; guard like spa-unpoly-nav.js
  // does rather than importing a global.
  if (typeof hideLoading === 'function') {
    hideLoading();
  }

  if (timeoutLayer) {
    return;
  }

  // Get translated texts from PHP
  const title = window.spaErrorTexts?.timeout || 'Timeout';
  const message = window.spaErrorTexts?.timeout_message || 'Request timed out';

  const heading = document.createElement('h2');
  heading.textContent = title;

  const messageElement = document.createElement('p');
  messageElement.textContent = message;

  // Modal mode is dismissible by close button, Escape and outside click by
  // default, so no manual event wiring is needed here.
  timeoutLayer = up.layer.open({
    mode: 'modal',
    class: 'spa-timeout-modal',
    content: [heading, messageElement],
    onDismissed: () => { timeoutLayer = null; },
  });
}

/**
 * Remove all tooltip elements to prevent ghost tooltips after SPA navigation.
 */
function cleanupTooltips() {
  const tooltipSelectors = [
    'body > .tooltip',
    'body > [class*="tooltip"]',
    'body > .ui-tooltip',
    'body > .popover',
    'body > [role="tooltip"]',
    'body > .fancybox-wrap',
    'body > .colorbox',
    'body > .jGrowl'
  ];

  tooltipSelectors.forEach(selector => {
    const elements = document.querySelectorAll(selector);
    const elementsArray = Array.prototype.slice.call(elements);
    elementsArray.forEach(el => {
      el.remove();
    });
  });
}

export {
  showTimeoutModal,
  cleanupTooltips
};
