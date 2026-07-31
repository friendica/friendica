// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// PoC only, opt-in via ?htmx_poc=1. Not wired into any default page load.

document.addEventListener('htmx:beforeRequest', function () {
	if (typeof showFetching === 'function') showFetching();
});

document.addEventListener('htmx:afterRequest', function () {
	if (typeof hideLoading === 'function') hideLoading();
});

document.addEventListener('htmx:afterSettle', function () {
	var content = document.getElementById('content');
	if (content) {
		content.setAttribute('tabindex', '-1');
		content.focus({ preventScroll: true });
	}
	if (typeof NavUpdate === 'function') NavUpdate();
});
