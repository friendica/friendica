// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
/*
 * The file contains functions for text editing and commenting
 */

// Lifted from https://css-tricks.com/snippets/jquery/move-cursor-to-end-of-textarea-or-input/
jQuery.fn.putCursorAtEnd = function () {
	return this.each(function () {
		// Cache references
		var $el = $(this),
			el = this;

		// Only focus if input isn't already
		if (!$el.is(":focus")) {
			$el.focus();
		}

		// If this function exists... (IE 9+)
		if (el.setSelectionRange) {
			// Double the length because Opera is inconsistent about whether a carriage return is one character or two.
			var len = $el.val().length * 2;

			// Timeout seems to be required for Blink
			setTimeout(function () {
				el.setSelectionRange(len, len);
			}, 1);
		} else {
			// As a fallback, replace the contents with itself
			// Doesn't work in Chrome, but Chrome supports setSelectionRange
			$el.val($el.val());
		}

		// Scroll to the bottom, in case we're in a tall textarea
		// (Necessary for Firefox and Chrome)
		this.scrollTop = 999999;
	});
};

function commentGetLink(id, prompttext) {
	reply = prompt(prompttext);
	if (reply && reply.length) {
		reply = bin2hex(reply);
		$.get("parseurl?noAttachment=1&binurl=" + reply, function (data) {
			addCommentText(data, id);
		});
	}
}

function addCommentText(data, id) {
	// get the textfield
	var textfield = document.getElementById("comment-edit-text-" + id);
	// check if the textfield does have the default-value
	commentOpenUI(textfield, id);
	// save already existent content
	var currentText = $("#comment-edit-text-" + id).val();
	//insert the data as new value
	textfield.value = currentText + data;
	autosize.update($("#comment-edit-text-" + id));
}

function commentLinkDrop(event, id) {
	var reply = event.dataTransfer.getData("text/uri-list");
	event.target.textContent = reply;
	event.preventDefault();
	if (reply && reply.length) {
		reply = bin2hex(reply);
		$.get("parseurl?noAttachment=1&binurl=" + reply, function (data) {
			addCommentText(data, id);
		});
	}
}

function commentLinkDropper(event) {
	var linkFound = event.dataTransfer.types.contains("text/uri-list");
	if (linkFound) {
		event.preventDefault();
	}
}

function insertFormattingToPost(BBCode) {
	textarea = document.getElementById("profile-jot-text");

	insertBBCodeInTextarea(BBCode, textarea);

	return true;
}

function showThread(id) {
	$("#collapsed-comments-" + id).show();
	$("#collapsed-comments-" + id + " .collapsed-comments").show();
}
function hideThread(id) {
	$("#collapsed-comments-" + id).hide();
	$("#collapsed-comments-" + id + " .collapsed-comments").hide();
}

function cmtBbOpen(id) {
	$("#comment-edit-bb-" + id).show();
}
function cmtBbClose(id) {
	$("#comment-edit-bb-" + id).hide();
}

function commentExpand(id) {
	$("#mod-cmnt-wrap-" + id).show();
	closeMenu("comment-fake-form-" + id);
	openMenu("item-comments-" + id);
	$("#comment-edit-text-" + id)
		.putCursorAtEnd()
		.addClass("comment-edit-text-full")
		.removeClass("comment-edit-text-empty");

	return true;
}

function commentClose(obj, id) {
	if (obj.value === "" || obj.value === obj.dataset.default) {
		$("#comment-edit-text-" + id)
			.removeClass("comment-edit-text-full")
			.addClass("comment-edit-text-empty");
		$("#mod-cmnt-wrap-" + id).hide();
		openMenu("comment-fake-form-" + id);
		closeMenu("item-comments-" + id);
		return true;
	}
	return false;
}

function showHideCommentBox(id) {
	var $el = $("#comment-edit-form-" + id);
	if ($el.is(":visible")) {
		$el.hide();
	} else {
		$el.show();
	}
}

function commentOpenUI(obj, id) {
	closeMenu("comment-fake-form-" + id);
	openMenu("item-comments-" + id);
	$("#comment-edit-text-" + id)
		.putCursorAtEnd()
		.addClass("comment-edit-text-full")
		.removeClass("comment-edit-text-empty")
		.attr("tabindex", "9"); // Choose an arbitrary tab index that's greater than what we're using in jot (3 of them)
	$("#comment-edit-submit-" + id).attr("tabindex", "10"); // The submit button gets tabindex + 1
	// initialize autosize for this comment
	autosize($("#comment-edit-text-" + id + ".text-autosize"));
}

function commentCloseUI(obj, id) {
	if (obj.value === "" || obj.value === obj.dataset.default) {
		$("#comment-edit-text-" + id)
			.removeClass("comment-edit-text-full")
			.addClass("comment-edit-text-empty")
			.removeAttr("tabindex");
		$("#comment-edit-submit-" + id).removeAttr("tabindex");
		openMenu("comment-fake-form-" + id);
		closeMenu("item-comments-" + id);
		// destroy the automatic textarea resizing
		autosize.destroy($("#comment-edit-text-" + id + ".text-autosize"));
	}
}

function jotTextOpenUI(obj) {
	if (obj.value === "" || obj.value === obj.dataset.default) {
		var $el = $(".modal-body #profile-jot-text");
		$el.addClass("profile-jot-text-full").removeClass("profile-jot-text-empty");
		// initiale autosize for the jot
		autosize($el);
	}
}

function jotTextCloseUI(obj) {
	if (obj.value === "" || obj.value === obj.dataset.default) {
		var $el = $(".modal-body #profile-jot-text");
		$el.removeClass("profile-jot-text-full").addClass("profile-jot-text-empty");
		// destroy the automatic textarea resizing
		autosize.destroy($el);
	}
}

function commentOpen(obj, id) {
	if (obj.value === "" || obj.value === obj.dataset.default) {
		$("#comment-edit-text-" + id)
			.putCursorAtEnd()
			.addClass("comment-edit-text-full")
			.removeClass("comment-edit-text-empty");
		$("#mod-cmnt-wrap-" + id).show();
		closeMenu("comment-fake-form-" + id);
		openMenu("item-comments-" + id);
		return true;
	}
	return false;
}

function confirmDelete() {
	return confirm(aStr.delitem);
}

function confirmBlock() {
	return confirm(aStr.blockAuthor);
}

function confirmIgnore() {
	return confirm(aStr.ignoreAuthor);
}

/**
 * Hide and removes an item element from the DOM after the deletion url is
 * successful, restore it else.
 *
 * @param {string} url The item removal URL
 * @param {string} elementId The DOM id of the item element
 * @returns {undefined}
 */
function dropItem(url, elementId) {
	if (confirmDelete()) {
		$("body").css("cursor", "wait");

		var $el = $(document.getElementById(elementId));

		$el.fadeTo('fast', 0.33, function () {
			$.get(url).then(function() {
				$el.remove();
			}).fail(function() {
				// @todo Show related error message
				$el.show();
			}).always(function() {
				$("body").css('cursor', 'auto');
			});
		});
	}
}

/**
 * Blocks an author and hide and removes an item element from the DOM after the block is
 * successful, restore it else.
 *
 * @param {string} url The item removal URL
 * @param {string} elementId The DOM id of the item element
 * @returns {undefined}
 */
function blockAuthor(url, elementId) {
	if (confirmBlock()) {
		$("body").css("cursor", "wait");

		var $el = $(document.getElementById(elementId));

		$el.fadeTo("fast", 0.33, function () {
			$.get(url)
				.then(function () {
					$el.remove();
				})
				.fail(function () {
					// @todo Show related error message
					$el.show();
				})
				.always(function () {
					$("body").css("cursor", "auto");
				});
		});
	}
}

/**
 * Ignored an author and hide and removes an item element from the DOM after the block is
 * successful, restore it else.
 *
 * @param {string} url The item removal URL
 * @param {string} elementId The DOM id of the item element
 * @returns {undefined}
 */
function ignoreAuthor(url, elementId) {
	if (confirmIgnore()) {
		$("body").css("cursor", "wait");

		var $el = $(document.getElementById(elementId));

		$el.fadeTo("fast", 0.33, function () {
			$.get(url)
				.then(function () {
					$el.remove();
				})
				.fail(function () {
					// @todo Show related error message
					$el.show();
				})
				.always(function () {
					$("body").css("cursor", "auto");
				});
		});
	}
}
// @license-end
