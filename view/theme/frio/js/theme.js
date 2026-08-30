// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

var jotcache = ""; //The jot cache. We use it as cache to restore old/original jot content
var stickyAside = null; //The aside currently handled by sticky-kit, see initTheme()

function syncSecondNavTabmenu() {
	// Move page tabbar into second navbar after initial load and SPA navigations
	var $tabbar = $("section .tabbar-wrapper").first();
	var $tabmenu = $("#topbar-second > .container-fluid > #tabmenu");

	if ($tabbar.length && $tabmenu.length) {
		$tabbar.appendTo($tabmenu);
	}

	// Initialize responsive overflow menu for newly injected tab bars only once per element
	$("ul.tabs.flex-nav").each(function () {
		var $tabs = $(this);
		if ($tabs.data("spa-flexmenu-initialized")) {
			return;
		}

		$tabs.flexMenu({
			cutoff: 2,
			popupClass: "dropdown-menu pull-right",
			popupAbsolute: false,
			target: ".flex-target",
		});

		$tabs.data("spa-flexmenu-initialized", true);
	});
}

function syncSecondNavJotButton() {
	var $jotButton = $("#jotOpen");
	if (!$jotButton.length) {
		return;
	}

	$jotButton.appendTo("#topbar-second > .container-fluid > #navbar-button");

	if ($("#jot-popup").is(":hidden")) {
		$jotButton.hide();
	}

	if ($jotButton.hasClass("modal-open")) {
		$jotButton.off("click.spa-jot").on("click.spa-jot", function (e) {
			e.preventDefault();
			jotShow();
		});
	}
}

// Initialize theme functionality (called on initial load and after SPA navigation)
function initTheme() {
	// Destroy unused perfect scrollbar in aside element
	$("aside").perfectScrollbar("destroy");

	//fade in/out based on scrollTop value
	var scrollStart;

	$(window).off("scroll.frio-theme").on("scroll.frio-theme", function () {
		let currentScroll = $(this).scrollTop();

		// Top of the page or going up = hide the button
		if (!scrollStart || !currentScroll || currentScroll < scrollStart) {
			$("#back-to-top").fadeOut();
			scrollStart = currentScroll;
		}

		// Going down enough = show the button
		if (currentScroll - scrollStart > 100) {
			$("#back-to-top").fadeIn();
			scrollStart = currentScroll;
		}
	});

	// add the class "active" to tabmenuli if li > a does have the class active
	if ($("#tabmenu ul li a").hasClass("active")) {
		$("#tabmenu ul li a.active").parent("li").addClass("active");
	}

	// give select fields Bootstrap classes
	// @todo: this needs to be changed in friendica core
	$(".field.select, .field.custom").addClass("form-group");
	$(".field.select > select, .field.custom > select").addClass("form-control");

	// move/init tabbar in second nav bar
	syncSecondNavTabmenu();

	// add Jot button to the second navbar
	syncSecondNavJotButton();

	let $body = $("body");

	// show bulk deletion button at network page if checkbox is checked
	$body.off("change.frio-theme", "input.item-select").on("change.frio-theme", "input.item-select", function () {
		var checked = false;

		// We need to get all checked items, so it would close the delete button
		// if we uncheck one item and others are still checked.
		// So return checked = true if there is any checked item
		$("input.item-select").each(function () {
			if ($(this).is(":checked")) {
				checked = true;
				return false;
			}
		});

		if (checked) {
			$("#item-delete-selected").fadeTo(400, 1);
			$("#item-delete-selected").show();
		} else {
			$("#item-delete-selected").fadeTo(400, 0, function () {
				$("#item-delete-selected").hide();
			});
		}
	});

	// initialize the Bootstrap tooltips
	$body.off("mouseenter.frio-theme mouseleave.frio-theme").tooltip({
		selector: '[data-toggle="tooltip"]',
		container: "body",
		animation: true,
		html: true,
		placement: "auto",
		trigger: "hover",
		delay: {
			show: 500,
			hide: 100,
		},
		sanitizeFn: function (content) {
			return DOMPurify.sanitize(content);
		},
	});

	// initialize the bootstrap-select
	$(".selectpicker").off("change.frio-theme").selectpicker();

	// add search-heading to the second navbar
	if ($(".search-heading").length) {
		$(".search-heading").appendTo("#topbar-second > .container-fluid > #tabmenu");
	}

	// add search results heading to the second navbar
	// and insert the search value to the top nav search input
	if ($(".search-content-wrapper").length) {
		// get the text of the heading (we catch the plain text because we don't
		// want to have a h4 heading in the navbar
		var searchText = $(".section-title-wrapper > h2").html();

		// temporary workaround to avoid 'undefined' being displayed (issue #9789)
		// https://github.com/friendica/friendica/issues/9789
		if (typeof searchText === "undefined") {
			searchText = "";
		}
		// insert the plain text in a <h4> heading and give it a class
		var newText = '<h4 class="search-heading">' + searchText + "</h4>";
		// append the new heading to the navbar
		$("#topbar-second > .container-fluid > #tabmenu").append(newText);

		// try to get the value of the original search input to insert it
		// as value in the nav-search-input
		var searchValue = $("#search-wrapper .form-group-search input").val();

		// if the orignal search value isn't available use the location path as value
		if (typeof searchValue === "undefined") {
			// get the location path
			var urlPath = window.location.search;
			// and split it up in its parts
			var splitPath = urlPath.split(/(\?search?=)(.*$)/);

			if (typeof splitPath[2] !== "undefined") {
				// decode the path (e.g to decode %40 to the character @)
				var searchValue = decodeURIComponent(splitPath[2]);
			}
		}

		if (typeof searchValue !== "undefined") {
			$("#nav-search-input-field").val(searchValue);
		}
	}

	// move the "Save the search" button to the second navbar
	$(".search-content-wrapper #search-save").appendTo("#topbar-second > .container-fluid > #navbar-button");

	// append the vcard-short-info to the second nav after passing the element
	// with .fn (vcard username). Use scrollspy to get the scroll position.
	if ($("aside .vcard .fn").length) {
		$(".vcard .fn").scrollspy({
			min: $(".vcard .fn").position().top - 50,
			onLeaveTop: function onLeave(element) {
				$("#vcard-short-info").fadeOut(500, function () {
					$("#vcard-short-info").appendTo("#vcard-short-info-wrapper");
				});
			},
			onEnter: function (element) {
				$("#vcard-short-info").appendTo("#nav-short-info");
				$("#vcard-short-info").fadeIn(500);
			},
		});
	}

	// move the group contact information of the network page into the second navbar
	if ($(".network-content-wrapper > #viewcontact_wrapper-network").length) {
		// get the contact-wrapper element and append it to the second nav bar
		// Note: We need the first() element with this class since at the present time we
		// store also the js template information in the html code and thats why
		// there are two elements with this class but we don't want the js template
		$(".network-content-wrapper > #viewcontact_wrapper-network .contact-wrapper")
			.first()
			.appendTo("#nav-short-info");
	}

	// move heading from network stream to the second navbar nav-short-info section
	if ($(".network-content-wrapper > .section-title-wrapper").length) {
		// get the heading element
		var heading = $(".network-content-wrapper > .section-title-wrapper > h2");
		// get the text of the heading
		var headingContent = heading.html();
		// create a new element with the content of the heading
		var newText =
			'<h4 class="heading" data-toggle="tooltip" title="' + headingContent + '">' + headingContent + "</h4>";
		// remove the old heading element
		heading.remove(),
			// put the new element to the second nav bar
			$("#topbar-second #nav-short-info").append(newText);
	}

	if ($(".community-content-wrapper").length) {
		// get the heading element
		var heading = $(".community-content-wrapper > h3").first();
		// get the text of the heading
		var headingContent = heading.html();
		// create a new element with the content of the heading
		var newText = '<h4 class="heading">' + headingContent + "</h4>";
		// remove the old heading element
		heading.remove(),
			// put the new element to the second nav bar
			$("#topbar-second > .container-fluid > #tabmenu").append(newText);
	}

	// Dropdown menus with the class "dropdown-head" will display the active tab
	// as button text
	$body.off("click.frio-theme", ".dropdown-head .dropdown-menu li a, .dropdown-head .dropdown-menu li button").on("click.frio-theme", ".dropdown-head .dropdown-menu li a, .dropdown-head .dropdown-menu li button", function () {
		toggleDropdownText(this);
	});

	// Change the css class while clicking on the switcher elements
	$(".toggle label, .toggle .toggle-handle").off("click.frio-theme").on("click.frio-theme", function (event) {
		event.preventDefault();
		// Get the value of the input element
		var input = $(this).siblings("input");
		var val = 1 - input.val();
		var id = input.attr("id");

		// The css classes for "on" and "off"
		var onstyle = "btn-primary";
		var offstyle = "btn-default off";

		// According to the value of the input element we need to decide
		// which class need to be added and removed when changing the switch
		var removedclass = val == 0 ? onstyle : offstyle;
		var addedclass = val == 0 ? offstyle : onstyle;
		$("#" + id + "_onoff")
			.addClass(addedclass)
			.removeClass(removedclass);

		// After changing the switch the input element is getting
		// the newvalue
		input.val(val);
	});

	// Set the padding for input elements with inline buttons
	//
	// In Frio we use some input elements where the submit button is visually
	// inside the input field (through css). We need to set a padding-right
	// to the input element where the padding value would be at least the width
	// of the button. Otherwise long user input would be invisible because it is
	// behind the button.
	$body.off("click.frio-theme", ".form-group-search > input").on("click.frio-theme", ".form-group-search > input", function () {
		// Get the width of the button (if the button isn't available
		// buttonWidth will be null
		var buttonWidth = $(this).next(".form-button-search").outerWidth();

		if (buttonWidth) {
			// Take the width of the button and ad 5px
			var newWidth = buttonWidth + 5;
			// Set the padding of the input element according
			// to the width of the button
			$(this).css("padding-right", newWidth);
		}
	});

	/*
	 * This event handler hides all comment UI when the user clicks anywhere on the page
	 * It ensures that we aren't closing the current comment box
	 *
	 * We are making an exception for buttons because of a race condition with the
	 * comment opening button that results in an already closed comment UI.
	 */
	$(document).off("mousedown.frio-theme").on("mousedown.frio-theme", function (event) {
		if (event.target.type === "button") {
			return true;
		}

		if ($(event.target).closest(".fg-emoji-container").length) {
			return true;
		}

		var $dontclosethis = $(event.target).closest(".wall-item-comment-wrapper").find(".comment-edit-form");
		$(".wall-item-comment-wrapper .comment-edit-submit-wrapper:visible").each(function () {
			var $parent = $(this).parent(".comment-edit-form");
			var itemId = $parent.data("itemId");

			if ($dontclosethis[0] != $parent[0]) {
				var textarea = $parent.find("textarea").get(0);

				commentCloseUI(textarea, itemId);
			}
		});
	});

	// Customize some elements when the app is used in standalone mode on Android
	if (window.matchMedia("(display-mode: standalone)").matches) {
		// Open links to source outside of the webview
		$("body").off("click.frio-theme", ".plink").on("click.frio-theme", ".plink", function (e) {
			$(e.target).attr("target", "_blank");
		});
	}

	/*
	 * This event listeners ensures that the textarea size is updated event if the
	 * value is changed externally (textcomplete, insertFormatting, fbrowser...)
	 */
	$(document).off("change.frio-theme").on("change.frio-theme", "textarea", function (event) {
		autosize.update(event.target);
	});

	/*
	 * Sticky aside on page scroll
	 * We enable the sticky aside only when window is wider than
	 * 976px - which is the maximum width where the aside is shown in
	 * mobile style - because on chrome-based browsers (desktop and
	 * android) the sticky plugin in mobile style causes the browser to
	 * scroll back to top the main content, making it impossible
	 * to navigate.
	 * A side effect is that the sitky aside isn't really responsive,
	 * since is enabled or not at page loading time.
	 */
	if ($(window).width() > 976) {
		// Detach the previous aside first: SPA navigation replaces the element,
		// and its window/body handlers stay bound until sticky_kit:detach.
		if (stickyAside) {
			stickyAside.trigger("sticky_kit:detach");
		}
		stickyAside = $("aside");
		stickyAside.stick_in_parent({
			offset_top: 100, // px, header + tab bar + spacing
			recalc_every: 10,
		});
		// recalculate sticky aside on clicks on <a> elements
		// this handle height changes on expanding submenus
		$("aside").off("click.frio-theme", "a").on("click.frio-theme", "a", function () {
			$(document.body).trigger("sticky_kit:recalc");
		});
	}

	/*
	 * Add or remove "aside-out" class to body tag
	 * when the mobile aside is shown or hidden.
	 * The class is used in css to disable scroll in page when the aside
	 * is shown.
	 */
	$("aside")
		.off("shown.bs.offcanvas.frio-theme hidden.bs.offcanvas.frio-theme")
		.on("shown.bs.offcanvas.frio-theme", function () {
			$body.addClass("aside-out");
		})
		.on("hidden.bs.offcanvas.frio-theme", function () {
			$body.removeClass("aside-out");
		});

	// Right offcanvas elements
	let $offcanvas_right_toggle = $(".offcanvas-right-toggle");
	let $offcanvas_right_container = $("#offcanvasUsermenu"); // Use ID for faster lookup, class is .offcanvas-right

	$offcanvas_right_toggle.off("click.frio-theme").on("click.frio-theme", function (event) {
		event.preventDefault();
		$("body").toggleClass("offcanvas-right-active");
	});

	// Close the right offcanvas menu when clicking somewhere
	$(document).off("mouseup.frio-theme touchend.frio-theme").on("mouseup.frio-theme touchend.frio-theme", function (event) {
		if (
			// Clicked element is not inside the menu
			!$offcanvas_right_container.is(event.target) &&
			$offcanvas_right_container.has(event.target).length === 0 &&
			// Clicked element is not the toggle button (taken care by the toggleClass above)
			!$offcanvas_right_toggle.is(event.target) &&
			$offcanvas_right_toggle.has(event.target).length === 0
		) {
			$("body").removeClass("offcanvas-right-active");
		}
	});

	// Event listener for 'Show & hide event map' button in the network stream.
	$body.off("click.frio-theme", ".event-map-btn").on("click.frio-theme", ".event-map-btn", function () {
		showHideEventMap(this);
	});

	// Comment form submit - only register once to prevent duplicate submissions
	if (typeof frioCommentFormHandlerRegistered === 'undefined') {
		frioCommentFormHandlerRegistered = true;
		$body.off("submit.frio-theme", ".comment-edit-form").on("submit.frio-theme", ".comment-edit-form", function (e) {
			let $form = $(this);
			let id = $form.data("item-id");

			// Compose page form exception: id is always 0 and form must not be submitted asynchronously
			if (id === 0) {
				return;
			}

			e.preventDefault();

			let $commentSubmit = $form.find(".comment-edit-submit").button("loading");

			unpause();
			commentBusy = true;
			showPosting();

			$.post("item", $form.serialize(), "json")
				.done(function (data) {
					showProcessing();
					if (data.success) {
						$("#comment-edit-wrapper-" + id).hide();
						let $textarea = $("#comment-edit-text-" + id);
						$textarea.val("");
						if ($textarea.get(0)) {
							commentClose($textarea.get(0), id);
						}
						if (timer) {
							clearTimeout(timer);
						}
						timer = setTimeout(NavUpdate, 10);
						updateItem(id, data.guid ?? null);
					}
					if (data.reload) {
						window.location.href = data.reload;
					}
				})
				.always(function () {
					hideLoading();
					commentBusy = false;
					$commentSubmit.button("reset");
				});
		});
	}

	try {
		navigator.canShare({ url: "#", });
	} catch(err) {
		$('.button-browser-share').hide();
	}

	// initialize autosize for the textareas
	autosize($("textarea.text-autosize"));
}

// Register theme initialization for initial page load and SPA navigation
window.onDocumentReady('body', initTheme);

// Keep existing spa:navigate handler for tabmenu syncing
window.addEventListener("spa:navigate", function () {
	syncSecondNavTabmenu();
	syncSecondNavJotButton();
});

function openClose(theID) {
	var elem = document.getElementById(theID);

	if ($(elem).is(":visible")) {
		$(elem).slideUp(200);
	} else {
		$(elem).slideDown(200);
	}
}

function showHide(theID) {
	var elem = document.getElementById(theID);
	var edit = document.getElementById("comment-edit-submit-wrapper-" + theID.match("[0-9$]+"));

	if ($(elem).is(":visible")) {
		if (!$(edit).is(":visible")) {
			edit.style.display = "block";
		} else {
			elem.style.display = "none";
		}
	} else {
		elem.style.display = "block";
	}
}

// Show & hide event map in the network stream by button click.
function showHideEventMap(elm) {
	// Get the id of the map element - it should be provided through
	// the attribute "data-map-id".
	var mapID = elm.getAttribute("data-map-id");

	// Get translation labels.
	var mapshow = elm.getAttribute("data-show-label");
	var maphide = elm.getAttribute("data-hide-label");

	// Change the button labels.
	if (elm.innerText == mapshow) {
		$("#" + elm.id).text(maphide);
	} else {
		$("#" + elm.id).text(mapshow);
	}
	// Because maps are iframe elements, we cant hide it through css (display: none).
	// We solve this issue by putting the map outside the screen with css.
	// So the first time the 'Show map' button is pressed we move the map
	// element into the screen area.
	var mappos = $("#" + mapID).css("position");

	if (mappos === "absolute") {
		$("#" + mapID).hide();
		$("#" + mapID).css({ position: "relative", left: "auto", top: "auto" });
		openClose(mapID);
	} else {
		openClose(mapID);
	}
	return false;
}

function justifyPhotos() {
	justifiedGalleryActive = true;
	$("#photo-album-contents")
		.justifiedGallery({
			margins: 3,
			border: 0,
			captions: false,
			sizeRangeSuffixes: {
				lt48: "-6",
				lt80: "-5",
				lt300: "-4",
				lt320: "-2",
				lt640: "-1",
				lt1024: "-0",
			},
		})
		.on("jg.complete", function (e) {
			justifiedGalleryActive = false;
		});
}

// Load a js script to the html head.
// Currently Only used to load browser.js, which could possibly be handled in a better way
function loadScript(url, callback) {
	// Check if the script is already in the html head.
	var oscript = $('head script[src="' + url + '"]');

	// Delete the old script from head.
	if (oscript.length > 0) {
		oscript.remove();
	}
	// Adding the script tag to the head as suggested before.
	var head = document.getElementsByTagName("head")[0];
	var script = document.createElement("script");
	script.type = "text/javascript";
	script.src = url;

	// Then bind the event to the callback function.
	// There are several events for cross browser compatibility.
	script.onreadystatechange = callback;
	script.onload = callback;

	// Fire the loading.
	head.appendChild(script);
}

// Does we need a ? or a & to append values to a url
function qOrAmp(url) {
	if (url.search("\\?") < 0) {
		return "?";
	} else {
		return "&";
	}
}

String.prototype.normalizeLink = function () {
	var ret = this.replace("https:", "http:");
	var ret = ret.replace("//www", "//");
	return ret.rtrim();
};

function cleanContactUrl(url) {
	var parts = parseUrl(url);

	if (!("scheme" in parts) || !("host" in parts)) {
		return url;
	}

	var newUrl = parts["scheme"] + "://" + parts["host"];

	if ("port" in parts) {
		newUrl += ":" + parts["port"];
	}

	if ("path" in parts) {
		newUrl += parts["path"];
	}

	return newUrl;
}

function parseUrl(str, component) {
	//       discuss at: http://locutusjs.io/php/parse_url/
	//      original by: Steven Levithan (http://blog.stevenlevithan.com)
	// reimplemented by: Brett Zamir (http://brett-zamir.me)
	//         input by: Lorenzo Pisani
	//         input by: Tony
	//      improved by: Brett Zamir (http://brett-zamir.me)
	//           note 1: original by http://stevenlevithan.com/demo/parseuri/js/assets/parseuri.js
	//           note 1: blog post at http://blog.stevenlevithan.com/archives/parseuri
	//           note 1: demo at http://stevenlevithan.com/demo/parseuri/js/assets/parseuri.js
	//           note 1: Does not replace invalid characters with '_' as in PHP,
	//           note 1: nor does it return false with
	//           note 1: a seriously malformed URL.
	//           note 1: Besides function name, is essentially the same as parseUri as
	//           note 1: well as our allowing
	//           note 1: an extra slash after the scheme/protocol (to allow file:/// as in PHP)
	//        example 1: parse_url('http://user:pass@host/path?a=v#a')
	//        returns 1: {scheme: 'http', host: 'host', user: 'user', pass: 'pass', path: '/path', query: 'a=v', fragment: 'a'}
	//        example 2: parse_url('http://en.wikipedia.org/wiki/%22@%22_%28album%29')
	//        returns 2: {scheme: 'http', host: 'en.wikipedia.org', path: '/wiki/%22@%22_%28album%29'}
	//        example 3: parse_url('https://host.domain.tld/a@b.c/folder')
	//        returns 3: {scheme: 'https', host: 'host.domain.tld', path: '/a@b.c/folder'}
	//        example 4: parse_url('https://gooduser:secretpassword@www.example.com/a@b.c/folder?foo=bar')
	//        returns 4: { scheme: 'https', host: 'www.example.com', path: '/a@b.c/folder', query: 'foo=bar', user: 'gooduser', pass: 'secretpassword' }

	var query;

	var mode =
		(typeof require !== "undefined" ? require("../info/ini_get")("locutus.parse_url.mode") : undefined) || "php";

	var key = [
		"source",
		"scheme",
		"authority",
		"userInfo",
		"user",
		"pass",
		"host",
		"port",
		"relative",
		"path",
		"directory",
		"file",
		"query",
		"fragment",
	];

	// For loose we added one optional slash to post-scheme to catch file:/// (should restrict this)
	var parser = {
		php: new RegExp(
			[
				"(?:([^:\\/?#]+):)?",
				"(?:\\/\\/()(?:(?:()(?:([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?))?",
				"()",
				"(?:(()(?:(?:[^?#\\/]*\\/)*)()(?:[^?#]*))(?:\\?([^#]*))?(?:#(.*))?)",
			].join(""),
		),
		strict: new RegExp(
			[
				"(?:([^:\\/?#]+):)?",
				"(?:\\/\\/((?:(([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?))?",
				"((((?:[^?#\\/]*\\/)*)([^?#]*))(?:\\?([^#]*))?(?:#(.*))?)",
			].join(""),
		),
		loose: new RegExp(
			[
				"(?:(?![^:@]+:[^:@\\/]*@)([^:\\/?#.]+):)?",
				"(?:\\/\\/\\/?)?",
				"((?:(([^:@\\/]*):?([^:@\\/]*))?@)?([^:\\/?#]*)(?::(\\d*))?)",
				"(((\\/(?:[^?#](?![^?#\\/]*\\.[^?#\\/.]+(?:[?#]|$)))*\\/?)?([^?#\\/]*))",
				"(?:\\?([^#]*))?(?:#(.*))?)",
			].join(""),
		),
	};

	var m = parser[mode].exec(str);
	var uri = {};
	var i = 14;

	while (i--) {
		if (m[i]) {
			uri[key[i]] = m[i];
		}
	}

	if (component) {
		return uri[component.replace("PHP_URL_", "").toLowerCase()];
	}

	if (mode !== "php") {
		var name =
			(typeof require !== "undefined" ? require("../info/ini_get")("locutus.parse_url.queryKey") : undefined) ||
			"queryKey";
		parser = /(?:^|&)([^&=]*)=?([^&]*)/g;
		uri[name] = {};
		query = uri[key[12]] || "";
		query.replace(parser, function ($0, $1, $2) {
			if ($1) {
				uri[name][$1] = $2;
			}
		});
	}

	delete uri.source;
	return uri;
}

// trim function to replace whitespace after the string
String.prototype.rtrim = function () {
	var trimmed = this.replace(/\s+$/g, "");
	return trimmed;
};

// format a html string to pure text
function htmlToText(htmlString) {
	// Replace line breaks with spaces
	var text = htmlString.replace(/<br>/g, " ");
	// Strip the text out of the html string
	text = text.replace(/<[^>]*>/g, "");

	return text;
}

/**
 * Sends a /like API call and updates the display of the relevant action button
 * before the update reloads the item.
 *
 * @param {int}     ident The id of the relevant item
 * @param {string}  verb  The verb of the action
 * @param {boolean} un    Whether to perform an activity removal instead of creation
 */
function doActivityItemAction(ident, verb, un) {
	showPosting();
	_verb = un ? 'un' + verb : verb;
	var thumbsClass = '';
	switch (verb) {
		case 'like':
			thumbsClass = 'ri-thumb-up-line';
			break;
		case 'dislike':
			thumbsClass = 'ri-thumb-down-line';
			break;
		case 'announce':
			thumbsClass = 'ri-repeat-line';
			break;
		case 'attendyes':
			thumbsClass = 'ri-check-line';
			break;
		case 'attendno':
			thumbsClass = 'ri-close-line';
			break;
		case 'attendmaybe':
			thumbsClass = 'ri-question-line';
	}
	if (verb.indexOf('announce') === 0 ) {
		// Share-Button(s)
		// remove share-symbol, to replace it by rotator
		$('button[id^=shareMenuOptions-' + ident.toString() + '] i:first-child').removeClass('ri-share-forward-line');
		$('button[id^=announce-' + ident.toString() + '] i:first-child').removeClass('ri-repeat-line');
		// avoid multiple rotators on like/share-button if klicked multiple times.
		if ($('img[id^=waitfor-' + verb + '-' + ident.toString() + ']').length == 0) {
			// append rotator to the shareMenu-button for small media
			$('<img>')
				.attr({id: 'waitfor-' + verb + '-' + ident.toString(), src: 'images/rotator.gif'})
				.addClass('ri')
				.appendTo($('button[id^=shareMenuOptions-' + ident.toString() + '] i:first-child' ));
		}
	}
	$('button[id^=' + verb + '-' + ident.toString() + '] i:first-child').removeClass(thumbsClass);
	// if verb is announce, then one rotator is added above to the shareMedia-dropdown button
	if ($('button:not(button.dropdown-toggle) img#waitfor-' + verb + '-' + ident.toString()).length == 0) {
		$('<img>')
			.attr({id: 'waitfor-' + verb + '-' + ident.toString(), src: 'images/rotator.gif'})
			.addClass('ri')
			.appendTo($('button[id^=' + verb + '-' + ident.toString() + '] i:first-child'));
	}
	$.post('item/' + ident.toString() + '/activity/' + _verb)
		.done(function(data){
			showProcessing();
			$('img[id^=waitfor-' + verb + '-' + ident.toString() + ']').remove();
			if (data.status == 'ok') {
				if (verb.indexOf('attend') === 0) {
					$('button[id^=attend][id$=' + ident.toString() + ']').removeClass('active')
					$('button#attendyes-' + ident.toString()).attr('onclick', 'javascript:doActivityItemAction(' + ident +', "attendyes")');
					$('button#attendno-' + ident.toString()).attr('onclick', 'javascript:doActivityItemAction(' + ident +', "attendno")');
					$('button#attendmaybe-' + ident.toString()).attr('onclick', 'javascript:doActivityItemAction(' + ident +', "attendmaybe")');
				}
				if (data.verb == 'un' + verb) {
					// like/dislike buttons
					$('button[id^=' + verb + '-' + ident.toString() + ']' )
						.removeClass('active')
						.attr('onclick', 'doActivityItemAction(' + ident +', "' + verb + '")');
					// link in share-menu
					$('a[id^=' + verb + '-' + ident.toString() + ']' )
						.removeClass('active')
						.attr('href', 'javascript:doActivityItemAction(' + ident +', "' + verb + '")');
					$('a[id^=' + verb + '-' + ident.toString() + '] i:first-child' ).addClass('ri-repeat-line').removeClass('ri-forbid-2-line');
				} else {
					// like/dislike buttons
					$('button[id^=' + verb + '-' + ident.toString() + ']' )
						.addClass('active')
						.attr('onclick', 'doActivityItemAction(' + ident + ', "' + verb + '", true )');
					// link in share-menu
					$('a[id^=' + verb + '-' + ident.toString() + ']' )
						.addClass('active')
						.attr('href', 'javascript:doActivityItemAction(' + ident + ', "' + verb + '", true )');
					$('a[id^=' + verb + '-' + ident.toString() + '] i:first-child' ).removeClass('ri-repeat-line').addClass('ri-forbid-2-line');
				}
				$('button[id^=' + verb + '-' + ident.toString() + '] i:first-child').addClass(thumbsClass);
				if (verb.indexOf('announce') === 0 ) {
					// ShareMenuButton
					$('button[id^=shareMenuOptions-' + ident.toString() + '] i:first-child').addClass('ri-share-forward-line');
					if (data.verb == 'un' + verb) {
						$('button[id^=shareMenuOptions-' + ident.toString() + ']').removeClass('active');
					} else {
						$('button[id^=shareMenuOptions-' + ident.toString() + ']').addClass('active');
					}
				}
				updateItem(ident.toString());
			} else {
				/* server-response was not ok. Database-problems or some changes in
				 * data?
				 * reset all buttons
				 */
				$('img[id^=waitfor-' + verb + '-' + ident.toString() + ']').remove();
				$('button[id^=shareMenuOptions-' + ident.toString() + '] i:first-child').addClass('ri-share-forward-line');
				$('button[id^=' + verb + '-' + ident.toString() + '] i:first-child').addClass(thumbsClass);
				$('a[id^=' + verb + '-' + ident.toString() + '] i:first-child').addClass(thumbsClass);
				$.jGrowl(aActErr[verb] + '<br>(' + aErrType['srvErr'] + ')', {sticky: false, theme: 'info', life: 5000});
			}
		})
		.fail(function(data){
			// Server could not be reached successfully
			$('img[id^=waitfor-' + verb + '-' + ident.toString() + ']').remove();
			$('button[id^=shareMenuOptions-' + ident.toString() + '] i:first-child').addClass('ri-share-forward-line');
			$('button[id^=' + verb + '-' + ident.toString() + '] i:first-child').addClass(thumbsClass);
			$('a[id^=' + verb + '-' + ident.toString() + '] i:first-child').addClass(thumbsClass);
			$.jGrowl(aActErr[verb] + '<br>(' + aErrType['netErr'] + ')', {sticky: false, theme: 'info', life: 5000});
		})
		.always(function() {
			hideLoading();
		});
}

// Decodes a hexadecimally encoded binary string
function hex2bin(s) {
	//  discuss at: http://locutus.io/php/hex2bin/
	// original by: Dumitru Uzun (http://duzun.me)
	//   example 1: hex2bin('44696d61')
	//   returns 1: 'Dima'
	//   example 2: hex2bin('00')
	//   returns 2: '\x00'
	//   example 3: hex2bin('2f1q')
	//   returns 3: false
	var ret = [];
	var i = 0;
	var l;
	s += "";

	for (l = s.length; i < l; i += 2) {
		var c = parseInt(s.substr(i, 1), 16);
		var k = parseInt(s.substr(i + 1, 1), 16);
		if (isNaN(c) || isNaN(k)) {
			return false;
		}
		ret.push((c << 4) | k);
	}
	return String.fromCharCode.apply(String, ret);
}

// Convert binary data into hexadecimal representation
function bin2hex(s) {
	// From: http://phpjs.org/functions
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   bugfixed by: Onno Marsman
	// +   bugfixed by: Linuxworld
	// +   improved by: ntoniazzi (http://phpjs.org/functions/bin2hex:361#comment_177616)
	// *     example 1: bin2hex('Kev');
	// *     returns 1: '4b6576'
	// *     example 2: bin2hex(String.fromCharCode(0x00));
	// *     returns 2: '00'

	var i,
		l,
		o = "",
		n;

	s += "";

	for (i = 0, l = s.length; i < l; i++) {
		n = s.charCodeAt(i).toString(16);
		o += n.length < 2 ? "0" + n : n;
	}

	return o;
}

// Dropdown menus with the class "dropdown-head" will display the active tab
// as button text
function toggleDropdownText(elm) {
	$(elm)
		.closest(".dropdown")
		.find(".btn")
		.html($(elm).html() + ' <span class="caret"></span>');
	$(elm).closest(".dropdown").find(".btn").val($(elm).data("value"));
	$(elm).closest("ul").children("li").show();
	$(elm).parent("li").hide();
}

// Check if element does have a specific class
function hasClass(elem, cls) {
	return (" " + elem.className + " ").indexOf(" " + cls + " ") > -1;
}

// Send on <CTRL>+<Enter> or <META>+<Enter> on macos
// e: event
// submit: the id of the submitbutton
function sendOnCtrlEnter(e, submit) {
	if ((e.ctrlKey || e.metaKey) && (e.keyCode == 13 || e.keyCode == 10)) {
		showPosting();
		$("#" + submit).trigger('click');
		hideLoading();
	}
}
// @license-end
