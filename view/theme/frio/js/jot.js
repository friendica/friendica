// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

/**
 * @file view/theme/frio/js/jot.js
 * JavaScript for link attachment in the jot (post composer).
 *
 * Handles inserting links into posts with automatic preview generation.
 *
 * @requires jQuery
 * @requires linkPreview.js
 * @requires autosize
 *
 * Note: The linkPreview variable is intentionally global for cross-module access.
 */

// Global linkPreview instance for cross-module access
// eslint-disable-next-line no-var
var linkPreview;

(function ($, window) {
	"use strict";

	/**
	 * Prompt for a link URL and insert it into the jot with preview.
	 *
	 * @returns {void}
	 */
	window.jotGetLink = function () {
		// Check for required global variables and functions
		if (typeof window.aStr === "undefined" || !window.aStr.linkurl) {
			return;
		}

		const reply = prompt(window.aStr.linkurl);

		if (!reply || !reply.length) {
			return;
		}

		const $textarea = $("#profile-jot-text");

		if (!$textarea.length) {
			return;
		}

		const currentText = $textarea.val();

		// Clear previous attachment preview
		$("#jot-attachment-preview").empty();
		$("#profile-rotator").show();

		// Check if post already has an attachment
		const hasAttachment = currentText.includes("[attachment") &&
		                      currentText.includes("[/attachment]");
		const noAttachment = hasAttachment ? "&noAttachment=1" : "";

		// Use linkPreview library if available
		if (typeof linkPreview === "object" && linkPreview !== null) {
			linkPreview.crawlText(reply + noAttachment);
		} else if (typeof window.bin2hex === "function") {
			// Fallback: directly fetch and insert BBCode
			const hexReply = window.bin2hex(reply);
			$.get("parseurl?binurl=" + hexReply + noAttachment, function (data) {
				if (typeof window.addeditortext === "function") {
					window.addeditortext(data);
				}
				$("#profile-rotator").hide();
			}).fail(function () {
				$("#profile-rotator").hide();
			});
		} else {
			$("#profile-rotator").hide();
		}

		// Update textarea autosize if available
		if (typeof window.autosize === "function") {
			window.autosize.update($textarea);
		}
	};

})(jQuery, window);
// @license-end
