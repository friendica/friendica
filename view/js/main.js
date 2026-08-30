// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later

// https://developer.mozilla.org/en-US/docs/Web/API/Element/matches#Polyfill
if (!Element.prototype.matches) {
	Element.prototype.matches =
		Element.prototype.matchesSelector ||
		Element.prototype.mozMatchesSelector ||
		Element.prototype.msMatchesSelector ||
		Element.prototype.oMatchesSelector ||
		Element.prototype.webkitMatchesSelector ||
		function(s) {
			var matches = (this.document || this.ownerDocument).querySelectorAll(s),
				i = matches.length;
			while (--i >= 0 && matches.item(i) !== this) {}
			return i > -1;
		};
}

const ModuleLifecycleReadyEvent = Object.freeze({
	DOCUMENT: 'document',
	WINDOW: 'window'
});

const registerModuleLifecycle = function (target, initialize, readyEvent) {
	if (typeof target !== 'string' || target === '') {
		return null;
	}

	if (!window.__friendica_unpoly_lifecycle_registry) {
		window.__friendica_unpoly_lifecycle_registry = new Map();
	}

	const initializerFingerprint = (function () {
		if (typeof initialize !== 'function') {
			return 'init';
		}

		if (initialize.name) {
			return initialize.name;
		}

		try {
			return initialize.toString().replace(/\s+/g, ' ').trim().substring(0, 240);
		} catch (error) {
			return 'anonymous';
		}
	})();

	const registryKey = target + '::' + initializerFingerprint + '::' + readyEvent;
	if (window.__friendica_unpoly_lifecycle_registry.has(registryKey)) {
		return window.__friendica_unpoly_lifecycle_registry.get(registryKey);
	}

	const refresh = function () {
		const targetElement = document.querySelector(target);
		if (!targetElement || typeof initialize !== 'function') {
			return null;
		}
		return initialize(targetElement);
	};

	if (window.addEventListener) {
		window.addEventListener('spa:navigate', refresh, { passive: true });

		if (readyEvent === ModuleLifecycleReadyEvent.WINDOW) {
			if (spaEnabled) {
				window.addEventListener('spa:window:load', refresh, { passive: true });
			} else {
				$(window).load(refresh);
			}
		} else {
			if (spaEnabled) {
				window.addEventListener('spa:document:ready', refresh, { passive: true });
			} else {
				$(document).ready(refresh);
			}
		}

		if ((document.readyState === 'complete' || document.readyState === 'interactive') && !window.__spa_reinit_phase) {
			setTimeout(refresh, 0);
		}
	}

	window.__friendica_unpoly_lifecycle_registry.set(registryKey, null);
	return null;
};

window.onDocumentReady = function (target, initialize) {
	return registerModuleLifecycle(target, initialize, ModuleLifecycleReadyEvent.DOCUMENT);
};

window.onWindowLoad = function (target, initialize) {
	return registerModuleLifecycle(target, initialize, ModuleLifecycleReadyEvent.WINDOW);
};

function resizeIframe(obj) {
	_resizeIframe(obj, 0);
}

function _resizeIframe(obj, desth) {
	var h = obj.style.height;
	var ch = obj.contentWindow.document.body.scrollHeight;

	if (h == (ch + 'px')) {
		return;
	}
	if (desth == ch && ch > 0) {
		obj.style.height  = ch + 'px';
	}
	setTimeout(_resizeIframe, 100, obj, ch);
}

function initWidget(name) {
	const widget = document.getElementById(name)
	const list = widget.querySelector(".sidebar-widget-list");
	const btn = widget.querySelector(".widget-btn");
	if (!btn || !list){ return; } // there are contexts in which not btn will be found
	if (localStorage.getItem(window.location.pathname.split("/")[1] + ":" + name) != "block") {
		list.style.display = "none";
		btn.setAttribute("aria-expanded", false)
	} else {
		list.style.display = "block";
		btn.setAttribute("aria-expanded", true)
	}
}

/* Consider switching to Bootstrap collapses or native "details" element to handle showing/hiding */
function openCloseWidget(name) {
	widget = document.getElementById(name)
	const list = widget.getElementsByClassName("sidebar-widget-list")[0];
	const btn = event.currentTarget

	btn.ariaExpanded = btn.ariaExpanded !== 'true';

	if (window.getComputedStyle(list).display === "block") {
		list.style.display = "none";
		localStorage.setItem(window.location.pathname.split("/")[1] + ":" + name, "none");
	} else {
		list.style.display = "block";
		localStorage.setItem(window.location.pathname.split("/")[1] + ":" + name, "block");
	}
}

function openClose(theID) {
	var el = document.getElementById(theID);
	if (el) {
		if (window.getComputedStyle(el).display === "none") {
			openMenu(theID);
		} else {
			closeMenu(theID);
		}
	}
}

function openMenu(theID) {
	var el = document.getElementById(theID);
	if (el) {
		if (!el.dataset.display) {
			el.dataset.display = 'block';
		}
		el.style.display = el.dataset.display;
	}
}

function closeMenu(theID) {
	var el = document.getElementById(theID);
	if (el) {
		el.dataset.display = window.getComputedStyle(el).display;
		el.style.display = "none";
	}
}

function decodeHtml(html) {
	var txt = document.createElement("textarea");

	txt.innerHTML = html;
	return txt.value;
}

/**
 * Retrieves a single named query string parameter
 *
 * @param {string} name
 * @returns {string}
 * @see https://davidwalsh.name/query-string-javascript
 */
function getUrlParameter(name) {
	name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
	var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
	var results = regex.exec(location.search);
	return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};

var src = null;
var prev = null;
var livetime = null;
var force_update = false;
var update_item = 0;
var stopped = false;
var totStopped = false;
var timer = null;
var pr = 0;
var liking = 0;
var in_progress = false;
var commentBusy = false;
var last_popup_menu = null;
var last_popup_button = null;
var lockLoadContent = false;
var originalTitle = document.title;

// Update original title on SPA navigation to prevent ping handler from resetting to stale title
if (window.addEventListener) {
	window.addEventListener('spa:navigate', function () {
		originalTitle = document.title;
	});
}

// Scroll to item deduplication flags
var scrollToItemInProgress = false;
var lastScrollToItemId = null;

const urlRegex = /^(?:https?:\/\/|\s)[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})(?:\/+[a-z0-9_.:;-]*)*(?:\?[&%|+a-z0-9_=,.:;-]*)?(?:[&%|+&a-z0-9_=,:;.-]*)(?:[!#\/&%|+a-z0-9_=,:;.-]*)}*$/i;

window.onDocumentReady('body', function() {
	$.ajaxSetup({cache: false});

	/* setup comment textarea buttons */
	/* comment textarea buttons needs some "data-*" attributes to work:
	 * 		data-role="insert-formatting" : to mark the element as a formatting button
	 * 		data-bbcode="<string>" : name of the bbcode element to insert. insertFormatting() will insert it as "[name][/name]"
	 * 		data-id="<string>" : id of the comment, used to find other comment-related element, like the textarea
	 * */
	$('body').off('click.friendica-main', '[data-role="insert-formatting"]').on('click.friendica-main', '[data-role="insert-formatting"]', function(e) {
		e.preventDefault();
		var o = $(this);
		var bbcode = o.data('bbcode');
		var id = o.data('id');
		if (bbcode == "img") {
			Dialog.doImageBrowser("comment", id);
			return;
		}

		if (bbcode == "imgprv") {
			bbcode = "img";
		}

		insertFormatting(bbcode, id);
	});

	/* event from comment textarea button popups */
	/* insert returned bbcode at cursor position or replace selected text */
	$('body').off('fbrowser.photo.comment').on('fbrowser.photo.comment', function(e, filename, bbcode, id) {
		$.colorbox.close();
		// Support both receiving an ID postfix appended to comment-edit-id or the full ID
		var textarea = document.getElementById('comment-edit-text-' + id) || document.getElementById(id);
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		textarea.value = textarea.value.substring(0, start) + bbcode + textarea.value.substring(end, textarea.value.length);
		$(textarea).trigger('change');
	});

	$(".comment-edit-wrapper textarea, .wall-item-comment-wrapper textarea")
		.editor_autocomplete(baseurl + '/search/acl')
		.bbco_autocomplete('bbcode');

	// Ensures asynchronously-added comment forms recognize mentions, tags and BBCodes as well
	if (window.__friendica_postprocess_liveupdate_autocomplete) {
		document.removeEventListener("postprocess_liveupdate", window.__friendica_postprocess_liveupdate_autocomplete);
	}
	window.__friendica_postprocess_liveupdate_autocomplete = function() {
		$(".comment-edit-wrapper textarea, .wall-item-comment-wrapper textarea")
			.editor_autocomplete(baseurl + '/search/acl')
			.bbco_autocomplete('bbcode');
	};
	document.addEventListener("postprocess_liveupdate", window.__friendica_postprocess_liveupdate_autocomplete);

	/* popup menus */
	function close_last_popup_menu() {
		if (last_popup_menu) {
			last_popup_menu.hide();
			last_popup_menu.off('click', function(e) {e.stopPropagation()});
			last_popup_button.removeClass("selected");
			last_popup_menu = null;
			last_popup_button = null;
		}
	}
	$('a[rel^="#"]').off('click.friendica-main').on('click.friendica-main', function(e) {
		e.preventDefault();
		var parent = $(this).parent();
		var isSelected = (last_popup_button && parent.attr('id') == last_popup_button.attr('id'));
		close_last_popup_menu();
		if (isSelected) {
			return false;
		}
		menu = $($(this).attr('rel'));
		e.preventDefault();
		e.stopPropagation();
		if (menu.attr('popup') == "false") {
			return false;
		}
		parent.toggleClass("selected");
		menu.toggle();
		if (menu.css("display") == "none") {
			last_popup_menu = null;
			last_popup_button = null;
		} else {
			last_popup_menu = menu;
			last_popup_menu.on('click', function(e) {e.stopPropagation()});
			last_popup_button = parent;
			$('#nav-notifications-menu').perfectScrollbar('update');
		}
		return false;
	});
	$('html').off('click.friendica-main').on('click.friendica-main', function() {
		close_last_popup_menu();
	});

	// Colorbox - related docs: https://www.jacklmoore.com/colorbox/
	/* Not used by frio. Used in two photos templates */
	$("a.popupbox").colorbox({
		'inline' : true,
		'transition' : 'elastic',
		'maxWidth' : '100%'
	});

	/* Ensure loading is visible when notifications menu is opened (if no notifications loaded yet)*/
	$('#nav-notifications-linkmenu, #nav-notifications-menu-btn').on('click', function() {
		if ($("#nav-notifications-loading").length && $("#nav-notifications-empty").length) {
			// Only show loading if we haven't loaded notifications yet
			var menu = $("#nav-notifications-menu");
			var hasNotifications = menu.find('.notif-item, li').not('#nav-notifications-loading, #nav-notifications-empty').length > 0;
			var hasContent = menu.html().indexOf('nav-notifications-see-all') > -1;
			if (!hasNotifications && !hasContent) {
				$("#nav-notifications-loading").show();
				$("#nav-notifications-empty").hide();
			}
		}
	});

	/* enable perfect-scrollbars for different elements */
	$('#nav-notifications-menu, aside').perfectScrollbar();

	/* nav update event  */
	$('#topbar-first').bind('nav-update', function(e, data) {
		var invalid = data.invalid || 0;
		if (invalid == 1) {
			window.location.href=window.location.href
		}

		let tabNotifications = data.mail + data.notification;
		if (tabNotifications > 0) {
			document.title = '(' + tabNotifications + ') ' + originalTitle;
		} else {
			document.title = originalTitle;
		}

		['home', 'intro', 'mail', 'events', 'birthdays', 'notification'].forEach(function(type) {
			var number = data[type];
			updateCounter(type, number);
		});

		var intro = data['intro'];
		if (intro == 0) {
			intro = ''; $('#intro-update-li').removeClass('show')
		} else {
			$('#intro-update-li').addClass('show')
		}

		$('#intro-update-li').html(intro);

		var mail = data['mail'];
		if (mail == 0) {
			mail = ''; $('#mail-update-li').removeClass('show')
		} else {
			$('#mail-update-li').addClass('show')
		}

		$('#mail-update-li').html(mail);

		$(".sidebar-circle-li .notify").removeClass("show");
		$(data.circles).each(function(key, circle) {
			var gid = circle.id;
			var gcount = circle.count;
			$(".circle-"+gid+" .notify").addClass("show").text(gcount);
		});

		$(".group-widget-entry .notify").removeClass("show");
		$(data.groups).each(function(key, group) {
			var fid = group.id;
			var fcount = group.count;
			$(".group-"+fid+" .notify").addClass("show").text(fcount);
		});

		// Hide loading state when we receive notification data
		$("#nav-notifications-loading").hide();

		var navNotifications = Array.isArray(data.notifications) ? data.notifications : [];
		if (navNotifications.length == 0) {
			$("#nav-notifications-empty").show();
		} else {
			$("#nav-notifications-empty").hide();
			var nnm = $("#nav-notifications-menu");
			// Preserve control/state elements from the current DOM to avoid stale template snapshots
			var seeAllElement = nnm.find("#nav-notifications-see-all");
			var markAllElement = nnm.find("#nav-notifications-mark-all");
			var loadingElement = nnm.find("#nav-notifications-loading");
			var emptyElement = nnm.find("#nav-notifications-empty");

			nnm.empty();
			if (seeAllElement.length > 0) {
				nnm.append(seeAllElement);
			}
			if (markAllElement.length > 0) {
				nnm.append(markAllElement);
			}

			// Re-add loading and empty state elements if they existed
			if (loadingElement.length > 0) {
				nnm.append(loadingElement);
			}
			if (emptyElement.length > 0) {
				nnm.append(emptyElement);
			}

			var lastItemStorageKey = "notification-lastitem:" + localUser;
			var notification_lastitem = parseInt(localStorage.getItem(lastItemStorageKey));
			var notification_id = 0;

			// Insert notifs into the notifications-menu
			$(navNotifications).each(function(key, navNotif) {
				nnm.append(navNotif.html);
			});

			// Desktop Notifications
			$(navNotifications.slice().reverse()).each(function(key, navNotif) {
				notification_id = parseInt(navNotif.timestamp);
				if (notification_lastitem !== null && notification_id > notification_lastitem && Number(navNotif.seen) === 0) {
					if (getNotificationPermission() === "granted") {
						var notification = new Notification(document.title, {
							body: decodeHtml(navNotif.plaintext),
							icon: navNotif.contact.photo,
						});
						notification['url'] = navNotif.href;
						notification.addEventListener("click", function(ev) {
							window.location = ev.target.url;
						});
					}
				}

			});
			notification_lastitem = notification_id;
			localStorage.setItem(lastItemStorageKey, notification_lastitem)

			$("img[data-src]", nnm).each(function(i, el) {
				// Add src attribute for images with a data-src attribute
				// However, don't bother if the data-src attribute is empty, because
				// an empty "src" tag for an image will cause some browsers
				// to prefetch the root page of the Friendica hub, which will
				// unnecessarily load an entire profile/ or network/ page
				if ($(el).data("src") != '') {
					$(el).attr('src', $(el).data("src"));
				}
			});
		}

		var notif = data['notification'];
		if (notif > 0) {
			$("#nav-notifications-linkmenu").addClass("on");
		} else {
			$("#nav-notifications-linkmenu").removeClass("on");
		}

		$(data.sysmsgs.notice).each(function(key, message) {
			$.jGrowl(message, {sticky: true, theme: 'notice'});
		});
		$(data.sysmsgs.info).each(function(key, message) {
			$.jGrowl(message, {sticky: false, theme: 'info', life: 5000});
		});

		// Update the js scrollbars
		$('#nav-notifications-menu').perfectScrollbar('update');
	});

	// Asynchronous calls are deferred until the very end of the page load to ease on slower connections
	// Only register once, not on every SPA navigation
	if (typeof window.__friendica_main_load_handler === 'undefined') {
		window.__friendica_main_load_handler = true;
		window.addEventListener("load", function(){
			NavUpdate();
			if (typeof acl !== 'undefined') {
				acl.get(0, 100);
			}
		});
	}

	// Allow folks to stop the ajax page updates with the pause/break key
	$(document).off('keydown.friendica-main-pause').on('keydown.friendica-main-pause', function(event) {
		// Pause/Break or Ctrl + Space
		if (event.which === 19 || (!event.metaKey && !event.shiftKey && !event.altKey && event.ctrlKey && event.which === 32)) {
			event.preventDefault();
			if (stopped === false) {
				stopped = true;
				if (event.ctrlKey) {
					totStopped = true;
				}
				$('#pause').html('<img src="images/pause.gif" alt="pause" style="border: 1px solid black;" />');
			} else {
				unpause();
			}
		} else if (!totStopped) {
			unpause();
		}
	});

	// Scroll to the next/previous thread when pressing J and K
	$(document).off('keydown.friendica-main-nav').on('keydown.friendica-main-nav', function (event) {
		var threads = $('.thread_level_1');
		if ((event.keyCode === 74 || event.keyCode === 75) && !$(event.target).is('textarea, input')) {
			var scrollTop = $(window).scrollTop();
			if (event.keyCode === 75) {
				threads = $(threads.get().reverse());
			}
			threads.each(function(key, item) {
				var comparison;
				var top = $(item).offset().top - 100;
				if (event.keyCode === 74) {
					comparison = top > scrollTop + 1;
				} else if (event.keyCode === 75) {
					comparison = top < scrollTop - 1;
				}
				if (comparison) {
					$('html, body').animate({scrollTop: top}, 200);
					return false;
				}
			});
		}
	});

	// Initialize infinite scroll on first page load
	if (typeof initInfiniteScroll === 'function') {
		initInfiniteScroll();
	}
});

// Function to initialize infinite scroll - can be called multiple times
function initInfiniteScroll() {
	
	// Only initialize if infinite_scroll is defined
	if (typeof infinite_scroll !== 'undefined') {
		// Remove any existing scroll handler to prevent duplicates
		$(window).off('scroll.infinite');
		
		$(window).on('scroll.infinite', function(e) {
			if ($(document).height() != $(window).height()) {
				// First method that is expected to work - but has problems with Chrome
				if ($(window).scrollTop() > ($(document).height() - $(window).height() * 1.5))
					loadScrollContent();
			} else {
				// This method works with Chrome - but seems to be much slower in Firefox
				if ($(window).scrollTop() > (($("section").height() + $("header").height() + $("footer").height()) - $(window).height() * 1.5)) {
					loadScrollContent();
				}
			}
		});
	}
}

// Register event listener for SPA navigation - do this immediately
if (window.addEventListener) {
	window.addEventListener('spa:initInfiniteScroll', initInfiniteScroll);
}

/**
 * Inserts a BBCode tag in the comment textarea identified by id
 *
 * @param {string} BBCode
 * @param {int} id
 * @returns {boolean}
 */
function insertFormatting(BBCode, id) {

	// Support both receiving an ID postfix appended to comment-edit-id or the full ID
	let textarea = document.getElementById('comment-edit-text-' + id) || document.getElementById(id);

	if (textarea.value === '') {
		$(textarea)
			.addClass("comment-edit-text-full")
			.removeClass("comment-edit-text-empty");
		closeMenu("comment-fake-form-" + id);
		openMenu("item-comments-" + id);
	}

	insertBBCodeInTextarea(BBCode, textarea);

	return true;
}

/**
 * Inserts a BBCode tag in the provided textarea element, wrapping the currently selected text.
 * For URL BBCode, it discriminates between link text and non-link text to determine where to insert the selected text.
 *
 * @param {string} BBCode
 * @param {HTMLTextAreaElement} textarea
 */
function insertBBCodeInTextarea(BBCode, textarea) {
	let selectionStart = textarea.selectionStart;
	let selectionEnd = textarea.selectionEnd;
	let selectedText = textarea.value.substring(selectionStart, selectionEnd);
	let openingTag = '[' + BBCode + ']';
	let closingTag = '[/' + BBCode + ']';
	let cursorPosition = selectionStart + openingTag.length + selectedText.length;

	if (BBCode === 'url') {
		if (urlRegex.test(selectedText)) {
			openingTag = '[' + BBCode + '=' + selectedText + ']';
			selectedText = '';
			cursorPosition = selectionStart + openingTag.length;
		} else {
			openingTag = '[' + BBCode + '=]';
			cursorPosition = selectionStart + openingTag.length - 1;
		}
	}

	textarea.value = textarea.value.substring(0, selectionStart) + openingTag + selectedText + closingTag + textarea.value.substring(selectionEnd, textarea.value.length);
	textarea.setSelectionRange(cursorPosition, cursorPosition);
	textarea.dispatchEvent(new Event('change'));
	textarea.focus();
}

function triggerLiveUpdates(force, guid) {
	if (force) {
		showProcessing();
	}
	force_update = force;
	['network', 'profile', 'channel', 'community', 'notes', 'display', 'contact'].forEach(function (src) {
		if ($('#live-' + src).length && (force || (updateContent && src !== 'display'))) {
			liveUpdate(src, force, guid);
		}
	});
	if (force) {
		hideLoading();
	}
}

/**
 * Scroll the screen to the item element whose id is provided, then highlights it
 *
 * Note: jquery.color.js is required
 *
 * @param {string} elementId The item element id
 * @returns {undefined}
 */
function scrollToItem(elementId) {
	if (typeof elementId === "undefined") {
		return false;
	}
	
	// Prevent multiple calls for the same element
	if (scrollToItemInProgress && lastScrollToItemId === elementId) {
		return false;
	}
	
	var $el = $("#" + elementId + " > .media");
	// Test if the Item exists
	if (!$el.length) {
		return false;
	}

	// Set tracking flags
	scrollToItemInProgress = true;
	lastScrollToItemId = elementId;

	// Define the colors which are used for highlighting
	var colWhite = { backgroundColor: "#7f7f7f" };
	var colShiny = { backgroundColor: "#7e763a" };

	// Get the Item Position (we need to substract 100 to match correct position
	var itemPos = $el.offset().top - 100;

	// Scroll to the DIV with the ID (GUID)
	$("html, body")
		.animate(
			{
				scrollTop: itemPos,
			},
			400,
		)
		.promise()
		.done(function () {
			// Highlight post/comment with ID  (GUID)
			$el.animate(colWhite, 1000).animate(colShiny).animate({ backgroundColor: "transparent" }, 600);
			
			// Reset flags after animation completes
			setTimeout(function() {
				scrollToItemInProgress = false;
				lastScrollToItemId = null;
			}, 2000); // Match animation duration (1000+600ms)
			return true;
		});
}

function NavUpdate() {
	if (!stopped) {
		if (force_update) {
			showFetching();
		}
		var pingCmd = 'ping';
		$.get(pingCmd)
			.done(function(data) {
				if (data.result) {
					if (force_update) {
						showProcessing();
					}
					// send nav-update event
					$('#topbar-first').trigger('nav-update', data.result);

					if (force_update) {
						hideLoading();
					}

					// start live update
					triggerLiveUpdates(force_update);

					if ($('#live-network').length && !$('#live-display').length) {
						networkUpdate(force_update);
					} else if (!$('#live-display').length) {
						var update_url = 'ping_network?ping=1';
						if (force_update) {
							showFetching();
						}
						$.get(update_url)
							.done(function(net) {
								if (force_update) {
									showProcessing();
								}
								updateCounter('net', net);
							})
							.always(function() {
								if (force_update) {
									hideLoading();
								}
							});
					}

					if ($('#live-photos').length) {
						if (liking) {
							liking = 0;
							window.location.href = window.location.href;
						}
					}
				}
			})
			.fail(function() {
				if (force_update) {
					hideLoading();
				}
			});
	}
	timer = setTimeout(NavUpdate, 30000);
}

function updateConvItems(data, guid) {
	// add a new thread
	$('.toplevel_item',data).each(function() {
		var ident = $(this).attr('id');

		// Add new top-level item.
		if ($('#' + ident).length === 0
			&& (!getUrlParameter('page')
				&& !getUrlParameter('max_id')
				&& !getUrlParameter('min_id')
				|| getUrlParameter('page') === '1'
			)
		) {
			$('#' + prev).after($(this));

		// Replace already existing thread.
		} else {
			// Find out if the hidden comments are open, so we can keep it that way
			// if a new comment has been posted
			var id = $('.hide-comments-total', this).attr('id');
			if (typeof id != 'undefined') {
				id = id.split('-')[3];
				var commentsOpen = $("#collapsed-comments-" + id).is(":visible");
			}

			$('#' + ident).replaceWith($(this));

			if (typeof id != 'undefined') {
				if (commentsOpen) {
					showHideComments(id);
				}
			}
		}
		prev = ident;
	});

	$('.like-rotator').hide();
	if (commentBusy) {
		commentBusy = false;
		$('body').css('cursor', 'auto');
	}
}

function getUpdateUrl(src)
{
	let force = force_update || $(document).scrollTop() === 0;

	var udargs = ((netargs.length) ? '/' + netargs : '');

	var update_url = src + udargs + '&p=' + profile_uid + '&force=' + (force ? 1 : 0) + '&item=' + update_item;

	if (getUrlParameter('page')) {
		update_url += '&page=' + getUrlParameter('page');
	}
	if (getUrlParameter('min_id')) {
		update_url += '&min_id=' + getUrlParameter('min_id');
	}
	if (getUrlParameter('max_id')) {
		update_url += '&max_id=' + getUrlParameter('max_id');
	}

	match = $("span.received").first();
	if (match.length > 0) {
		update_url += '&first_received=' + match[0].innerHTML;
	}

	match = $("span.created").first();
	if (match.length > 0) {
		update_url += '&first_created=' + match[0].innerHTML;
	}

	match = $("span.commented").first();
	if (match.length > 0) {
		update_url += '&first_commented=' + match[0].innerHTML;
	}

	match = $("span.uriid").first();
	if (match.length > 0) {
		update_url += '&first_uriid=' + match[0].innerHTML;
	}
	return update_url;
}

function liveUpdate(src, force, guid) {
	if ((src == null) || stopped || !profile_uid) {
		$('.like-rotator').hide(); return;
	}

	if (($('.comment-edit-text-full').length) || in_progress) {
		if (livetime) {
			clearTimeout(livetime);
		}
		livetime = setTimeout(function() {liveUpdate(src, force, guid)}, 5000);
		return;
	}

	if (livetime != null) {
		livetime = null;
	}
	prev = 'live-' + src;

	in_progress = true;

	var orgHeight = $("section").height();

	var update_url = getUpdateUrl(src);

	if (force_update) {
		force_update = false;
	}

	showFetching();
	$.get('update_' + update_url)
		.done(function(data) {
			showProcessing();
			in_progress = false;
			update_item = 0;

			if ($('.wall-item-body', data).length == 0) {
				return;
			}

			$('.wall-item-body', data).imagesLoaded(function() {
				updateConvItems(data, guid);

				document.dispatchEvent(new Event('postprocess_liveupdate'));

				// Update the scroll position.
				if (guid) {
					scrollToItem("item-" + guid);
				}
			})
		})
		.always(function() {
			in_progress = false;
			hideLoading();
		});
}

function networkUpdate(force) {
	if (force) {
		showFetching();
	}
	$.get('ping_' + getUpdateUrl('network'))
		.done(function(net) {
			if (force) {
				showProcessing();
			}
			updateCounter('net', net);
		})
		.always(function() {
			if (force) {
				hideLoading();
			}
		});
}

function updateCounter(type, counter) {
	if (counter < 0) {
		return;
	}

	if (counter == 0) {
		counter = '';
		$('#' + type + '-update').removeClass('show');
	} else {
		$('#' + type + '-update').addClass('show');
	}
	$('#' + type + '-update').text(counter);
}

function updateItem(itemNo, guid) {
	update_item = itemNo;
	triggerLiveUpdates(true, guid);
}

function imgbright(node) {
	$(node).removeClass("drophide").addClass("drop");
}

function imgdull(node) {
	$(node).removeClass("drop").addClass("drophide");
}

// Since our ajax calls are asynchronous, we will give a few
// seconds for the first ajax call (setting like/dislike), then
// run the updater to pick up any changes and display on the page.
// The updater will turn any rotators off when it's done.
// This function will have returned long before any of these
// events have completed and therefore there won't be any
// visible feedback that anything changed without all this
// trickery. This still could cause confusion if the "like" ajax call
// is delayed and NavUpdate runs before it completes.

/**
 * @param {int}     ident The id of the relevant item
 * @param {string}  verb  The verb of the action
 * @param {boolean} un    Whether to perform an activity removal instead of creation
 */
function doActivityItem(ident, verb, un) {
	unpause();
	$('#like-rotator-' + ident.toString()).show();
	showPosting();
	verb = un ? 'un' + verb : verb;
	$.post('item/' + ident.toString() + '/activity/' + verb)
		.done(function() { 
			showProcessing();
			updateItem(ident.toString()); 
		})
		.always(function() { hideLoading(); });
	liking = 1;
}

function doFollowThread(ident) {
	unpause();
	$('#like-rotator-' + ident.toString()).show();
	showPosting();
	$.post('item/' + ident.toString() + '/follow')
		.done(function() { 
			showProcessing();
			updateItem(ident.toString()); 
		})
		.always(function() { hideLoading(); });
	liking = 1;
}

function doCompleteThread(ident) {
	unpause();
	$('#like-rotator-' + ident.toString()).show();
	showPosting();
	$.post('item/' + ident.toString() + '/complete')
		.done(function() { 
			showProcessing();
			updateItem(ident.toString()); 
		})
		.always(function() { hideLoading(); });
	liking = 1;
}

function doStar(ident) {
	ident = ident.toString();
	$('#like-rotator-' + ident).show();
	showPosting();
	$.post('item/' + ident + '/star')
		.done(function(data) {
		showProcessing();
		if (data.state === 1) {
			$('#starred-' + ident)
				.addClass('starred')
				.removeClass('unstarred');
			$('#star-' + ident).addClass('hidden');
			$('#unstar-' + ident).removeClass('hidden');
		} else {
			$('#starred-' + ident)
				.addClass('unstarred')
				.removeClass('starred');
			$('#star-' + ident).removeClass('hidden');
			$('#unstar-' + ident).addClass('hidden');
		}
	})
	.always(function () {
		$('#like-rotator-' + ident).hide();
		hideLoading();
	});
}

function doPin(ident) {
	ident = ident.toString();
	$('#like-rotator-' + ident).show();
	showPosting();
	$.post('item/' + ident + '/pin')
		.done(function(data) {
		if (data.state === 1) {
			$('#pinned-' + ident)
				.addClass('pinned')
				.removeClass('unpinned');
			$('#pin-' + ident).addClass('hidden');
			$('#unpin-' + ident).removeClass('hidden');
		} else {
			$('#pinned-' + ident)
				.addClass('unpinned')
				.removeClass('pinned');
			$('#pin-' + ident).removeClass('hidden');
			$('#unpin-' + ident).addClass('hidden');
		}
	})
	.always(function () {
		$('#like-rotator-' + ident).hide();
		hideLoading();
	});
}

function doIgnoreThread(ident) {
	ident = ident.toString();
	$('#like-rotator-' + ident).show();
	showPosting();
	$.post('item/' + ident + '/ignore')
		.done(function(data) {
			if (data.state === 1) {
				$('#ignored-' + ident)
					.addClass('ignored')
					.removeClass('unignored');
				$('#ignore-' + ident).addClass('hidden');
				$('#unignore-' + ident).removeClass('hidden');
			} else {
				$('#ignored-' + ident)
					.addClass('unignored')
					.removeClass('ignored');
				$('#ignore-' + ident).removeClass('hidden');
				$('#unignore-' + ident).addClass('hidden');
			}
			$('#like-rotator-' + ident).hide();
		})
		.always(function() {
			hideLoading();
		});
}

function getPosition(e) {
	var cursor = {x:0, y:0};

	if (e.pageX || e.pageY) {
		cursor.x = e.pageX;
		cursor.y = e.pageY;
	} else {
		if (e.clientX || e.clientY) {
			cursor.x = e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft) - document.documentElement.clientLeft;
			cursor.y = e.clientY + (document.documentElement.scrollTop  || document.body.scrollTop)  - document.documentElement.clientTop;
		} else if (e.x || e.y) {
			cursor.x = e.x;
			cursor.y = e.y;
		}
	}
	return cursor;
}

function displaySearchText(id) {
	showFetching();
	$.get('item/' + id + '/searchtext')
		.done(function(data) {
			hideLoading();
			alert(data);
		})
		.fail(function() {
			hideLoading();
		});
}

function displayLanguage(id) {
	showFetching();
	$.get('item/' + id + '/language')
		.done(function(data) {
			hideLoading();
			alert(data);
		})
		.fail(function() {
			hideLoading();
		});
}

var lockvisible = false;

function lockview(event, type, id) {
	event = event || window.event;
	cursor = getPosition(event);
	if (lockvisible) {
		lockvisible = false;
		$('#panel').hide();
	} else {
		lockvisible = true;
		showFetching();
		$.get('permission/tooltip/' + type + '/' + id)
			.done(function(data) {
				showProcessing();
				$('#panel')
					.html(data)
					.css({'left': cursor.x + 5 , 'top': cursor.y + 5})
					.show();
			})
			.always(function() {
				hideLoading();
			});
	}
}

function post_comment(id) {
	
	if (commentBusy) {
		return false;
	}
	
	unpause();
	commentBusy = true;
	showPosting();
	$('body').css('cursor', 'wait');
	$.post(
		"item",
		$("#comment-edit-form-" + id).serialize()
	)
		.done(function(data) {
			showProcessing();
			if (data.success) {
				$("#comment-edit-wrapper-" + id).hide();
				$("#comment-edit-text-" + id).val('');
				var textarea = document.getElementById("comment-edit-text-" + id);
				if (textarea) {
					commentClose(textarea,id);
				}
				if (timer) {
					clearTimeout(timer);
				}
				updateItem(id, data.guid ?? null);
			}
			if (data.reload) {
				window.location.href=data.reload;
			}
		})
		.always(function() {
		hideLoading();
		commentBusy = false;
		$('body').css('cursor', 'auto');
	});
	return false;
}

function preview_comment(id) {
	showPosting();
	$("#comment-edit-preview-" + id).show();
	$.post(
		"item",
		$("#comment-edit-form-" + id).serialize() + '&preview=1'
	)
		.done(function(data) {
			showProcessing();
			if (data.preview) {
				$("#comment-edit-preview-" + id).html(data.preview);
				$("#comment-edit-preview-" + id + " a").click(function() {return false;});
			}
		})
		.always(function() {
		hideLoading();
	});
	fix_preview_img_wrap(id);
	return true;
}

function showHideComments(id) {
	if ($('#collapsed-comments-' + id).is(':visible')) {
		$('#collapsed-comments-' + id).slideUp();
		$('#hide-comments-' + id).hide();
		$('#hide-comments-total-' + id).show();
	} else {
		$('#collapsed-comments-' + id).slideDown();
		$('#hide-comments-' + id).show();
		$('#hide-comments-total-' + id).hide();
	}
}

// Load more comments for a specific post
function loadMoreComments(uriId, itemId, existing) {
	var button = $('#load-more-comments-' + itemId);
	var loadingText = $('#load-more-loading-' + itemId);
	
	if (button.hasClass('loading') || commentBusy) {
		return;
	}
	
	// Hide button, show loading text (which contains the rotator)
	button.addClass('loading').prop('disabled', true).hide();
	loadingText.show();
	commentBusy = true;
	showFetching();
	
	// Parse existing JSON string if it's a string, or use as-is if already an array
	var existingArray = typeof existing === 'string' ? JSON.parse(existing) : existing;
	
	$.get({
		url: 'item/' + uriId + '/comments',
		data: {
			'mode': 'raw',
			'existing': existingArray.join(',')
		}
	})
	.done(function(data) {
		loadingText.hide();
		showProcessing();
		if ($(data).length > 0) {
			var $data = $(data);
			// Find all elements with id starting with "item-comments-" or "item-"
			var allItems = $data.find('[id^="item-comments-"], [id^="item-"]').addBack('[id^="item-comments-"], [id^="item-"]');
			
			// Filter to only keep items that don't already exist on the page
			var newItems = allItems.filter(function() {
				var id = $(this).attr('id');
				return id && $('#' + id).length === 0;
			});
			
			if (newItems.length > 0) {
				// Replace the button with the new comments
				button.replaceWith(newItems);
			} else {
				// No new comments to add
				button.hide();
			}
		} else {
			// No more comments to load
			button.hide();
		}
		hideLoading();
	})
	.fail(function() {
		// Show error feedback
		hideLoading();
		button.removeClass('loading').prop('disabled', false).show();
		loadingText.hide();
	})
	.always(function() {
		commentBusy = false;
	});
}

function preview_post() {
	showPosting();
	$("#jot-preview-content").show();
	$.post(
		"item",
		$("#profile-jot-form").serialize() + '&preview=1'
	)
		.done(function(data) {
			showProcessing();
			if (data.preview) {
				$("#jot-preview-content").html(data.preview);
				$("#jot-preview-content" + " a").click(function() {return false;});
				document.dispatchEvent(new Event('postprocess_liveupdate'));
			}
		})
		.always(function() {
		hideLoading();
	});
	fix_preview_img_wrap();
	return true;
}
function fix_preview_img_wrap(index){
	/* We don't know how long it will take the server to do the ajax request
	   so we need to monitor the preview pane for a DOM mutation.
	   
	   We also do not want to attach the mutation observer unless previewing
	   and we do not need this on pages without the jot composer. This might
	   be in feed, in modal, or on a separate page so check for parent obj.	   
	   
	   We only want ot use a Mutation Observer if the browser supports it
	   (and most do) but just in case there is a fallback to a timeOut method.
	*/

	if ("MutationObserver" in window){
		const targetElement = (!index && $('#jot-preview-content .tread-wrapper')) ? $('#jot-preview-content .tread-wrapper') : $('#comment-edit-preview-'+index+' .tread-wrapper');
		const parentToWatch = (!index && document.getElementById('jot-preview-content')) ? document.getElementById('jot-preview-content') : document.getElementById('comment-edit-preview-'+index);	/* jQuery obj will not work! */
		
		const observer = new MutationObserver((mutationList, observer) => {
			for (const mutation of mutationList) {
				if (mutation.type === "childList") {
					const newElement = targetElement;
					if (newElement){
						preview_post_img(index);
						observer.disconnect();
						break;
					}
				}
			}
		});
		// attach the mutation observer IF we have a valid parent obj
		if (parentToWatch){
			observer.observe(parentToWatch, { childList: true, subtree: true });
		}
	} else {
		// fallback if MutationObserver is not available
		setTimeout(function(){preview_post_img(index);},3000);
	}
}


function masonry_or_not(parentElement){
	/* This convoluted function grabs the preview contents as rendered by Friendica
	   and then tries to determine if it is one of the scenarios in which masonry
	   layout will not be shown and returns boolean true|false to the caller.
	   
	   Normally the masonry layout is only shown if:
	   * There are multiple images in the post
	   * The images are consecutive
	   * There are no other elements in between them.
	   * There are no raw text nodes immediately before or after any of them.
	   * There is no paragraph after the last one (a DIV is okay though)
	   
	   This function assumes a masonry layout could be shown, unless it should find
	   one of the above conditions is not met.
	*/

	var gallery = true;
	$(parentElement+" .wall-item-body").find('img.empty-description, img.has-alt-description').each(function(index, element){
			if ( $(element).parent('a').length > 0 ){
				// check if img has other stuff in with it
				$(element).parent().parent().contents().filter(function(){
					if (this.nodeType === 3){
						// there is a text node in here with it, this is NOT a gallery
						gallery = false;
						return;
					}
				});
				// if this element has a next sibling and it has an image in it
				if ( $(element).parent().parent().next().length > 0 ){
					if ( $(element).parent().parent().next().prop('tagName') === 'P' ){
						// has to check <p> so it does not confuse with link preview <div>
						if ( $(element).parent().parent().next('p:has(img)').length > 0 ){
							// next sibling contains image so this is a gallery
						} else {
							// next sibling does NOT contain an image, this is not a gallery
							gallery = false;
							return;
						}
					} else {
						// next sibling is some other kind of element
					}
				} else {
					// there is no next sibling
				}
					
			} else if( $(element).parent('p').length > 0 ){
				// check if img has other stuff in with it
				$(element).parent().contents().filter(function(){
					if (this.nodeType === 3){
						// there is a text node in it, this is NOT a gallery
						gallery = false;
						return;
					}
				});	
				// if this element has a next sibling and it has an image in it
				if ( $(element).parent().next().length > 0 ){
					// there IS a next sibling
					if( $(element).parent().next().prop('tagName') === 'P' ){
						if ( $(element).parent().next('p:has(img)').length > 0 ){
							// next sibling contains an image
						} else {
							// next sibling does NOT contain an image, NOT a gallery
							gallery = false;
							return;
						}
					} else {
						// next sibling is some other kind of element
					}
				} else {
					// there is no next sibling
				}			
			} else {
				// no clue what element this is inside of so NOT a gallery
				gallery = false;
				return;
			}
		});
		return gallery;
}


function preview_post_img(index){
	var parentElement;
	if (!index){
		index = 0;
		parentElement = '#jot-preview-content';
	} else {
		parentElement = '#comment-edit-preview-'+index;
	}
	var $images = $(parentElement+" img.empty-description, "+parentElement+" img.has-alt-description");
	if ($images.length === 0){
		// no images to process
		return;
	} else {
		// before we manipulate the DOM check if this could use masonry layout
		var gallery = masonry_or_not(parentElement);
		if ($images.length > 1 && ($images.parent().next(':has(img)') || $images.parent().parent('p').next(':has(img)')) ){
			// this appears to be a sequence of images
		} else {
			gallery = false;
		}
		// wrap attached images like they would be in feed
		$images.parent().wrap('<div></div>').wrap('<figure></figure>');
		// get images with alt text
		$(parentElement+" img.has-alt-description").each(function(index, element){
			var $alt_text = $(element).attr("alt");
			$(element).parent().parent().append('<button class="alt-text-button" type="button" aria-hidden="true">ALT<span class="alt-text-block" dir="auto">'+$alt_text+'</span></button>');
		});
		// if we have multiple images do masonry layout
		if ($images.length > 1 && gallery === true){
			// if processing takes too long pulse the container background
			$(parentElement+" .wall-item-body").addClass('img-processing');
			// show process spinner (if there is one)
			$(parentElement+" .wall-item-decor img").show();
			// hide container content during processing...
			$(parentElement+" .wall-item-body").css({visibility: 'hidden'});


			/* only setInterval or setTimeout work to trigger masonry function!
			   jQuery promise().done() or count tracking or MutationObserver
			   all cause infinite loops. The ONLY way to know if the above is
			   done manipulating the DOM is to compare the number of FIGURE
			   tags to the original IMAGE count. If they match, it's done.			
			*/ 
			var condition = setInterval(function(){
				if( $(parentElement+" figure").length === $images.length ){
					clearInterval(condition);
					preview_masonry_rows(index);
				}
			}, 100);
		} else {
			$images.parent().wrap('<div class="body-attach"></div>');
		}
	}
}

function preview_masonry_rows(index) {
	// first determine if there are multiple images (by this point they should all be wrapped in <figure> tags)
	// and we do not want to accidentally scoop up jot and comment ones together
	var parentElement;
	if (!index){
		parentElement = "#jot-preview-content";
	} else {
		parentElement = "#comment-edit-preview-"+index;
	}
	var $images = $(parentElement+" .wall-item-content").find("figure");
	// if called by preview_post_image we should never have zero or one image but catch them anyway...
	if ($images.length === 0){
		$(parentElement+" .wall-item-decor img").hide();
		$(parentElement+" .wall-item-body").removeClass('img-processing');
		$(parentElement+" .wall-item-body").css({visibility: 'visible'});
		return;
	}
	else if ($images.length === 1){ 
		$images.parent().wrap('<div class="body-attach"></div>');
		$(parentElement+" .wall-item-decor img").hide();
		$(parentElement+" .wall-item-body").removeClass('img-processing');
		$(parentElement+" .wall-item-body").css({visibility: 'visible'});
		return; // no need to do masonry layout
	} else { // do masonry layout
		var rows = [];

		var couples = [];
		for(let i=0; i < $images.length; i++){
			let entry;
			if (typeof($images[i+1]) === "undefined"){
				entry = [$images[i]];
			} else {
				entry = [$images[i], $images[i+1]];
			}
			couples.push(entry);
			i++;				// NOT a double increment bug! This pairs images
		}


		for(let c=0; c < couples.length; c++){
			var widths = [];
			var heights = [];
			for(let i=0; i < couples[c].length; i++){
				let this_width;
				let this_height;
				let this_image = $(couples[c][i]).find('img').first();
				if ( $(this_image).width() === 0){
					this_width = 640;
				} else {
					this_width = $(this_image).width();
				}
				if ( $(this_image).height() === 0){
					this_height = 480;
				} else {
					this_height = $(this_image).height();
				}
				widths.push( this_width );
				heights.push( this_height );
			}
			var maxHeight = Math.max(...heights);
			// corrected width preserving aspect ratio when all images on a row are the same height
			var correctedWidths = [];
			for(let w=0; w < widths.length; w++){
				correctedWidths.push( (widths[w] * maxHeight / heights[w]) );
			}
			var totalWidth = 0;
			// total sum of all widths
			for(let t=0; t < correctedWidths.length; t++){
				totalWidth += correctedWidths[t];
			}
			// This magic value will stay constant for each image of any given row and is ultimately
			// used to determine the height of the row container relative to the available width
			var commonHeightRatio = (100 * correctedWidths[0] / totalWidth / (widths[0] / heights[0]));
			
			var first_image = [couples[c][0], (100 * correctedWidths[0]/totalWidth), (100 * maxHeight/correctedWidths[0])];
			
			var second_image;
			if (couples[c].length === 1){ // single image
				second_image = [];
			} else {
				second_image = [couples[c][1], (100 * correctedWidths[1]/totalWidth), (100 * maxHeight/correctedWidths[1])];
			}
			// push them onto a row
			rows.push([widths, heights, maxHeight, totalWidth, commonHeightRatio, first_image, second_image]);
		}
		var $attachbox = $('<div></div>');
			$attachbox.addClass('body-attach');

		for(let r=0; r < rows.length; r++){
			var $newRow = $('<div></div>');
				$newRow.addClass('masonry-row');
				$newRow.css('height', rows[r][4]+'%');
				rows[r][5][0].setAttribute('style','width: '+rows[r][5][1]+'%; padding-bottom: calc('+(rows[r][5][1] * rows[r][5][2] / 100)+'% - 5px / 2)');
				rows[r][5][0].className = "img-allocated-height";
				$newRow.append(rows[r][5][0]);
			if (rows[r][6].length > 0){ // do not assume a matching image
				rows[r][6][0].setAttribute('style','width: '+rows[r][6][1]+'%; padding-bottom: calc('+(rows[r][6][1] * rows[r][6][2] / 100)+'% - 5px / 2)');
				rows[r][6][0].className = "img-allocated-height";
				$newRow.append(rows[r][6][0]);
			}
			$attachbox.append($newRow);
		}
		$(parentElement+" .wall-item-body div:empty").remove();				// clean up now empty divs that used to wrap images
		$(parentElement+" .wall-item-body").append($attachbox);				
		$(parentElement+" .wall-item-decor img").hide();						// hide spinner
		$(parentElement+" .wall-item-body").removeClass('img-processing');
		$(parentElement+" .wall-item-body").css({visibility: 'visible'});	// make container visible
	}
}
function unpause() {
	// unpause auto reloads if they are currently stopped
	totStopped = false;
	stopped = false;
	$('#pause').html('');
}

// load more network content (used for infinite scroll)
function loadScrollContent() {
	if (lockLoadContent) {
		return;
	}
	
	// Guard: Check if scroll-loader element and infinite_scroll are available
	if ($('#scroll-loader').length === 0 || typeof infinite_scroll === 'undefined' || typeof infinite_scroll.reload_uri === 'undefined') {
		lockLoadContent = false;
		return;
	}
	
	lockLoadContent = true;

	$("#scroll-loader").fadeIn('normal');

	match = $("span.received").last();
	if (match.length > 0) {
		received = match[0].innerHTML;
	} else {
		received = "0000-00-00 00:00:00";
	}

	match = $("span.created").last();
	if (match.length > 0) {
		created = match[0].innerHTML;
	} else {
		created = "0000-00-00 00:00:00";
	}

	match = $("span.commented").last();
	if (match.length > 0) {
		commented = match[0].innerHTML;
	} else {
		commented = "0000-00-00 00:00:00";
	}

	match = $("span.uriid").last();
	if (match.length > 0) {
		uriid = match[0].innerHTML;
	} else {
		uriid = "0";
	}

	// get the raw content from the next page and insert this content
	// right before "#conversation-end"
	showFetching();
	$.get({
		url: infinite_scroll.reload_uri,
		data: {
			'mode'          : 'raw',
			'last_received' : received,
			'last_commented': commented,
			'last_created'  : created,
			'last_uriid'    : uriid
		}
	})
	.done(function(data) {
		showProcessing();
		$("#scroll-loader").hide();
		if ($(data).length > 0) {
			$(data).insertBefore('#conversation-end');
		} else {
			$("#scroll-end").fadeIn('normal');
		}

		document.dispatchEvent(new Event('postprocess_liveupdate'));
	})
	.fail(function() {
		$("#scroll-loader").hide();
	})
	.always(function () {
		$("#scroll-loader").hide();
		lockLoadContent = false;
		hideLoading();
	});
}

function bin2hex(s) {
	// Converts the binary representation of data to hex
	//
	// version: 812.316
	// discuss at: http://phpjs.org/functions/bin2hex
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +   bugfixed by: Onno Marsman
	// +   bugfixed by: Linuxworld
	// *     example 1: bin2hex('Kev');
	// *     returns 1: '4b6576'
	// *     example 2: bin2hex(String.fromCharCode(0x00));
	// *     returns 2: '00'
	var v,i, f = 0, a = [];
	s += '';
	f = s.length;

	for (i = 0; i<f; i++) {
		a[i] = s.charCodeAt(i).toString(16).replace(/^([\da-f])$/,"0$1");
	}

	return a.join('');
}

function circleChangeMember(gid, cid, sec_token) {
	showFetching();
	$('body .fakelink').css('cursor', 'wait');
	$.get('circle/' + gid + '/' + cid + "?t=" + sec_token)
		.done(function(data) {
			showProcessing();
			$('#circle-update-wrapper').html(data);
			$('body .fakelink').css('cursor', 'auto');
		})
		.always(function() {
		hideLoading();
	});
}

function contactCircleChangeMember(checkbox, gid, cid) {
	let url;
	// checkbox.checked is the checkbox state after the click
	if (checkbox.checked) {
		url = 'circle/' + gid + '/add/' + cid;
	} else {
		url = 'circle/' + gid + '/remove/' + cid;
	}
	$('body').css('cursor', 'wait');
	showPosting();
	$.post(url)
		.fail(function () {
			// Restores previous state in case of error
			checkbox.checked = !checkbox.checked;
		})
		.always(function() {
			$('body').css('cursor', 'auto');
			hideLoading();
		});

	return true;
}

function checkboxhighlight(box) {
	if ($(box).is(':checked')) {
		$(box).addClass('checkeditem');
	} else {
		$(box).removeClass('checkeditem');
	}
}

function notificationMarkAll() {
	showFetching();
	$.get('notification/mark/all')
		.done(function(data) {
			showProcessing();
			if (timer) {
				clearTimeout(timer);
			}
			timer = setTimeout(NavUpdate,1000);
			force_update = true;
		})
		.always(function() {
		hideLoading();
	});
}

/**
 * sprintf in javascript
 *	"{0} and {1}".format('zero','uno');
 **/
String.prototype.format = function() {
	var formatted = this;
	for (var i = 0; i < arguments.length; i++) {
		var regexp = new RegExp('\\{'+i+'\\}', 'gi');
		formatted = formatted.replace(regexp, arguments[i]);
	}
	return formatted;
};
// Array Remove
Array.prototype.remove = function(item) {
	to=undefined; from=this.indexOf(item);
	var rest = this.slice((to || from) + 1 || this.length);
	this.length = from < 0 ? this.length + from : from;
	return this.push.apply(this, rest);
};

function previewTheme(elm) {
	theme = $(elm).val();
	showFetching();
	$.getJSON('pretheme?theme=' + theme)
		.done(function(data) {
			showProcessing();
			$('#theme-preview').html(`
		<div id="theme-desc">${data.desc}</div>
		<div id="theme-credits">${data.credits}</div>
		<a href="${data.img}">
			<img src="${data.img}" width="320" height="240" alt="${theme}" />
		</a>
		<div id="theme-version">${data.version}</div>
	`);
		})
		.always(function() {
			hideLoading();
		});
}

// notification permission settings in localstorage
// set by settings page
function getNotificationPermission() {
	if (window["Notification"] === undefined) {
		return null;
	}

	if (Notification.permission === 'granted') {
		var val = localStorage.getItem('notification-permissions');
		if (val === null) {
			return 'denied';
		}
		return val;
	} else {
		return Notification.permission;
	}
}

/**
 * Show a dialog loaded from an url
 * By defaults this load the url in an iframe in colorbox
 * Themes can overwrite `show()` function to personalize it
 */
var Dialog = {
	/**
	 * Show the dialog
	 *
	 * @param string url
	 * @return object colorbox
	 */
	show : function (url) {
		var size = Dialog._get_size();
		return $.colorbox({href: url, iframe:true,innerWidth: size.width+'px',innerHeight: size.height+'px'})
	},

	/**
	 * Show the Image browser dialog
	 *
	 * @param string name
	 * @param string id (optional)
	 * @return object
	 *
	 * The name will be used to build the event name
	 * fired by image browser dialog when the user select
	 * an image. The optional id will be passed as argument
	 * to the event handler
	 */
	doImageBrowser : function (name, id) {
		var url = Dialog._get_url('photo', name, id);
		return Dialog.show(url);
	},

	/**
	 * Show the File browser dialog
	 *
	 * @param string name
	 * @param string id (optional)
	 * @return object
	 *
	 * The name will be used to build the event name
	 * fired by file browser dialog when the user select
	 * a file. The optional id will be passed as argument
	 * to the event handler
	 */
	doFileBrowser : function (name, id) {
		var url = Dialog._get_url('attachment', name, id);
		return Dialog.show(url);
	},

	_get_url : function(type, name, id) {
		var hash = name;
		if (id !== undefined) {
			hash = hash + "-" + id;
		}
		return 'media/' + type + '/browser?mode=minimal#' + hash;
	},

	_get_size: function() {
		return {
			width: window.innerWidth-50,
			height: window.innerHeight-100
		};
	}
}
// @license-end