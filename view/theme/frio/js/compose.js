// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

/**
 * @file view/theme/frio/js/compose.js
 * JavaScript for the compose page (/compose).
 *
 * Handles:
 * - Link preview attachment
 * - Character counter
 * - ACL autocomplete (@-mentions)
 * - BBCode autocomplete
 * - Location button with geolocation support
 *
 * @requires jQuery
 * @requires linkPreview.js
 * @requires autocomplete.js
 */

(function ($, window, document) {
	"use strict";

	// DOM element references (cached)
	let $textarea = null;
	let locationButton = null;
	let locationInput = null;

	/**
	 * Initialize the compose page functionality.
	 */
	function init() {
		initTextarea();
		initLocation();
		initFormReset();
	}

	/**
	 * Handle form reset after successful submission and draft saving.
	 * Clears the form when a post was successfully submitted to prevent
	 * showing old content on the next visit to /compose.
	 * Also saves/restores scheduling settings as draft.
	 */
	function initFormReset() {
		const $form = $("form.comment-edit-form");

		// Check if sessionStorage is available and if a post was submitted
		// This flag is set in the submit handler below
		if (isStorageAvailable() && sessionStorage.getItem("compose_post_submitted") === "true") {
			// Clear the flag
			sessionStorage.removeItem("compose_post_submitted");

			// Clear saved draft data
			sessionStorage.removeItem("compose_draft");

			// Clear the form after a short delay to let browser autofill complete
			setTimeout(function () {
				if ($textarea && $textarea.length) {
					$textarea.val("");
					updateCharacterCounter(0);
				}

				// Also clear location if set
				if (locationInput) {
					locationInput.value = "";
					updateLocationButtonDisplay(locationButton, locationInput);
				}

				// Clear any link preview
				if (typeof window.linkPreview === "object" && window.linkPreview !== null) {
					window.linkPreview.destroy();
					window.linkPreview = null;
				}

					// Reset scheduled time
					$('[name="scheduled_at"]').val("");
				}, 50);
		} else {
			// No submission - try to restore draft if available
			restoreDraft();
		}

		// Save draft when form values change (but not on submit)
		$form.on("change.compose-draft input.compose-draft", function (e) {
			// Don't save if this is triggered by programmatic changes
			if (e.isTrigger) {
				return;
			}
			saveDraft();
		});

		// Listen for form submission
		$form.on("submit.compose", function () {
			// Mark that the form was submitted
			// This flag will be checked when the page loads next time
			if (isStorageAvailable()) {
				sessionStorage.setItem("compose_post_submitted", "true");
			}
		});
	}

	/**
	 * Check if sessionStorage is available.
	 * @returns {boolean}
	 */
	function isStorageAvailable() {
		try {
			const test = "__test__";
			sessionStorage.setItem(test, test);
			sessionStorage.removeItem(test);
			return true;
		} catch (e) {
			return false;
		}
	}

	/**
	 * Save current form state as draft to sessionStorage.
	 */
	function saveDraft() {
		if (!$textarea || !$textarea.length || !isStorageAvailable()) {
			return;
		}

		const draft = {
			body: $textarea.val(),
			location: locationInput ? locationInput.value : "",
			scheduled_at: $('[name="scheduled_at"]').val(),
		};

		try {
			sessionStorage.setItem("compose_draft", JSON.stringify(draft));
		} catch (e) {
			// Ignore storage errors (e.g., quota exceeded, private mode)
		}
	}

	/**
	 * Restore form state from sessionStorage draft.
	 */
	function restoreDraft() {
		if (!isStorageAvailable()) {
			return;
		}

		try {
			const draftJson = sessionStorage.getItem("compose_draft");
			if (!draftJson) {
				return;
			}

			const draft = JSON.parse(draftJson);

			// Restore values after a short delay to override browser autofill
			setTimeout(function () {
				if (typeof draft.body !== "undefined" && $textarea && $textarea.length) {
					$textarea.val(draft.body);
					updateCharacterCounter(draft.body.length);
				}

				// Restore location (check for undefined, not falsy, so empty string works)
				if (typeof draft.location !== "undefined" && locationInput) {
					locationInput.value = draft.location;
					updateLocationButtonDisplay(locationButton, locationInput);
					// Trigger change event so other scripts can react
					$(locationInput).trigger("change");
				}

				// Restore scheduled time (check for undefined, not falsy)
				const $scheduledAt = $('[name="scheduled_at"]');
				if (typeof draft.scheduled_at !== "undefined" && $scheduledAt.length) {
					$scheduledAt.val(draft.scheduled_at);
					// Trigger change event for datepicker plugins
					$scheduledAt.trigger("change");
				}
			}, 100);
		} catch (e) {
			// Ignore parse errors
			sessionStorage.removeItem("compose_draft");
		}
	}

	/**
	 * Initialize textarea features: link preview, autocomplete, character counter.
	 */
	function initTextarea() {
		$textarea = $("textarea[name=body]");

		if (!$textarea.length) {
			return;
		}

		// Initialize link preview plugin
		if (typeof $.fn.linkPreview === "function") {
			$textarea.linkPreview();
		}

		// Initialize ACL autocomplete (@-mentions)
		if (typeof $.fn.editor_autocomplete === "function" && typeof baseurl !== "undefined") {
			$textarea.editor_autocomplete(baseurl + "/search/acl");
		}

		// Initialize BBCode autocomplete
		if (typeof $.fn.bbco_autocomplete === "function") {
			$textarea.bbco_autocomplete("bbcode");
		}

		// Character counter - use input event to catch all changes (paste, cut, etc.)
		$textarea.on("input.compose", function () {
			updateCharacterCounter($(this).val().length);
		});

		// Initial count
		updateCharacterCounter($textarea.val().length);
	}

	/**
	 * Update the character counter display.
	 *
	 * @param {number} count - The character count
	 */
	function updateCharacterCounter(count) {
		$("#character-counter").text(count);
	}

	/**
	 * Initialize location button functionality.
	 */
	function initLocation() {
		locationButton = document.getElementById("profile-location");
		locationInput = document.getElementById("jot-location");

		if (!locationButton || !locationInput) {
			return;
		}

		// Set initial button state
		updateLocationButtonDisplay(locationButton, locationInput);

		// Bind to both input and change events for robustness
		$(locationInput)
			.on("input.compose change.compose", function () {
				updateLocationButtonDisplay(locationButton, locationInput);
			});

		// Location button click handler
		$(locationButton).on("click.compose", function () {
			handleLocationButtonClick();
		});
	}

	/**
	 * Handle the location button click.
	 */
	function handleLocationButtonClick() {
		if (!locationButton || !locationInput) {
			return;
		}

		// If location is already set, clear it
		if (locationInput.value) {
			locationInput.value = "";
			updateLocationButtonDisplay(locationButton, locationInput);
			// Trigger change event to save draft
			$(locationInput).trigger("change");
			return;
		}

		// Otherwise, try to get geolocation
		if (!("geolocation" in navigator)) {
			// Geolocation not supported - button should already be disabled
			return;
		}

		// Temporarily disable button while getting position
		locationButton.disabled = true;

		navigator.geolocation.getCurrentPosition(
			handleGeolocationSuccess,
			handleGeolocationError,
			{
				enableHighAccuracy: false,
				timeout: 10000,
				maximumAge: 600000, // 10 minutes
			}
		);
	}

	/**
	 * Handle successful geolocation retrieval.
	 *
	 * @param {GeolocationPosition} position
	 */
	function handleGeolocationSuccess(position) {
		if (!locationInput || !locationButton) {
			return;
		}

		const coords = position.coords;
		locationInput.value = coords.latitude + ", " + coords.longitude;
		locationButton.disabled = false;
		updateLocationButtonDisplay(locationButton, locationInput);
		// Trigger change event to save draft
		$(locationInput).trigger("change");
	}

	/**
	 * Handle geolocation error.
	 *
	 * @param {GeolocationPositionError} error
	 */
	function handleGeolocationError(error) {
		if (!locationButton) {
			return;
		}

		// Re-enable button so user can try again (unless geolocation is unsupported)
		if ("geolocation" in navigator) {
			locationButton.disabled = false;
		}

		updateLocationButtonDisplay(locationButton, locationInput);

		// Log error for debugging (non-critical)
		if (typeof console !== "undefined" && console.warn) {
			console.warn("Geolocation error:", error.message);
		}
	}

	/**
	 * Update the location button display based on current state.
	 *
	 * @param {HTMLButtonElement} button - The location button element
	 * @param {HTMLInputElement} input - The location input element
	 */
	function updateLocationButtonDisplay(button, input) {
		if (!button || !input) {
			return;
		}

		const hasValue = !!input.value;
		const hasGeolocation = "geolocation" in navigator;

		// Remove primary class first (will be re-added if needed)
		button.classList.remove("btn-primary");

		if (hasValue) {
			// Location is set - button clears it
			button.disabled = false;
			button.classList.add("btn-primary");
			button.title = button.dataset.titleClear || "Clear location";
		} else if (!hasGeolocation) {
			// Geolocation not supported
			button.disabled = true;
			button.title = button.dataset.titleUnavailable || "Geolocation not available";
		} else if (button.disabled) {
			// Geolocation supported but button disabled (error state)
			button.title = button.dataset.titleDisabled || "Location unavailable";
		} else {
			// Ready to get location
			button.title = button.dataset.titleSet || "Set location";
		}
	}

	// Initialize on DOM ready
	$(function () {
		init();
	});

	// Expose public API
	window.updateLocationButtonDisplay = updateLocationButtonDisplay;

})(jQuery, window, document);
// @license-end
