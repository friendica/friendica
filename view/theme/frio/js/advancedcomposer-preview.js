// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
(function (window) {
	'use strict';

	const api = window.AdvancedComposerLibrary;
	if (!api) {
		return;
	}

	const l10n = window.AdvancedComposerL10n || {};
	const state = api.state || (api.state = {});
	state.previewToken = state.previewToken || 0;

	function fillCardHeader(overlay, previewContainer) {
		const avatarPlaceholder = overlay.querySelector('.ec-preview-avatar-placeholder');
		const authorNameEl = overlay.querySelector('.ec-preview-author-name');
		const timestampEl = overlay.querySelector('.ec-preview-timestamp');

		if (avatarPlaceholder) {
			avatarPlaceholder.textContent = '';
			const realAvatar = document.querySelector('#main-menu img#avatar')
				|| document.querySelector('img#avatar')
				|| document.querySelector('#main-menu img')
				|| (previewContainer && previewContainer.querySelector('.author-avatar img'))
				|| (previewContainer && previewContainer.querySelector('.avatar img'))
				|| document.querySelector('.widget.profile-sidebar img')
				|| document.querySelector('.navbar-right img')
				|| document.querySelector('#profile-photo');

			if (realAvatar && realAvatar.getAttribute('src')) {
				const imgEl = document.createElement('img');
				imgEl.src = realAvatar.getAttribute('src');
				imgEl.className = 'ec-preview-avatar';
				imgEl.alt = '';
				avatarPlaceholder.appendChild(imgEl);
			} else {
				const fallbackEl = document.createElement('div');
				fallbackEl.className = 'ec-preview-avatar-fallback';
				fallbackEl.textContent = l10n.avatarFallback;
				avatarPlaceholder.appendChild(fallbackEl);
			}
		}

		if (authorNameEl) {
			const realName = document.querySelector('#main-menu .user-title strong')
				|| document.querySelector('#nav-user-linkmenu strong')
				|| (previewContainer && previewContainer.querySelector('.author-name'))
				|| (previewContainer && previewContainer.querySelector('.author-wrapper a'))
				|| document.querySelector('.profile-sidebar .name')
				|| document.querySelector('.navbar-right .username');
			authorNameEl.textContent = realName ? realName.textContent.trim() : l10n.lblYou;
		}

		if (timestampEl) {
			timestampEl.textContent = l10n.previewTimestamp;
		}
	}

	function closeFocusPreview() {
		state.previewToken++;
		document.body.classList.remove('ec-preview-overlay-active');
		const overlay = document.getElementById('easy-compose-preview-overlay');
		if (overlay && overlay.parentNode) {
			if (overlay._ecPreviewInputHandler && api.getContext().textarea) {
				api.getContext().textarea.removeEventListener('input', overlay._ecPreviewInputHandler);
				overlay._ecPreviewInputHandler = null;
			}
			overlay.parentNode.removeChild(overlay);
		}

		clearTimeout(state.previewDebounceTimer);
		state.previewDebounceTimer = null;

		const context = api.getContext();
		if (context.parentForm && state.wasNativePreviewVisible === false) {
			const previewContainer = context.parentForm.querySelector('.comment-edit-preview')
				|| context.parentForm.querySelector('[id^="comment-edit-preview-"]')
				|| document.getElementById('jot-preview-content');
			if (previewContainer) {
				previewContainer.style.display = 'none';
			}
		}
		if (typeof context.syncPreviewLayout === 'function') {
			context.syncPreviewLayout();
		}
	}

	function startPollingPreview(myTokenId, context, cardBody, previewBtn, previewContainer, refreshBtn, parentForm, overlay) {
		const liveUpdateHandler = function () {
			const container = getContainer();
			if (container) {
				container._ecStale = false;
			}
			onContentReady();
		};
		document.addEventListener('postprocess_liveupdate', liveUpdateHandler);

		function tryTransferContent(container) {
			if (state.previewToken !== myTokenId) return false;
			if (!document.body.classList.contains('ec-preview-overlay-active')) return false;
			if (container && container._ecStale === true) {
				if (container._ecOldHtml !== undefined && container.innerHTML !== container._ecOldHtml) {
					container._ecStale = false;
				} else {
					return false;
				}
			}
			if (!container || !container.innerHTML.trim()) return false;
			if (container.querySelector('.ec-preview-too-long-warning')) return false;

			const titleEl = container.querySelector('.wall-item-title') || container.querySelector('.comment-edit-preview-title');
			const bodyEl = container.querySelector('.wall-item-body') || container.querySelector('.comment-edit-preview') || container;

			cardBody.innerHTML = '';
			if (titleEl && bodyEl !== container) {
				cardBody.appendChild(titleEl.cloneNode(true));
			}
			cardBody.appendChild(bodyEl.cloneNode(true));
			container._ecStale = false;
			fillCardHeader(overlay, container);
			setTimeout(function () {
				cardBody.scrollTop = cardBody.scrollHeight;
			}, 0);
			return true;
		}

		function getContainer() {
			return parentForm.querySelector('.comment-edit-preview')
				|| parentForm.querySelector('[id^="comment-edit-preview-"]')
				|| document.getElementById('jot-preview-content');
		}

		let transferred = false;
		let observer = null;
		let fallbackHandle = null;

		function onContentReady() {
			if (transferred) return;
			const container = getContainer();
			if (tryTransferContent(container)) {
				transferred = true;
				if (observer) { observer.disconnect(); observer = null; }
				if (fallbackHandle !== null) { clearTimeout(fallbackHandle); fallbackHandle = null; }
				document.removeEventListener('postprocess_liveupdate', liveUpdateHandler);
			}
		}

		const observeTarget = getContainer() || parentForm;
		try {
			observer = new MutationObserver(function () {
				const container = getContainer();
				if (container) {
					container._ecStale = false;
				}
				onContentReady();
			});
			observer.observe(observeTarget, { childList: true, subtree: true });
		} catch (e) {
			observer = null;
		}

		let attempts = 0;
		(function pollFallback() {
			if (transferred) return;
			if (state.previewToken !== myTokenId) return;
			if (!document.body.classList.contains('ec-preview-overlay-active')) return;
			onContentReady();
			if (!transferred) {
				if (attempts < 15) {
					attempts++;
					fallbackHandle = setTimeout(pollFallback, 300);
				} else {
					if (observer) { observer.disconnect(); observer = null; }
					document.removeEventListener('postprocess_liveupdate', liveUpdateHandler);
				}
			}
		}());
	}

	api.registerHandler('openFocusPreview', function () {
		const context = api.getContext();
		const textarea = context.textarea;
		const parentForm = context.parentForm;
		if (!textarea || !parentForm) {
			return;
		}

		state.previewToken++;
		const myToken = state.previewToken;

		let previewContainer = parentForm.querySelector('.comment-edit-preview')
			|| parentForm.querySelector('[id^="comment-edit-preview-"]')
			|| document.getElementById('jot-preview-content');

		const idMatch = textarea.id.match(/comment-edit-text-([a-zA-Z0-9_-]+)/);
		const formId = idMatch ? idMatch[1] : null;

		const previewBtn = parentForm.querySelector('[id^="comment-edit-preview-link-"]')
			|| document.getElementById('jot-preview-link')
			|| document.getElementById(`comment-edit-preview-link-${formId}`);

		if (previewBtn) {
			const isVisible = previewContainer && window.getComputedStyle(previewContainer).display !== 'none';
			state.wasNativePreviewVisible = isVisible;
			if (previewContainer) {
				previewContainer._ecStale = true;
				previewContainer._ecOldHtml = previewContainer.innerHTML;
			}
			if (typeof preview_comment === 'function' && formId) {
				preview_comment(formId);
			} else if (!isVisible) {
				previewBtn.click();
			} else {
				previewBtn.click();
				setTimeout(function () { previewBtn.click(); }, 80);
			}
		}

		let overlay = document.getElementById('easy-compose-preview-overlay');
		if (!overlay) {
			overlay = document.createElement('div');
			overlay.id = 'easy-compose-preview-overlay';
			overlay.className = 'ec-preview-overlay';
			if (document.body.classList.contains('ec-dark-theme')) {
				overlay.classList.add('dark-theme');
			}
			document.body.appendChild(overlay);
		}

		overlay.innerHTML = '';

		const topbar = document.createElement('div');
		topbar.className = 'ec-preview-topbar';
		const deviceSelector = document.createElement('div');
		deviceSelector.className = 'ec-preview-device-selector';
		const desktopBtn = document.createElement('button');
		desktopBtn.type = 'button';
		desktopBtn.className = 'ec-device-btn active';
		desktopBtn.setAttribute('data-device', 'desktop');
		desktopBtn.textContent = l10n.btnDesktop;
		const mobileBtn = document.createElement('button');
		mobileBtn.type = 'button';
		mobileBtn.className = 'ec-device-btn';
		mobileBtn.setAttribute('data-device', 'mobile');
		mobileBtn.textContent = l10n.btnMobile;
		deviceSelector.appendChild(desktopBtn);
		deviceSelector.appendChild(mobileBtn);

		const refreshBtn = document.createElement('button');
		refreshBtn.type = 'button';
		refreshBtn.className = 'ec-preview-refresh-btn';
		refreshBtn.title = l10n.btnRefresh;
		api.buildButtonContent(refreshBtn, 'ri ri-refresh-line', l10n.btnRefresh);

		const topbarLeft = document.createElement('div');
		topbarLeft.className = 'ec-preview-topbar-left';
		topbarLeft.appendChild(deviceSelector);
		topbarLeft.appendChild(refreshBtn);

		const closeBtn = document.createElement('button');
		closeBtn.type = 'button';
		closeBtn.className = 'ec-preview-close-btn';
		closeBtn.textContent = l10n.btnBackToEditor;
		topbar.appendChild(topbarLeft);
		topbar.appendChild(closeBtn);

		const contentArea = document.createElement('div');
		contentArea.className = 'ec-preview-content-area';
		const cardWrapper = document.createElement('div');
		cardWrapper.className = 'ec-preview-card-wrapper desktop';
		const cardHeader = document.createElement('div');
		cardHeader.className = 'ec-preview-card-header';
		const avatarPlaceholder = document.createElement('div');
		avatarPlaceholder.className = 'ec-preview-avatar-placeholder';
		const authorInfo = document.createElement('div');
		authorInfo.className = 'ec-preview-author-info';
		const authorName = document.createElement('span');
		authorName.className = 'ec-preview-author-name';
		const timestamp = document.createElement('span');
		timestamp.className = 'ec-preview-timestamp';
		authorInfo.appendChild(authorName);
		authorInfo.appendChild(timestamp);
		cardHeader.appendChild(avatarPlaceholder);
		cardHeader.appendChild(authorInfo);
		const cardBody = document.createElement('div');
		cardBody.className = 'ec-preview-card-body';
		const loadingDiv = document.createElement('div');
		loadingDiv.className = 'ec-preview-loading';
		loadingDiv.textContent = l10n.btnLoadingPreview;
		cardBody.appendChild(loadingDiv);
		cardWrapper.appendChild(cardHeader);
		cardWrapper.appendChild(cardBody);
		contentArea.appendChild(cardWrapper);
		overlay.appendChild(topbar);
		overlay.appendChild(contentArea);

		document.body.classList.add('ec-preview-overlay-active');

		closeBtn.onclick = closeFocusPreview;
		refreshBtn.onclick = function () {
			cardBody.innerHTML = '';
			const loadDiv = document.createElement('div');
			loadDiv.className = 'ec-preview-loading';
			loadDiv.textContent = l10n.btnLoadingPreview;
			cardBody.appendChild(loadDiv);
			refreshBtn.classList.add('ec-refreshing');
			setTimeout(function () { refreshBtn.classList.remove('ec-refreshing'); }, 600);

			if (previewBtn) {
				if (previewContainer) {
					previewContainer._ecStale = true;
					previewContainer._ecOldHtml = previewContainer.innerHTML;
				}
				const isVisible = previewContainer && window.getComputedStyle(previewContainer).display !== 'none';
				if (typeof preview_comment === 'function' && formId) {
					preview_comment(formId);
				} else if (!isVisible) {
					previewBtn.click();
				} else {
					previewBtn.click();
					setTimeout(function () { previewBtn.click(); }, 80);
				}
			}

			state.previewToken++;
			startPollingPreview(state.previewToken, context, cardBody, previewBtn, previewContainer, refreshBtn, parentForm, overlay);
		};

		const deviceBtns = overlay.querySelectorAll('.ec-device-btn');
		deviceBtns.forEach(function (btn) {
			btn.onclick = function () {
				deviceBtns.forEach(function (other) { other.classList.remove('active'); });
				btn.classList.add('active');
				cardWrapper.className = 'ec-preview-card-wrapper ' + (btn.getAttribute('data-device') === 'mobile' ? 'mobile' : 'desktop');
			};
		});

		const escapeHandler = function (e) {
			if (e.key === 'Escape') {
				closeFocusPreview();
				document.removeEventListener('keydown', escapeHandler);
			}
		};
		document.addEventListener('keydown', escapeHandler);

		const previewInputHandler = function () {
			clearTimeout(state.previewDebounceTimer);
			state.previewDebounceTimer = setTimeout(function () {
				if (document.body.classList.contains('ec-preview-overlay-active')) {
					try {
						refreshBtn.click();
					} catch (e) {}
				}
			}, 2000);
		};
		textarea.addEventListener('input', previewInputHandler);
		overlay._ecPreviewInputHandler = previewInputHandler;

		startPollingPreview(state.previewToken, context, cardBody, previewBtn, previewContainer, refreshBtn, parentForm, overlay);
	});

	api.bindGlobalAction('openFocusPreview', 'AdvancedComposerOpenFocusPreview');
})(window);