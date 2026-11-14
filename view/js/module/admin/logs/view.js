// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// Runs again after every SPA navigation, since the log table is replaced then
window.onDocumentReady(".log-event", function () {
	function log_show_details(elm) {
		const id = elm.id;
		var hidden = true;
		document
			.querySelectorAll('[data-id="' + id + '"]')
			.forEach(edetails => {
				hidden = edetails.classList.toggle('hidden');
			});
		document
			.querySelectorAll('[aria-expanded="true"]')
			.forEach(eexpanded => {
				eexpanded.setAttribute('aria-expanded', false);
			});
		
		if (!hidden) {
			elm.setAttribute('aria-expanded', true);
		}
	}

	document
		.querySelectorAll('.log-event')
		.forEach(elm => {
			elm.onclick = evt => {
				log_show_details(evt.currentTarget);
			};
			elm.onkeydown = evt => {
				if (evt.keyCode == 13 || evt.keyCode == 32) {
					log_show_details(evt.currentTarget);
				}
			};
		});
});