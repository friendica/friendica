// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
$(function () {
	// Jot attachment live preview.
	let $textarea = $("textarea[name=body]");
	$textarea.linkPreview();
	$textarea.keyup(function () {
		var textlen = $(this).val().length;
		$("#character-counter").text(textlen);
	});
	$textarea.editor_autocomplete(baseurl + "/search/acl");
	$textarea.bbco_autocomplete("bbcode");

	let location_button = document.getElementById("profile-location");
	let location_input = document.getElementById("jot-location");

	if (location_button && location_input) {
		updateLocationButtonDisplay(location_button, location_input);

		location_input.addEventListener("change", function () {
			updateLocationButtonDisplay(location_button, location_input);
		});
		location_input.addEventListener("keyup", function () {
			updateLocationButtonDisplay(location_button, location_input);
		});

		location_button.addEventListener("click", function () {
			if (location_input.value) {
				location_input.value = "";
				updateLocationButtonDisplay(location_button, location_input);
			} else if ("geolocation" in navigator) {
				navigator.geolocation.getCurrentPosition(
					function (position) {
						location_input.value = position.coords.latitude + ", " + position.coords.longitude;
						updateLocationButtonDisplay(location_button, location_input);
					},
					function (error) {
						location_button.disabled = true;
						updateLocationButtonDisplay(location_button, location_input);
					},
				);
			}
		});
	}
});

function updateLocationButtonDisplay(location_button, location_input) {
	location_button.classList.remove("btn-primary");
	if (location_input.value) {
		location_button.disabled = false;
		location_button.classList.add("btn-primary");
		location_button.title = location_button.dataset.titleClear;
	} else if (!"geolocation" in navigator) {
		location_button.disabled = true;
		location_button.title = location_button.dataset.titleUnavailable;
	} else if (location_button.disabled) {
		location_button.title = location_button.dataset.titleDisabled;
	} else {
		location_button.title = location_button.dataset.titleSet;
	}
}
// @license-end
