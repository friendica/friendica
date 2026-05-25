// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

var batchConfirmed = false;

$(document).ready(function () {
	// Replace the drop contact link of the photo menu
	// with a confirmation modal.
	$("body").on("click", ".contact-photo-menu a", function (e) {
		var photoMenuLink = $(this).attr("href");
		if (typeof photoMenuLink !== "undefined" && photoMenuLink.indexOf("/drop?confirm=1") !== -1) {
			e.preventDefault();
			addToModal(photoMenuLink);
			return false;
		}
	});
});

/**
 * This function submits the form with the batch action values.
 *
 * @param {string} name The name of the batch action.
 * @param {string} value If it isn't empty the action will be posted.
 *
 * @return {void}
 */
function batch_submit_handler(name, value) {
	if (confirm(value + " ?")) {
		// Set the value of the hidden input element with the name batch_submit.
		document.batch_actions_submit.batch_submit.value = value;
		// Change the name of the input element from batch_submit according to the
		// name which is transmitted to this function.
		document.batch_actions_submit.batch_submit.name = name;
		// Transmit the form.
		document.batch_actions_submit.submit();

		return true;
	} else {
		return false;
	}
}
// @license-end
