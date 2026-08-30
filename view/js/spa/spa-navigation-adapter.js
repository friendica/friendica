// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// @type {module}

/**
 * Build a navigation adapter that handles link interception and history navigation.
 *
 * @param {Object} options
 * @param {(path: string) => boolean} options.isSPARoute
 * @param {(url: string) => void} options.loadContent
 */
function createNavigationAdapter(options) {
  const { isSPARoute, loadContent } = options;

  function dispatchBeforeNavigate(path, url) {
    window.dispatchEvent(new CustomEvent('spa:beforeNavigate', {
      detail: { path: path, url: url }
    }));
  }

  /**
   * Check if a link is an internal link (same origin)
   * @param {HTMLAnchorElement} link - The anchor element
   * @returns {boolean} True if internal link
   */
  function isInternalLink(link) {
    const href = link.getAttribute('href');
    if (!href) return false;
    if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')) return false;
    try {
      return new URL(href, window.location.href).origin === window.location.origin;
    } catch (e) {
      return false;
    }
  }

  /**
   * Navigate to URL using SPA
   * @param {string} url - The URL to navigate to
   */
  function navigateTo(url) {
    const path = new URL(url, window.location.href).pathname;

    if (!isSPARoute(path)) {
      window.location.href = url;
      return;
    }

    dispatchBeforeNavigate(path, url);
    history.pushState({ path: path, spa: true, __friendicaSPA: true }, '', url);
    loadContent(url);
  }

  /**
   * Handle link clicks
   * @param {Event} e - Click event
   */
  function handleLinkClick(e) {
    const link = e.target.closest('a');
    if (!link) return;

    let href = link.getAttribute('href');

    // Ignore anchor-only links (e.g., href="#" or href="#back-to-top")
    if (href && href.startsWith('#')) {
      return;
    }

    // Ignore links that are meant to open modals (e.g., modal-open class)
    if (link.classList.contains('modal-open')) {
      return;
    }

    // Ignore links with data-fancybox attribute (for Fancybox lightbox)
    if (link.hasAttribute('data-fancybox')) {
      history.pushState({ __fancyboxMarker: true }, '', window.location.href);
      return;
    }

    // Ignore links with inline event handlers (onclick, etc.)
    if (link.hasAttribute('onclick') || link.onclick || link.hasAttribute('data-spa-ignore')) {
      return;
    }

    if (!isInternalLink(link)) {
      return;
    }

    if (!href.startsWith('http://') && !href.startsWith('https://') && !href.startsWith('/')) {
      href = '/' + href;
    }

    // Allow middle-click, ctrl-click, cmd-click to open in new tab
    if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey) {
      return;
    }

    e.preventDefault();
    navigateTo(href);
  }

  /**
   * Handle browser back/forward navigation
   * @param {Event} e - Popstate event
   */
  function handlePopState(e) {
    if (e.state?.spa && e.state.__friendicaSPA) {
      dispatchBeforeNavigate(window.location.pathname, window.location.href);
      loadContent(window.location.href);
    }
  }

  function bindNavigationEvents() {
    document.addEventListener('click', handleLinkClick);
    window.addEventListener('popstate', handlePopState);
  }

  return {
    isInternalLink,
    navigateTo,
    handleLinkClick,
    handlePopState,
    bindNavigationEvents
  };
}

export { createNavigationAdapter };