// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// @type {module}

function createContentLoader(options) {
  const {
    showTimeoutModal,
    replaceContainerContent
  } = options;

  /**
   * Handle HTTP error responses.
   * @param {number} status - HTTP status code
   * @param {string} url - The original request URL
   */
  function handleHttpError(status, url) {
    if (status === 401) {
      window.location.href = '/login?return_path=' + encodeURIComponent(url);
    }
  }

  /**
   * Load content via AJAX.
   * @param {string} url - The URL to load
   */
  // Module-level variable to track the current request
  let currentAbortController = null;

  function loadContent(url) {
    const fetchUrl = new URL(url, window.location.href);
    let finalUrl = fetchUrl.toString();

    // Create new AbortController for this request
    const controller = new AbortController();

    // Abort any in-flight request
    if (currentAbortController) {
      currentAbortController.abort();
    }
    currentAbortController = controller;

    showFetching();
    fetch(fetchUrl, { headers: { 'Accept': 'text/html' }, credentials: 'include', signal: currentAbortController.signal })
      .then(async (response) => {
        if (!(response.headers.get('Content-Type') || response.headers.get('content-type') || '').includes('text/html')) {
          window.location.href = url;
          return null;
        }

        if (response.url && response.url !== fetchUrl.toString()) {
          finalUrl = response.url;
        }

        if (response.status === 401) {
          handleHttpError(401, url);
          return null;
        }

        if (response.status === 504) {
          showTimeoutModal();
          return null;
        }

        showReceiving();
        const html = await response.text();

        showProcessing();

        if (typeof html !== 'string') {
          throw new Error('Response body is not a string');
        }

        if (finalUrl !== fetchUrl.toString()) {
          history.replaceState({ path: new URL(finalUrl).pathname, spa: true, __friendicaSPA: true }, '', finalUrl);
        }

        replaceContainerContent(html, finalUrl);
        hideLoading();
        
        // Reset abort controller after successful completion
        if (currentAbortController === controller) {
          currentAbortController = null;
        }

        return html;
      })
      .catch(error => {
        // Reset abort controller
        if (currentAbortController === controller) {
          currentAbortController = null;
        }
        
        hideLoading();

        if (error.name === 'AbortError') {
          // Request was aborted by a newer navigation - ignore silently
          return;
        } else if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
          window.location.href = url;
        } else {
          window.location.href = url;
        }
      });
  }

  return {
    handleHttpError,
    loadContent
  };
}

export { createContentLoader };