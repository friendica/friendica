// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

/**
 * Loading Indicator - Shared module for main.js and spa-unpoly-nav.js
 * Provides consistent loading indicator across the application
 * All texts must be provided by PHP via window.spaLoadingTexts
 */

// ============================================
// CONFIGURATION
// ============================================

var LOADING_STATES = {
  FETCHING: 'fetching',
  RECEIVING: 'receiving',
  PROCESSING: 'processing',
  POSTING: 'posting',
};

var loadingIndicator = null;
var currentLoadingState = null;
var messageRotationInterval = null;

// Unpoly's up.ProgressBar instance (the thin bar at the top of the viewport)
// and the timer that defers showing the whole indicator, see setLoadingState().
var progressBar = null;
var showTimer = null;
var pendingState = null;

var LOADING_CONFIG = {
  indicatorId: 'spa-loading-indicator',
  barHeight: 3,
  fadeOutDuration: 180,
  messageRotationDelay: 2000, // 2 seconds for message rotation
  showDelay: 400 // mirror Unpoly's up.network.config.badResponseTime: don't
                 // show anything for actions that finish quicker than this
};

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Get computed style value safely
 * @param {HTMLElement} element
 * @param {string} property
 * @returns {string}
 */
function getComputedStyleValue(element, property) {
  if (element && window.getComputedStyle) {
    try {
      return window.getComputedStyle(element)[property];
    } catch (e) {
      console.warn('[Loading] Could not get computed style for', property, e);
    }
  }
  return '';
}

/**
 * Get loading text from PHP translations
 * All texts are provided by PHP via window.spaLoadingTexts
 * @param {string} key
 * @returns {string}
 */
function getLoadingText(key) {
  if (window.spaLoadingTexts && window.spaLoadingTexts[key]) {
    return window.spaLoadingTexts[key];
  }
  console.warn('[Loading] Loading text missing for key:', key);
  return '';
}

/**
 * Get a random message from delay messages array
 * @returns {string}
 */
function getRandomDelayMessage() {
  const messages = window.spaLoadingTexts.delay_messages || [];
  if (messages && messages.length > 0) {
    const randomIndex = Math.floor(Math.random() * messages.length);
    return messages[randomIndex];
  }
  return '';
}

/**
 * Start rotating delay messages in the status text
 */
function startMessageRotation() {
  // Clear any existing rotation
  if (messageRotationInterval) {
    clearInterval(messageRotationInterval);
    messageRotationInterval = null;
  }
  
  // Start new rotation after initial delay
  messageRotationInterval = setTimeout(() => {
    const statusText = loadingIndicator && loadingIndicator.querySelector('.spa-status-text');
    if (statusText) {
      // Set first random message
      statusText.textContent = getRandomDelayMessage();
      
      // Then rotate every 2 seconds
      messageRotationInterval = setInterval(() => {
        statusText.textContent = getRandomDelayMessage();
      }, LOADING_CONFIG.messageRotationDelay);
    }
  }, LOADING_CONFIG.messageRotationDelay);
}

// ============================================
// LOADING INDICATOR CREATION
// ============================================

/**
 * Create loading indicator element
 */
function createLoadingIndicator() {
  if (loadingIndicator) return;

  var indicator = document.createElement('div');
  indicator.id = LOADING_CONFIG.indicatorId;
  indicator.className = 'spa-loading';
  indicator.innerHTML = '<div class="spa-progress"></div><div class="spa-status-text"></div>';

  var sourceElement = document.getElementById('topbar-first');
  if (sourceElement) {
    var bgColor = getComputedStyleValue(sourceElement, 'background-color');
    var iconElement = document.querySelector('nav#topbar-first .icon');
    var textColor = iconElement ? getComputedStyleValue(iconElement, 'color') : getComputedStyleValue(sourceElement, 'color');

    if (bgColor === '' || bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent') {
      var parent = sourceElement.parentElement;
      if (parent) {
        bgColor = getComputedStyleValue(parent, 'background-color');
      }
    }

    if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
      indicator.style.setProperty('--spa-loading-bg', bgColor);
    }
    if (textColor) {
      indicator.style.setProperty('--spa-loading-text', textColor);
    }
  }

  document.body.appendChild(indicator);
  loadingIndicator = indicator;
  document.documentElement.style.setProperty('--spa-loading-height', LOADING_CONFIG.barHeight + 'px');
}

/**
 * Initialize loading indicator
 */
function initLoadingIndicator() {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createLoadingIndicator);
  } else {
    createLoadingIndicator();
  }
}

// ============================================
// STATE MANAGEMENT
// ============================================

/**
 * Start Unpoly's original progress bar (the thin bar at the top of the
 * viewport). No-op when Unpoly is not loaded (non-SPA mode) or when a bar is
 * already running - the status-text strip below is the fallback in that case.
 */
function startProgressBar() {
  if (progressBar) return;
  if (typeof window.up === 'undefined' || typeof up.ProgressBar !== 'function') return;

  try {
    progressBar = new up.ProgressBar();
  } catch (e) {
    console.warn('[Loading] Could not start Unpoly progress bar', e);
    progressBar = null;
  }
}

/**
 * Finish the progress bar: animate it to 100% and let it fade out, same as
 * Unpoly does when a real request completes.
 */
function concludeProgressBar() {
  if (!progressBar) return;

  try {
    progressBar.conclude();
  } catch (e) {
    console.warn('[Loading] Could not conclude Unpoly progress bar', e);
  }
  progressBar = null;
}

/**
 * Apply a loading state to the DOM: swap state classes, set the status text,
 * start message rotation, show the strip and start the progress bar.
 * @param {string} state
 * @param {string} text
 */
function applyLoadingState(state, text) {
  if (!loadingIndicator) createLoadingIndicator();

  Object.values(LOADING_STATES).forEach(function(s) {
    loadingIndicator.classList.remove(s);
  });

  currentLoadingState = state;
  loadingIndicator.classList.add(state);

  var statusText = loadingIndicator.querySelector('.spa-status-text');
  if (statusText) {
    // Set initial state text
    statusText.textContent = text || getLoadingText(state);
    // Start message rotation after initial delay
    startMessageRotation();
  }

  loadingIndicator.classList.add('active');
  startProgressBar();
}

/**
 * Request a loading state. Nothing is shown until LOADING_CONFIG.showDelay has
 * passed, so actions that finish quickly (a fast comment reload, a cached SPA
 * navigation) never flash an indicator. Once visible, further state changes
 * (e.g. fetching -> processing) apply immediately.
 * @param {string} state
 * @param {string} text
 */
function setLoadingState(state, text) {
  pendingState = { state: state, text: text };

  if (loadingIndicator && loadingIndicator.classList.contains('active')) {
    applyLoadingState(state, text);
    return;
  }

  // A show is already scheduled - keep its original deadline so the total
  // delay stays ~showDelay regardless of how many states are requested.
  if (showTimer) return;

  showTimer = setTimeout(function() {
    showTimer = null;
    if (pendingState) {
      applyLoadingState(pendingState.state, pendingState.text);
    }
  }, LOADING_CONFIG.showDelay);
}

function showFetching() {
  setLoadingState(LOADING_STATES.FETCHING);
}

function showReceiving() {
  setLoadingState(LOADING_STATES.RECEIVING);
}

function showProcessing() {
  setLoadingState(LOADING_STATES.PROCESSING);
}

function showPosting() {
  setLoadingState(LOADING_STATES.POSTING);
}

function hideLoading() {
  // Cancel a still-pending show: the action finished within showDelay, so no
  // indicator was ever shown and nothing needs to be torn down.
  pendingState = null;
  if (showTimer) {
    clearTimeout(showTimer);
    showTimer = null;
  }

  concludeProgressBar();

  if (!loadingIndicator) return;

  // Clear message rotation interval
  if (messageRotationInterval) {
    if (typeof messageRotationInterval === 'number') {
      clearTimeout(messageRotationInterval);
    } else {
      clearInterval(messageRotationInterval);
    }
    messageRotationInterval = null;
  }

  loadingIndicator.classList.remove('active');
  setTimeout(function() {
    Object.values(LOADING_STATES).forEach(function(s) {
      loadingIndicator.classList.remove(s);
    });
    currentLoadingState = null;
  }, LOADING_CONFIG.fadeOutDuration);
}

// ============================================
// MODULE EXPORT
// ============================================

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    LOADING_STATES: LOADING_STATES,
    initLoadingIndicator: initLoadingIndicator,
    createLoadingIndicator: createLoadingIndicator,
    getLoadingText: getLoadingText,
    setLoadingState: setLoadingState,
    showFetching: showFetching,
    showReceiving: showReceiving,
    showProcessing: showProcessing,
    showPosting: showPosting,
    hideLoading: hideLoading
  };
}

if (document.readyState !== 'loading') {
  initLoadingIndicator();
}
