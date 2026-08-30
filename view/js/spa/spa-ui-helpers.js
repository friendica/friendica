// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// @type {module}

/**
 * Show timeout modal overlay.
 * Displays a modal dialog that can be clicked away.
 */
function showTimeoutModal() {
  hideLoading();

  // Check if modal already exists
  if (document.getElementById('spa-timeout-modal')) {
    return;
  }

  // Get translated texts from PHP
  const title = window.spaErrorTexts?.timeout || 'Timeout';
  const message = window.spaErrorTexts?.timeout_message || 'Request timed out';
  const closeText = window.spaErrorTexts?.close || 'Close';

  // Create modal overlay
  const modal = document.createElement('div');
  modal.id = 'spa-timeout-modal';
  modal.className = 'spa-modal-overlay';

  // Create modal content
  const content = document.createElement('div');
  content.className = 'spa-modal-content';

  // Create heading
  const heading = document.createElement('h2');
  heading.textContent = title;

  // Create message paragraph
  const messageElement = document.createElement('p');
  messageElement.textContent = message;

  // Create close button
  const closeButton = document.createElement('button');
  closeButton.textContent = closeText;
  closeButton.className = 'btn btn-primary spa-modal-close-btn';
  closeButton.onclick = dismissTimeoutModal;

  content.append(heading, messageElement, closeButton);
  modal.appendChild(content);

  // Close modal when clicking on overlay (outside content)
  modal.addEventListener('click', (e) => { if (e.target === modal) dismissTimeoutModal(); });

  // Add modal to body
  document.body.appendChild(modal);

  // Also listen for Escape key
  const escapeHandler = (e) => { if (e.key === 'Escape' || e.keyCode === 27) dismissTimeoutModal(); };
  document.addEventListener('keydown', escapeHandler);

  // Store reference to clean up
  modal._escapeHandler = escapeHandler;
}

/**
 * Dismiss the timeout modal.
 */
function dismissTimeoutModal() {
  const modal = document.getElementById('spa-timeout-modal');
  if (modal) {
    if (modal._escapeHandler) {
      document.removeEventListener('keydown', modal._escapeHandler);
    }
    modal.remove();
  }
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
  dismissTimeoutModal,
  cleanupTooltips
};