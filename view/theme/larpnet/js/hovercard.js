// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
/*
 * The javascript for friendicas hovercard. Bootstraps popover is needed.
 *
 * Much parts of the code are from Hannes Mannerheims <h@nnesmannerhe.im>
 * qvitter code (https://github.com/hannesmannerheim/qvitter)
 *
 * It is licensed under the GNU Affero General Public License <http://www.gnu.org/licenses/>
 *
 */
$(document).ready(function () {
	let $body = $("body");
	// Prevents normal click action on click hovercard elements
	$body.on("click", ".userinfo.click-card", function (e) {
		e.preventDefault();
	});
	// This event listener needs to be declared before the one that removes
	// all cards so that we can stop the immediate propagation of the event
	// Since the manual popover appears instantly and the hovercard removal is
	// on a 100ms delay, leaving event propagation immediately hides any click hovercard
	$body.on("mousedown", ".userinfo.click-card", function (e) {
		e.stopImmediatePropagation();
		let timeNow = new Date().getTime();

		let contactUrl = false;
		let targetElement = $(this);

		// get href-attribute
		if (targetElement.is("[href]")) {
			contactUrl = targetElement.attr("href");
		} else {
			return true;
		}

		// no hovercard for anchor links
		if (contactUrl.substring(0, 1) === "#") {
			return true;
		}

		openHovercard(targetElement, contactUrl, timeNow);
	});

	// hover cards should be removed very easily, e.g. when any of these events happens
	$body.on("mouseleave touchstart scroll mousedown submit keydown", function (e) {
		// remove hover card only for desktop user, since on mobile we open the hovercards
		// by click event insteadof hover
		removeAllHovercards(e, new Date().getTime());
	});

	$body
		.on("mouseover", ".userinfo.hover-card, .wall-item-responses a, .wall-item-bottom .mention a", function (e) {
			let timeNow = new Date().getTime();
			removeAllHovercards(e, timeNow);
			let contactUrl = false;
			let targetElement = $(this);

			// get href-attribute
			if (targetElement.is("[href]")) {
				contactUrl = targetElement.attr("href");
			} else {
				return true;
			}

			// no hover card if the element has the no-hover-card class
			if (targetElement.hasClass("no-hover-card")) {
				return true;
			}

			// no hovercard for anchor links
			if (contactUrl.substring(0, 1) === "#") {
				return true;
			}

			targetElement.attr("data-awaiting-hover-card", timeNow);

			// Delay until the hover-card does appear
			setTimeout(function () {
				if (
					targetElement.is(":hover") &&
					parseInt(targetElement.attr("data-awaiting-hover-card"), 10) === timeNow &&
					$(".hovercard").length === 0
				) {
					openHovercard(targetElement, contactUrl, timeNow);
				}
			}, 500);
		})
		.on("mouseleave", ".userinfo.hover-card, .wall-item-responses a, .wall-item-bottom .mention a", function (e) {
			// action when mouse leaves the hover-card
			removeAllHovercards(e, new Date().getTime());
		});

	// if we're hovering a hover card, give it a class, so we don't remove it
	$body.on("mouseover", ".hovercard", function (e) {
		$(this).addClass("dont-remove-card");
	});

	$body.on("mouseleave", ".hovercard", function (e) {
		$(this).removeClass("dont-remove-card");
		$(this).popover("hide");
	});
}); // End of $(document).ready

// removes all hover cards
function removeAllHovercards(event, priorTo) {
	// don't remove hovercards until after 100ms, so user have time to move the cursor to it (which gives it the dont-remove-card class)
	setTimeout(function () {
		$.each($(".hovercard"), function () {
			let title = $(this).attr("data-orig-title");
			// don't remove card if it was created after removeAllhoverCards() was called
			if ($(this).data("card-created") < priorTo) {
				// don't remove it if we're hovering it right now!
				if (!$(this).hasClass("dont-remove-card")) {
					let $handle = $('[data-hover-card-active="' + $(this).data("card-created") + '"]');
					$handle.removeAttr("data-hover-card-active");

					// Restoring the popover handle title
					let title = $handle.attr("data-orig-title");
					$handle.attr({ "data-orig-title": "", title: title });

					$(this).popover("hide");
				}
			}
		});
	}, 100);
}

function openHovercard(targetElement, contactUrl, timeNow) {
	// store the title in a data attribute because Bootstrap
	// popover destroys the title attribute.
	let title = targetElement.attr("title");
	targetElement.attr({ "data-orig-title": title, title: "" });

	// get an additional data attribute if the card is active
	targetElement.attr("data-hover-card-active", timeNow);
	// get the whole html content of the hover card and
	// push it to the bootstrap popover
	getHoverCardContent(contactUrl, function (data) {
		if (data) {
			targetElement
				.popover({
					html: true,
					placement: function () {
						// Calculate the placement of the hovercard (if top or bottom)
						// The placement depence on the distance between window top and the element
						// which triggers the hover-card
						let get_position = $(targetElement).offset().top - $(window).scrollTop();
						if (get_position < 270) {
							return "bottom";
						}
						return "top";
					},
					trigger: "manual",
					template:
						'<div class="popover hovercard" data-card-created="' +
						timeNow +
						'"><div class="arrow"></div><div class="popover-content hovercard-content"></div></div>',
					content: data,
					container: "body",
					sanitizeFn: function (content) {
						return DOMPurify.sanitize(content);
					},
				})
				.popover("show");
		}
	});
}

getHoverCardContent.cache = {};

function getHoverCardContent(contact_url, callback) {
	let postdata = {
		url: contact_url,
	};

	// Normalize and clean the profile so we can use a standardized url
	// as key for the cache
	let nurl = cleanContactUrl(contact_url).normalizeLink();

	// If the contact is already in the cache use the cached result instead
	// of doing a new ajax request
	if (nurl in getHoverCardContent.cache) {
		callback(getHoverCardContent.cache[nurl]);
		return;
	}

	$.ajax({
		url: baseurl + "/contact/hovercard",
		data: postdata,
		success: function (data, textStatus, request) {
			getHoverCardContent.cache[nurl] = data;
			callback(data);
		},
	});
}
// @license-end
