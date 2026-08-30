// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// @type {module}

/**
 * SPA Router for Friendica
 * ES Module - load with <script type="module">
 * Composition root for SPA navigation, content loading and lifecycle modules.
 */

import {
  checkForDOMReadyPatterns,
  classifyScriptContent,
  promoteToGlobal,
  classifyScript,
  executeScripts
} from '/view/js/spa/spa-script-runtime.js';
import {
  showTimeoutModal,
  dismissTimeoutModal,
  cleanupTooltips
} from '/view/js/spa/spa-ui-helpers.js';
import { createNavigationAdapter } from '/view/js/spa/spa-navigation-adapter.js';
import {
  triggerSPADocumentReady,
  triggerSPAWindowLoad,
  reinitializeDynamicContent
} from '/view/js/spa/spa-lifecycle.js';
import { createDomSwapPipeline } from '/view/js/spa/spa-dom-swap.js';
import { createContentLoader } from '/view/js/spa/spa-content-loader.js';
import { initUnpolyEnhancements, bindUnpolyBridgeEvents } from '/view/js/spa/spa-unpoly-bridge.js';

// ============================================
// FEATURE DETECTION
// ============================================

const supportsSPA = window.history && window.history.pushState && window.fetch;

// ============================================
// GLOBAL STATE
// ============================================

const clientRouterVersion = typeof window.__spa_router_version !== 'undefined' ? window.__spa_router_version : null;

// ============================================
// CONFIGURATION
// ============================================

const SPA_CONFIG = {
  enabled: true,
  excludedRoutes: ['/delegation'],
  scrollToTopOnNavigate: true,
  containerSelectorsCore: [
    'nav#topbar-first',
    'div#topbar-second',
    'main'
  ],
  // Extended container syncing is opt-in to avoid broad, often unnecessary DOM replacements.
  enableExtendedContainerSync: false,
  extendedContainerSelectors: [
    'aside',
    'section'
  ]
};

// ============================================
// ROUTE HANDLING
// ============================================

/**
 * Check if a URL path is an SPA route
 * @param {string} path - The URL pathname
 * @returns {boolean} True if route should use SPA
 */
function isSPARoute(path) {
  return !SPA_CONFIG.excludedRoutes.some(route => path === route || path.startsWith(route + '/'));
}

const navigationAdapter = createNavigationAdapter({
  isSPARoute,
  loadContent
});

const domSwapPipeline = createDomSwapPipeline({
  clientRouterVersion,
  spaConfig: SPA_CONFIG,
  cleanupTooltips,
  classifyScript,
  executeScripts,
  reinitializeDynamicContent,
  scrollToTopInstant
});

const contentLoader = createContentLoader({
  showTimeoutModal,
  replaceContainerContent
});

/**
 * Check if a link is an internal link (same origin)
 * @param {HTMLAnchorElement} link - The anchor element
 * @returns {boolean} True if internal link
 */
function isInternalLink(link) {
  return navigationAdapter.isInternalLink(link);
}

/**
 * Handle link clicks
 * @param {Event} e - Click event
 */
function handleLinkClick(e) {
  navigationAdapter.handleLinkClick(e);
}

/**
 * Set focus to content area for accessibility
 * Scrolls to top and sets focus on the content element
 * This is better for screen readers and keyboard navigation
 */
function scrollToTopInstant() {
  const contentElement = document.getElementById('content');
  
  // Save previous scroll behavior
  const html = document.documentElement;
  const previousBehavior = html.style.scrollBehavior;
  
  // Scroll to top instantly
  html.style.scrollBehavior = 'auto';
  window.scrollTo(0, 0);
  html.style.scrollBehavior = previousBehavior;
  
  // Set focus to content element for accessibility
  if (contentElement) {
    contentElement.setAttribute('tabindex', '-1'); // Make element focusable
    contentElement.focus();
  }
}

/**
 * Navigate to URL using SPA
 * @param {string} url - The URL to navigate to
 */
function navigateTo(url) {
  navigationAdapter.navigateTo(url);
}

// ============================================
// CONTENT LOADING
// ============================================

/**
 * Handle HTTP error responses
 * @param {number} status - HTTP status code
 * @param {string} url - The original request URL
 */
function handleHttpError(status, url) {
  contentLoader.handleHttpError(status, url);
}

/**
 * Load content via AJAX
 * @param {string} url - The URL to load
 */
function loadContent(url) {
  contentLoader.loadContent(url);
}

// ============================================
// CONTAINER CONTENT REPLACEMENT
// ============================================

/**
 * Replace content of the main containers: nav#topbar-first, div#topbar-second, and main
 * This approach preserves the DOM structure while updating the content
 * @param {string} htmlString - New content HTML (may be full document or fragment)
 * @param {string} finalUrl - The final URL after following redirects (optional)
 */
function replaceContainerContent(htmlString, finalUrl = null) {
  domSwapPipeline.replaceContainerContent(htmlString, finalUrl);
}

// ============================================
// EVENT LISTENERS & REINITIALIZATION
// ============================================

/**
 * Handle browser back/forward navigation
 * @param {Event} e - Popstate event
 */
function handlePopState(e) {
  navigationAdapter.handlePopState(e);
}

// ============================================
// MAIN INITIALIZATION
// ============================================

/**
 * Initialize SPA Router
 */
async function initSPARouter() {
  if (!supportsSPA || !SPA_CONFIG.enabled || (typeof spaEnabled !== 'undefined' && !spaEnabled)) {
    return;
  }
  
  navigationAdapter.bindNavigationEvents();
  bindUnpolyBridgeEvents();
  initUnpolyEnhancements();
  
  // Register handlers for jQuery ready and window load events
  $(document).ready(triggerSPADocumentReady);
  $(window).load(triggerSPAWindowLoad);
}

if (supportsSPA) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSPARouter);
  } else {
    initSPARouter();
  }
}

// ============================================
// PUBLIC API
// ============================================

export {
  initSPARouter,
  isSPARoute,
  isInternalLink,
  supportsSPA,
  SPA_CONFIG,
  classifyScriptContent,
  promoteToGlobal,
  classifyScript,
  executeScripts,
  replaceContainerContent,
  loadContent,
  handleHttpError,
  showTimeoutModal,
  dismissTimeoutModal,
  cleanupTooltips,
  scrollToTopInstant,
  navigateTo,
  handleLinkClick,
  handlePopState,
  reinitializeDynamicContent,
  checkForDOMReadyPatterns,
  triggerSPADocumentReady,
  triggerSPAWindowLoad
};
