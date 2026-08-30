// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// @type {module}

function createDomSwapPipeline(options) {
  const {
    clientRouterVersion,
    spaConfig,
    cleanupTooltips,
    classifyScript,
    executeScripts,
    reinitializeDynamicContent,
    scrollToTopInstant
  } = options;

  function getContainerSelectors() {
    const coreSelectors = Array.isArray(spaConfig.containerSelectorsCore) && spaConfig.containerSelectorsCore.length > 0
      ? spaConfig.containerSelectorsCore
      : ['nav#topbar-first', 'div#topbar-second', 'main'];

    const includeExtended = spaConfig.enableExtendedContainerSync !== false;
    const extendedSelectors = includeExtended && Array.isArray(spaConfig.extendedContainerSelectors)
      ? spaConfig.extendedContainerSelectors
      : [];

    return Array.from(new Set([...coreSelectors, ...extendedSelectors]));
  }

  function normalizeScriptSource(src) {
    if (!src) {
      return null;
    }

    try {
      const baseUrl = document.baseURI || (window.location.origin + '/');
      return new URL(src, baseUrl).toString();
    } catch (e) {
      return src;
    }
  }

  /**
   * Replace content of the main containers.
   * @param {string} htmlString - New content HTML (may be full document or fragment)
   * @param {string} finalUrl - The final URL after following redirects (optional)
   */
  function replaceContainerContent(htmlString, finalUrl = null) {
    if (typeof htmlString !== 'string') {
      return;
    }

    const effectiveFinalUrl = finalUrl || window.location.href;

    const newDoc = new DOMParser().parseFromString(htmlString, 'text/html');

    const newTitle = newDoc.querySelector('title');
    if (newTitle) document.title = newTitle.textContent;

    const newRouterVersion = newDoc.querySelector('script[data-spa-version]');
    if (newRouterVersion) {
      const serverVersion = newRouterVersion.getAttribute('data-spa-version');
      if (clientRouterVersion && serverVersion && serverVersion !== clientRouterVersion) {
        window.location.reload();
        return;
      }
    }

    const globalScripts = [];
    const bodyScripts = [];
    const externalScriptPromises = [];

    const resourceElements = newDoc.querySelectorAll('head > link, head > script, head > style, body script, body link');
    const effectivePath = new URL(effectiveFinalUrl, window.location.href).pathname;

    const containerSelectors = getContainerSelectors();
    let containerReplaced = false;

    containerSelectors.forEach(selector => {
      const newDiv = newDoc.querySelector(selector);
      const oldDiv = document.querySelector(selector);

      if (newDiv && oldDiv) {
        containerReplaced = true;
        for (const script of newDiv.querySelectorAll('script:not([src])')) {
          const type = script.getAttribute('type') || '';
          if (type && 
              type !== 'text/javascript' && 
              type !== 'application/javascript' &&
              type !== 'module' &&
              type !== 'application/ecmascript' &&
              type !== 'text/ecmascript') {
            continue;
          }
          const content = script.textContent.trim();
          if (content) classifyScript(content, globalScripts, bodyScripts, script, 'container');
          script.parentNode.removeChild(script);
        }

        for (const script of newDiv.querySelectorAll('script[src]')) {
          script.parentNode.removeChild(script);
        }

        if (selector === 'main') {
          if (spaConfig.scrollToTopOnNavigate && !effectivePath.includes('/display/')) {
            scrollToTopInstant();
          }
        }

        oldDiv.innerHTML = newDiv.innerHTML;
      }
    });

    // Only cleanup tooltips if we successfully replaced at least one container
    // This prevents blank pages when error pages or redirects don't have the expected containers
    if (containerReplaced) {
      cleanupTooltips();
    }

    const scrollLoaderNew = newDoc.querySelector('#scroll-loader');
    if (scrollLoaderNew) {
      const scrollLoaderOld = document.getElementById('scroll-loader');
      if (scrollLoaderOld) scrollLoaderOld.innerHTML = scrollLoaderNew.innerHTML;
    }

    const newPageScripts = new Set();
    newDoc.querySelectorAll('head > script[src], body script[src]').forEach(el => {
      const src = normalizeScriptSource(el.getAttribute('src'));
      if (src) {
        newPageScripts.add(src);
      }
    });

    document.querySelectorAll('script[src]').forEach(script => {
      if (newPageScripts.has(normalizeScriptSource(script.getAttribute('src') || script.src)) || script.hasAttribute('data-spa-persistent')) {
        return;
      }

      script.parentNode.removeChild(script);
    });

    for (const el of resourceElements) {
      const tag = el.tagName.toLowerCase();

      if (tag === 'link' || tag === 'style') {
        const href = el.getAttribute('href');
        // For style elements without href, use a different approach
        if (tag === 'style' && !href) {
          // Use text content as part of selector to avoid matching existing styles
          const contentHash = el.textContent.trim().substring(0, 32);
          const selector = 'style[data-spa-style-hash="' + contentHash + '"]';
          if (!document.head.querySelector(selector)) {
            const clone = el.cloneNode(true);
            clone.setAttribute('data-spa-style-hash', contentHash);
            document.head.appendChild(clone);
          }
        } else {
          const selector = tag + (href ? `[href="${CSS.escape ? CSS.escape(href) : href}"]` : '');
          if (!document.head.querySelector(selector)) {
            document.head.appendChild(el.cloneNode(true));
          }
        }
      } else if (tag === 'script') {
        const src = el.getAttribute('src');
        if (src) {
          if (!document.querySelector(`script[src="${src}"]`) && !document.querySelector(`script[src="${normalizeScriptSource(src)}"]`)) {
            const clone = document.createElement('script');

            Array.from(el.attributes).forEach(attr => {
              clone.setAttribute(attr.name, attr.value);
            });

            clone.async = false;

            externalScriptPromises.push(new Promise(resolve => {
              clone.onload = () => {
                resolve();
              };
              clone.onerror = () => {
                resolve();
              };
            }));
            document.head.appendChild(clone);
          }
        } else {
          if (el.parentNode && el.parentNode.tagName.toLowerCase() === 'head') {
            classifyScript(el.textContent.trim(), globalScripts, bodyScripts, el, 'head');
          }
        }
      }
    }

    window.__spa_executing_page_scripts = true;
    window.__spa_bodyScripts = bodyScripts;

    executeScripts(globalScripts, 'global-head');

    if (typeof initInfiniteScroll === 'function') {
      initInfiniteScroll();
    }

    const runFinalAppInit = () => {
      window.__spa_reinit_phase = true;
      reinitializeDynamicContent(executeScripts);
      window.__spa_reinit_phase = false;
      window.__spa_executing_page_scripts = false;
    };
    if (externalScriptPromises.length > 0) {
      Promise.all(externalScriptPromises).then(() => {
        setTimeout(runFinalAppInit, 10);
      });
    } else {
      runFinalAppInit();
    }
  }

  return {
    replaceContainerContent
  };
}

export { createDomSwapPipeline };