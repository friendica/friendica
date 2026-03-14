// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

/**
 * @file view/theme/frio/js/modal.js
 * Bootstrap modal handling for the Frio theme.
 *
 * This module provides functions for modal operations including:
 * - Generic modal content loading
 * - Jot (post composition) modal handling
 * - File browser integration
 *
 * @requires jQuery
 * @requires Bootstrap Modal
 */

(function ($, window, document) {
	"use strict";

	// Module state
	let isJotResetBound = false;
	let isEditJotClosing = false;

	/**
	 * Initialize modal handlers on document ready.
	 */
	$(function () {
		const $body = $("body");

		// Clear bs modal on close - prevent old content display.
		// Using a single namespaced event handler for all modals.
		$body.on("hidden.bs.modal.frio", ".modal", function () {
			const $modal = $(this);
			$modal.removeData("bs.modal");
			$("#modal-title").empty();
			$("#modal-body").empty();
			// Clean up file browser elements
			$(".fbrowser").remove();
			$(".ajaxbutton-wrapper").remove();
		});

		// Special handling for jot-modal close - restore cached jot content
		$body.on("hidden.bs.modal.frio", "#jot-modal", function () {
			// Restore cached jot at its hidden position ("#jot-content")
			if (window.jotcache && window.jotcache.length) {
				$("#jot-content").append(window.jotcache);
				window.jotcache = "";
			}
			// Destroy the attachment linkPreview for Jot
			if (typeof window.linkPreview === "object" && window.linkPreview !== null) {
				window.linkPreview.destroy();
				window.linkPreview = null;
			}
		});

		// Navbar login
		$body.on("click.frio", "#nav-login", function (e) {
			e.preventDefault();
			if (window.Dialog && typeof window.Dialog.show === "function") {
				window.Dialog.show(this.href, this.dataset.originalTitle || this.title);
			}
		});

		// Jot nav menu tabs
		$body.on("click.frio", "#jot-modal .jot-nav li .jot-nav-lnk", function (e) {
			e.preventDefault();
			toggleJotNav(this);
		});

		// Bookmarklet page needs a jot modal which appears automatically
		if (window.location.pathname.indexOf("/bookmarklet") >= 0 && $("#jot-modal").length) {
			// jotShow is defined in jot-header.tpl
			if (typeof window.jotShow === "function") {
				window.jotShow();
			}
		}

		// Open filebrowser for elements with the class "image-select"
		$body.on("click.frio", ".image-select", function () {
			this.setAttribute("image-input", "select");
			if (window.Dialog && typeof window.Dialog.doImageBrowser === "function") {
				window.Dialog.doImageBrowser("input");
			}
		});

		// Insert filebrowser images into the input field
		$body.on("fbrowser.photo.input.frio", function (e, filename, embedcode, id, img) {
			const $elm = $("[image-input='select']");
			const $input = $elm.closest(".input-group").find("input");
			$elm.removeAttr("image-input");
			$input.val(img);
		});

		// Generic delegated event to open an anchor URL in a modal
		$body.on("click.frio", "a.add-to-modal", function (e) {
			e.preventDefault();
			addToModal(this.href);
		});

		// Bind the edit-jot reset handler exactly once using event delegation
		bindJotResetOnce();
	});

	/**
	 * Bind the jot reset handler exactly once to prevent duplicate event registration.
	 * This fixes the critical bug where calling editpost() multiple times would
	 * register multiple handlers, causing the reset code to run multiple times.
	 */
	function bindJotResetOnce() {
		if (isJotResetBound) {
			return;
		}
		isJotResetBound = true;

		$("body").on("hidden.bs.modal.frio-edit", "#jot-modal.edit-jot", function () {
			// Prevent concurrent execution
			if (isEditJotClosing) {
				return;
			}
			isEditJotClosing = true;

			const $modal = $(this);
			$modal.removeData("bs.modal");
			$(".jot-nav .jot-perms-lnk").parent("li").removeClass("hidden");
			$("#profile-jot-form #jot-title-wrap, #profile-jot-form #jot-category-wrap").show();
			$modal.removeClass("edit-jot");
			$("#jot-modal-content").empty();

			// Reset flag after a short delay to allow Bootstrap to finish its cleanup
			setTimeout(function () {
				isEditJotClosing = false;
			}, 0);
		});
	}

	/**
	 * Overwrite Dialog.show from main js to load the filebrowser into a bs modal.
	 *
	 * @param {string} url - The URL to load
	 * @param {string} [title] - Optional modal title
	 */
	window.Dialog.show = function (url, title) {
		title = title || "";
		const $modal = $("#modal").modal();
		$modal.find("#modal-header h4").html(title);
		$modal.find("#modal-body").load(url, function (responseText, textStatus) {
			if (textStatus === "success" || textStatus === "notmodified") {
				$modal.show();
				if (typeof window.Dialog._load === "function") {
					window.Dialog._load(url);
				}
			}
		});
	};

	/**
	 * Overwrite the function _get_url from main.js.
	 *
	 * @param {string} type - The browser type
	 * @param {string} name - The browser name
	 * @param {string|number} [id] - Optional ID
	 * @returns {string} The constructed URL
	 */
	window.Dialog._get_url = function (type, name, id) {
		let hash = name;
		if (id !== undefined) {
			hash = hash + "-" + id;
		}
		return "media/" + type + "/browser?mode=none&theme=frio#" + hash;
	};

	/**
	 * Load the filebrowser into the jot modal.
	 */
	window.Dialog.showJot = function () {
		const type = "photo";
		const name = "main";
		const url = window.Dialog._get_url(type, name);

		if ($(".modal-body #jot-fbrowser-wrapper .fbrowser").length < 1) {
			$("#jot-fbrowser-wrapper").load(url, function (responseText, textStatus) {
				if (textStatus === "success" || textStatus === "notmodified") {
					if (typeof window.Dialog._load === "function") {
						window.Dialog._load(url);
					}
				}
			});
		}
	};

	/**
	 * Initialize the filebrowser after page load.
	 *
	 * @param {string} url - The URL being loaded
	 */
	window.Dialog._load = function (url) {
		const filebrowser = document.getElementById("filebrowser");
		const match = url.match(/media\/[a-z]+\/.*(#.*)/);

		if (!filebrowser || match === null) {
			return; // not fbrowser
		}

		// Initialize the filebrowser
		if (typeof window.loadScript === "function") {
			window.loadScript("view/js/ajaxupload.js");
			window.loadScript("view/theme/frio/js/module/media/browser.js", function () {
				if (typeof window.Browser !== "undefined" && typeof window.Browser.init === "function") {
					window.Browser.init(filebrowser.dataset.nickname, filebrowser.dataset.type, match[1]);
				}
			});
		}
	};

	/**
	 * Add first element with the class "heading" as modal title.
	 * Note: This should ideally be done in the template.
	 */
	function loadModalTitle() {
		$("#modal-title").empty();
		const $heading = $("#modal-body .heading").first();
		$heading.hide();

		let title = "";

		// Special handling for event modals
		if ($("#modal-body .event-wrapper .event-summary").length) {
			const eventsum = $("#modal-body .event-wrapper .event-summary").html();
			title = '<i class="fa fa-calendar" aria-hidden="true"></i>&nbsp;' + eventsum;
		} else {
			title = $heading.html();
		}

		if (title) {
			$("#modal-title").append(title);
		}
	}

	/**
	 * Load HTML content from a Friendica page into a modal.
	 *
	 * @param {string} url - The URL with HTML content
	 * @param {string} [id] - Optional ID of a specific HTML element to show
	 */
	function addToModal(url, id) {
		const char = window.qOrAmp ? window.qOrAmp(url) : (url.indexOf("?") < 0 ? "?" : "&");
		url = url + char + "mode=none";

		if (typeof id !== "undefined") {
			url = url + " div#" + id;
		}

		const $modal = $("#modal").modal();
		$modal.find("#modal-body").load(url, function (responseText, textStatus) {
			if (textStatus === "success" || textStatus === "notmodified") {
				$modal.show();
				loadModalTitle();
				// Re-initialize autosize for new modal content
				if (typeof window.autosize === "function") {
					window.autosize($(".modal .text-autosize"));
				}
			}
		});
	}

	/**
	 * Add an element (by its id) to a bootstrap modal.
	 *
	 * @param {string} id - The element ID selector
	 */
	function addElmToModal(id) {
		const elm = $(id).html();
		const $modal = $("#modal").modal();

		$modal.find("#modal-body").append(elm);
		loadModalTitle();
	}

	/**
	 * Load the HTML from the edit post page into the jot modal.
	 *
	 * @param {string} url - The edit post URL
	 */
	function editpost(url) {
		// Check if this is an event post
		const splitURL = window.parseUrl ? window.parseUrl(url) : { path: "" };
		if (splitURL.path && splitURL.path.indexOf("calendar/event/show") > -1) {
			addToModal(splitURL.path);
			return;
		}

		const $modal = $("#jot-modal").modal();
		const loadUrl = url + " #jot-sections";

		$(".jot-nav .jot-perms-lnk").parent("li").addClass("hidden");

		// Store original jot and remove it to avoid conflicts
		window.jotcache = $("#jot-content > #jot-sections");
		window.jotcache.detach();

		$("#jot-modal").addClass("edit-jot");

		// Handler is bound once in document.ready via bindJotResetOnce()
		// No need to call jotreset() here anymore

		$modal.find("#jot-modal-content").load(loadUrl, function (responseText, textStatus) {
			if (textStatus === "success" || textStatus === "notmodified") {
				const type = $(responseText).find("#profile-jot-form input[name='type']").val();

				if (type === "wall-comment" || type === "remote-comment") {
					$("#profile-jot-form #jot-title-wrap").hide();
					$("#profile-jot-form #jot-category-wrap").hide();
				}

				// Setup dropzone for comment editing
				if ($("#jot-text-wrap").length && typeof window.dzFactory !== "undefined") {
					window.dzFactory.setupDropzone("#jot-text-wrap", "profile-jot-text");
				}

				$modal.show();
				$("#jot-popup").show();

				const $profileJotText = $("#profile-jot-text");
				if ($profileJotText.length && typeof $profileJotText.linkPreview === "function") {
					window.linkPreview = $profileJotText.linkPreview();
				}
			}
		});
	}

	/**
	 * Give the active "jot-nav" list element the class "active".
	 *
	 * @param {HTMLElement} elm - The navigation link element
	 */
	function toggleJotNav(elm) {
		const tabpanel = elm.getAttribute("aria-controls");
		const isMobile = elm.classList.contains("jot-nav-lnk-mobile");

		// Toggle active class
		$(elm).closest("li").siblings("li").removeClass("active");
		$(elm).closest("li").addClass("active");

		// Toggle tab panels
		$("#profile-jot-form > [role=tabpanel]")
			.addClass("minimize")
			.attr("aria-hidden", "true");
		$("#" + tabpanel)
			.removeClass("minimize")
			.attr("aria-hidden", "false");

		// Set aria-selected states
		$("#jot-modal .modal-header .nav-tabs .jot-nav-lnk").attr("aria-selected", "false");
		elm.setAttribute("aria-selected", "true");

		// Handle specific tab panels
		if (tabpanel === "jot-preview-content") {
			if (typeof window.preview_post === "function") {
				window.preview_post();
			}
			$("#jot-preview-share").removeClass("minimize").attr("aria-hidden", "false");
		} else if (tabpanel === "jot-fbrowser-wrapper") {
			window.Dialog.showJot();
		}

		// Update mobile dropdown button text
		if (isMobile && typeof window.toggleDropdownText === "function") {
			window.toggleDropdownText(elm);
		}
	}

	/**
	 * Wall Message special handling - redirects to own server if needed.
	 *
	 * @param {string} url - The wall message URL
	 */
	function openWallMessage(url) {
		const parts = window.parseUrl ? window.parseUrl(url) : {};

		if (parts.host && parts.host !== window.location.host) {
			window.location.href = url;
		} else {
			addToModal(url);
		}
	}

	/**
	 * Load the content of an edit URL into a modal.
	 *
	 * @param {string} url - The event edit URL
	 */
	function eventEdit(url) {
		const char = window.qOrAmp ? window.qOrAmp(url) : (url.indexOf("?") < 0 ? "?" : "&");
		const fullUrl = url + char + "mode=none";

		$.get(fullUrl, function (data) {
			$("#modal-body").empty().append(data);
		}).done(function () {
			loadModalTitle();
		});
	}

	// Expose functions to global scope
	window.addToModal = addToModal;
	window.addElmToModal = addElmToModal;
	window.editpost = editpost;
	window.toggleJotNav = toggleJotNav;
	window.openWallMessage = openWallMessage;
	window.eventEdit = eventEdit;
	// jotreset is no longer needed as a public function since we bind the handler once
	// But keep it for backward compatibility just in case
	window.jotreset = function () {
		// Handler is now bound automatically in document.ready
		// This function is kept for backward compatibility
	};

})(jQuery, window, document);
// @license-end
