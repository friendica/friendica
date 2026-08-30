// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
/**
 * JavaScript for the admin module
 */
$(function () {
	let $body = $("body");
	$body.off("click.mod-admin", ".selectall").on("click.mod-admin", ".selectall", function () {
		selectall($(this).data("selectAll"));
	});
	$body.off("click.mod-admin", ".selectnone").on("click.mod-admin", ".selectnone", function () {
		selectnone($(this).data("selectNone"));
	});

	// Toggle checkbox status to all or none for all checkboxes of a specific
	// css class.
	$body.off("change.mod-admin", "input[type=checkbox].selecttoggle").on("change.mod-admin", "input[type=checkbox].selecttoggle", function () {
		$this = $(this);
		if ($this.prop("checked")) {
			selectall($this.data("selectClass"));
			$this.attr("title", $this.data("selectNone"));
		} else {
			selectnone($this.data("selectClass"));
			$this.attr("title", $this.data("selectAll"));
		}
	});

	function selectall(cls) {
		$("." + cls).prop("checked", true);
		return false;
	}
	function selectnone(cls) {
		$("." + cls).prop("checked", false);
		return false;
	}
});

// Users
function confirm_delete(msg, uname) {
	return confirm(msg.format(uname));
}

function details(uid) {
	$("#user-" + uid + "-detail").toggleClass("hidden");
	$("#user-" + uid).toggleClass("opened");
	return false;
}
// @license-end
