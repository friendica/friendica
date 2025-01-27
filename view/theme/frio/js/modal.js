// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
/**
 * Contains functions for bootstrap modal handling.
 */
$(document).ready(function () {
	// Clear bs modal on close.
	// We need this to prevent that the modal displays old content.
	$("body, footer").on("hidden.bs.modal", ".modal", function () {
		$(this).removeData("bs.modal");
		$("#modal-title").empty();
		$("#modal-body").empty();
		// Remove the file browser from jot (else we would have problems
		// with AjaxUpload.
		$(".fbrowser").remove();
		// Remove the AjaxUpload element.
		$(".ajaxbutton-wrapper").remove();
	});

	// Clear bs modal on close.
	// We need this to prevent that the modal displays old content.
	$("body").on("hidden.bs.modal", "#jot-modal", function () {
		// Restore cached jot at its hidden position ("#jot-content").
		$("#jot-content").append(jotcache);
		// Clear the jotcache.
		jotcache = "";
		// Destroy the attachment linkPreview for Jot.
		if (typeof linkPreview === "object") {
			linkPreview.destroy();
		}
	});

	// Navbar login.
	$("body").on("click", "#nav-login", function (e) {
		e.preventDefault();
		Dialog.show(this.href, this.dataset.originalTitle || this.title);
	});

	// Jot nav menu..
	$("body").on("click", "#jot-modal .jot-nav li .jot-nav-lnk", function (e) {
		e.preventDefault();
		toggleJotNav(this);
	});

	// Bookmarklet page needs an jot modal which appears automatically.
	if (window.location.pathname.indexOf("/bookmarklet") >= 0 && $("#jot-modal").length) {
		jotShow();
	}

	// Open filebrowser for elements with the class "image-select"
	// The following part handles the filebrowser for field_fileinput.tpl.
	$("body").on("click", ".image-select", function () {
		// Set a extra attribute to mark the clicked button.
		this.setAttribute("image-input", "select");
		Dialog.doImageBrowser("input");
	});

	// Insert filebrowser images into the input field (field_fileinput.tpl).
	$("body").on("fbrowser.photo.input", function (e, filename, embedcode, id, img) {
		// Select the clicked button by it's attribute.
		var elm = $("[image-input='select']");
		// Select the input field which belongs to this button.
		var input = elm.parent(".input-group").children("input");
		// Remove the special indicator attribut from the button.
		elm.removeAttr("image-input");
		// Insert the link from the image into the input field.
		input.val(img);
	});

	// Generic delegated event to open an anchor URL in a modal.
	// Used in the hovercard.
	document.getElementsByTagName("body")[0].addEventListener("click", function (e) {
		var target = e.target;
		while (target) {
			if (target.matches && target.matches("a.add-to-modal")) {
				addToModal(target.href);
				e.preventDefault();
				return false;
			}

			target = target.parentNode || null;
		}
	});
});

// Overwrite Dialog.show from main js to load the filebrowser into a bs modal.
Dialog.show = function (url, title) {
	if (typeof title === "undefined") {
		title = "";
	}

	var modal = $("#modal").modal();
	modal.find("#modal-header h4").html(title);
	modal.find("#modal-body").load(url, function (responseText, textStatus) {
		if (textStatus === "success" || textStatus === "notmodified") {
			modal.show();

			$(function () {
				Dialog._load(url);
			});
		}
	});
};

// Overwrite the function _get_url from main.js.
Dialog._get_url = function (type, name, id) {
	var hash = name;
	if (id !== undefined) hash = hash + "-" + id;
	return 'media/' + type + '/browser?mode=none&theme=frio#' + hash;
};

// Does load the filebrowser into the jot modal.
Dialog.showJot = function () {
	var type = "photo";
	var name = "main";

	var url = Dialog._get_url(type, name);
	if ($(".modal-body #jot-fbrowser-wrapper .fbrowser").length < 1) {
		// Load new content to fbrowser window.
		$("#jot-fbrowser-wrapper").load(url, function (responseText, textStatus) {
			if (textStatus === "success" || textStatus === "notmodified") {
				$(function () {
					Dialog._load(url);
				});
			}
		});
	}
};

// Init the filebrowser after page load.
Dialog._load = function (url) {
	// Get nickname & filebrowser type from the modal content.
	let filebrowser = document.getElementById("filebrowser");

	// Try to fetch the hash form the url.
	let match = url.match(/media\/[a-z]+\/.*(#.*)/);
	if (!filebrowser || match === null) {
		return; //not fbrowser
	}

	// Initialize the filebrowser.
	loadScript("view/js/ajaxupload.js");
	loadScript("view/theme/frio/js/module/media/browser.js", function () {
		Browser.init(filebrowser.dataset.nickname, filebrowser.dataset.type, match[1]);
	});
};

/**
 * Add first element with the class "heading" as modal title
 *
 * Note: this should be really done in the template
 * and is the solution where we havent done it until this
 * moment or where it isn't possible because of design
 */
function loadModalTitle() {
	// Clear the text of the title.
	$("#modal-title").empty();

	// Hide the first element with the class "heading" of the modal body.
	$("#modal-body .heading").first().hide();

	var title = "";

	// Get the text of the first element with "heading" class.
	title = $("#modal-body .heading").first().html();

	// for event modals we need some special handling
	if ($("#modal-body .event-wrapper .event-summary").length) {
		title = '<i class="fa fa-calendar" aria-hidden="true"></i>&nbsp;';
		var eventsum = $("#modal-body .event-wrapper .event-summary").html();
		title = title + eventsum;
	}

	// And append it to modal title.
	if (title !== "") {
		$("#modal-title").append(title);
	}
}

/**
 * This function loads html content from a friendica page into a modal.
 *
 * @param {string} url The url with html content.
 * @param {string} id The ID of a html element (can be undefined).
 * @returns {void}
 */
function addToModal(url, id) {
	var char = qOrAmp(url);

	url = url + char + "mode=none";
	var modal = $("#modal").modal();

	// Only search for an element if we have an ID.
	if (typeof id !== "undefined") {
		url = url + " div#" + id;
	}

	modal.find("#modal-body").load(url, function (responseText, textStatus) {
		if (textStatus === "success" || textStatus === "notmodified") {
			modal.show();

			//Get first element with the class "heading"
			//and use it as title.
			loadModalTitle();

			// We need to initialize autosize again for new
			// modal content.
			autosize($(".modal .text-autosize"));
		}
	});
}

// Add an element (by its id) to a bootstrap modal.
function addElmToModal(id) {
	var elm = $(id).html();
	var modal = $("#modal").modal();

	modal.find("#modal-body").append(elm).modal.show;

	loadModalTitle();
}

// Function to load the html from the edit post page into
// the jot modal.
function editpost(url) {
	// Next to normel posts the post can be an event post. The event posts don't
	// use the normal Jot modal. For event posts we will use a normal modal
	// But first we have to test if the url links to an event. So we will split up
	// the url in its parts.
	var splitURL = parseUrl(url);
	// Test if in the url path containing "calendar/event/show". If the path containing this
	// expression then we will call the addToModal function and exit this function at
	// this point.
	if (splitURL.path.indexOf("calendar/event/show") > -1) {
		addToModal(splitURL.path);
		return;
	}

	var modal = $("#jot-modal").modal();
	url = url + " #jot-sections";

	$(".jot-nav .jot-perms-lnk").parent("li").addClass("hidden");

	// For editpost we load the modal html of "jot-sections" of the edit page. So we would have two jot forms in
	// the page html. To avoid js conflicts we store the original jot in the variable jotcache.
	// After closing the modal original jot should be restored at its original position in the html structure.
	jotcache = $("#jot-content > #jot-sections");

	// Remove the original Jot as long as the edit Jot is open.
	jotcache.detach();

	// Add the class "edit" to the modal to have some kind of identifier to
	// have the possibility to e.g. put special event-listener.
	$("#jot-modal").addClass("edit-jot");

	jotreset();

	modal.find("#jot-modal-content").load(url, function (responseText, textStatus) {
		if (textStatus === "success" || textStatus === "notmodified") {
			// get the item type and hide the input for title and category if it isn't needed.
			var type = $(responseText).find("#profile-jot-form input[name='type']").val();
			if (type === "wall-comment" || type === "remote-comment") {
				// Hide title and category input fields because we don't.
				$("#profile-jot-form #jot-title-wrap").hide();
				$("#profile-jot-form #jot-category-wrap").hide();
			}

			// To make dropzone fileupload work on editing a comment, we need to
			// attach a new dropzone to modal
			if ($('#jot-text-wrap').length > 0) {
				dzFactory.setupDropzone('#jot-text-wrap', 'profile-jot-text');
			}

			modal.show();
			$("#jot-popup").show();
			if ($("#profile-jot-text").length > 0) {
				linkPreview = $("#profile-jot-text").linkPreview();
			}
		}
	});
}

// Remove content from the jot modal.
function jotreset() {
	// Clear bs modal on close.
	// We need this to prevent that the modal displays old content.
	$("body").on("hidden.bs.modal", "#jot-modal.edit-jot", function () {
		$(this).removeData("bs.modal");
		$(".jot-nav .jot-perms-lnk").parent("li").removeClass("hidden");
		$("#profile-jot-form #jot-title-wrap").show();
		$("#profile-jot-form #jot-category-wrap").show();

		// Remove the "edit-jot" class so we can the standard behavior on close.
		$("#jot-modal.edit-jot").removeClass("edit-jot");
		$("#jot-modal-content").empty();
	});
}

// Give the active "jot-nav" list element the class "active".
function toggleJotNav(elm) {
	// Get the ID of the tab panel which should be activated.
	var tabpanel = elm.getAttribute("aria-controls");
	var cls = hasClass(elm, "jot-nav-lnk-mobile");

	// Select all li of jot-nav and remove the active class.
	$(elm).parent("li").siblings("li").removeClass("active");
	// Add the active class to the parent of the link which was selected.
	$(elm).parent("li").addClass("active");

	// Minimize all tab content wrapper and activate only the selected
	// tab panel.
	$("#profile-jot-form > [role=tabpanel]").addClass("minimize").attr("aria-hidden", "true");
	$("#" + tabpanel)
		.removeClass("minimize")
		.attr("aria-hidden", "false");

	// Set the aria-selected states
	$("#jot-modal .modal-header .nav-tabs .jot-nav-lnk").attr("aria-selected", "false");
	elm.setAttribute("aria-selected", "true");

	// For some tab panels we need to execute other js functions.
	if (tabpanel === "jot-preview-content") {
		preview_post();
		// Make Share button visible in preview
		$("#jot-preview-share").removeClass("minimize").attr("aria-hidden", "false");
	} else if (tabpanel === "jot-fbrowser-wrapper") {
		$(function () {
			Dialog.showJot();
		});
	}

	// If element is a mobile dropdown nav menu we need to change the button text.
	if (cls) {
		toggleDropdownText(elm);
	}
}

// Wall Message needs a special handling because in some cases
// it redirects you to your own server. In such cases we can't
// load it into a modal.
function openWallMessage(url) {
	// Split the url in its parts.
	var parts = parseUrl(url);

	// If the host isn't the same we can't load it in a modal.
	// So we will go to to the url directly.
	if ("host" in parts && parts.host !== window.location.host) {
		window.location.href = url;
	} else {
		// Otherwise load the wall message into a modal.
		addToModal(url);
	}
}

// This function load the content of the edit url into a modal.
/// @todo Rename this function because it can be used for more than events.
function eventEdit(url) {
	var char = qOrAmp(url);
	url = url + char + "mode=none";

	$.get(url, function (data) {
		$("#modal-body").empty();
		$("#modal-body").append(data);
	}).done(function () {
		loadModalTitle();
	});
}
// @license-end
