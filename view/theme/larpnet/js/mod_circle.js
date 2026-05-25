// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

/**
 * @file view/theme/frio/js/mod_circle.js
 * The javascript for the circle module
 */

$(document).ready(function () {
	// Add an event listeners on buttons for switching the contact list view
	$("body").on("click", ".circle-list-switcher", function () {
		switchCircleViewMode(this);
	});
});

/**
 * Change the circle membership of the contacts and fetch the new grup list
 * as html
 *
 * @param {int} gid The circle ID
 * @param {int} cid The contact ID
 * @param {string} sec_token The security token
 *
 * @returns {undefined}
 */
function circleChangeMember(gid, cid, sec_token) {
	$("#contact-entry-wrapper-" + cid).fadeTo("fast", 0.33);
	$(".tooltip").tooltip("hide");
	$("body").css("cursor", "wait");

	$.get("circle/" + gid + "/" + cid + "?t=" + sec_token, function (data) {
		// Insert the new circle member list
		$("#circle-update-wrapper").html(data);

		// Apply the actual circle list view mode to the new
		// circle list html
		var activeMode = $(".circle-list-switcher.active");
		switchCircleViewMode(activeMode[0]);

		$("body").css("cursor", "auto");
	});
}

/**
 * Change the circle list view mode
 *
 * @param {object} elm The button element of the view mode switcher
 * @returns {undefined}
 */
function switchCircleViewMode(elm) {
	// Remove the active class from circle list switcher buttons
	$(".circle-list-switcher").removeClass("active");
	// And add it to the active button element
	$(elm).addClass("active");

	// Add or remove the css classes for the circle list with regard to the active view mode
	if (elm.id === "circle-list-small") {
		$("#contact-circle-list > li").addClass("shortmode col-lg-6 col-md-6 col-sm-6 col-xs-12");
	} else {
		$("#contact-circle-list > li").removeClass("shortmode col-lg-6 col-md-6 col-sm-6 col-xs-12");
	}
}

/**
 * Filter the circle member list for contacts
 *
 * @returns {undefined}
 */
function filterList() {
	const search = document.getElementById("contacts-search").value.toUpperCase();
	const li     = document.querySelectorAll("#contact-circle-list>li");

	for (let i = 0; i < li.length; i++) {
		let foundInDisplayName = li[i].getElementsByClassName("media-heading")[0].firstChild.textContent.toUpperCase().indexOf(search) > -1;
		let foundInAddr        = li[i].getElementsByClassName("contact-entry-url")[0].textContent.toUpperCase().indexOf(search) > -1;

		if (foundInDisplayName || foundInAddr) {
			li[i].style.display = "";
		} else {
			li[i].style.display = "none";
		}
	}
}
// @license-end
