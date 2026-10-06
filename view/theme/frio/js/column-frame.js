/**
 * @file view/theme/frio/js/column-frame.js
 * Behaviour of a page that is embedded as a column in the deck (mode=column).
 *
 * @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
 * SPDX-FileCopyrightText: 2010-2026 the Friendica project
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
(function () {
	"use strict";

	if (window.parent === window) {
		return;
	}

	const MODE = "column";
	const noInterceptSelector = "[data-toggle], [data-fancybox], [data-remote], [data-gallery], [rel~=lightbox]";

	function withMode(url) {
		const parsed = new URL(url, document.baseURI);
		parsed.searchParams.set("mode", MODE);
		return parsed.href;
	}

	function post(message) {
		window.parent.postMessage(Object.assign({ friendicaColumns: true }, message), window.location.origin);
	}

	/** Diagnostic output, shown by the deck when localStorage "friendica.deck.debug" is set. */
	function log() {
		post({ action: "log", args: Array.prototype.map.call(arguments, function (arg) {
			return typeof arg === "string" || typeof arg === "number" || typeof arg === "boolean" || arg == null ? arg : String(arg);
		}) });
	}

	/** The state variables of main.js that decide whether the automatic update runs. */
	function updateState() {
		return "stopped=" + (typeof stopped !== "undefined" ? stopped : "n/a")
			+ " updateContent=" + (typeof updateContent !== "undefined" ? updateContent : "n/a")
			+ " profile_uid=" + (typeof profile_uid !== "undefined" ? profile_uid : "n/a")
			+ " in_progress=" + (typeof in_progress !== "undefined" ? in_progress : "n/a");
	}

	// Trace the automatic update of main.js: ping, live update and the counter.
	["NavUpdate", "triggerLiveUpdates", "liveUpdate", "networkUpdate", "updateCounter"].forEach(function (name) {
		const original = window[name];
		if (typeof original !== "function") {
			log("update function missing", name);
			return;
		}
		window[name] = function () {
			log("call", name, Array.prototype.slice.call(arguments).join(","), updateState());
			return original.apply(this, arguments);
		};
	});

	if (window.jQuery) {
		window.jQuery(document).ajaxComplete(function (event, xhr, settings) {
			if (/(^|\/)(ping|update_|ping_)/.test(settings.url)) {
				log("ajax", settings.url, xhr.status, String(xhr.responseText || "").length + " bytes",
					(xhr.responseText || "").substring(0, 100));
			}
		}).ajaxError(function (event, xhr, settings, error) {
			log("ajax error", settings.url, xhr.status, error);
		});
	} else {
		log("jQuery is not available");
	}

	// At the top of the page getUpdateUrl() of main.js sets force=1 and ping_network answers with an empty
	// result for forced requests. A column at the top would never report new posts, so the check is not forced.
	// Only relevant without the live update, with it main.js inserts the new posts itself.
	if (typeof networkUpdate === "function" && typeof updateContent !== "undefined" && Number(updateContent) === 0) {
		window.networkUpdate = function () {
			window.jQuery.get("ping_" + getUpdateUrl("network").replace(/([?&])force=1(&|$)/, "$1force=0$2"))
				.done(function (net) {
					updateCounter("net", net);
				});
		};
	}

	// Without #live-network main.js asks ping_network for the default network timeline of the user,
	// which says nothing about the page of this column. Only a network timeline may report new posts.
	if (typeof updateCounter === "function") {
		const updateCounterOriginal = window.updateCounter;
		window.updateCounter = function (type) {
			if (type === "net" && !document.getElementById("live-network")) {
				return;
			}
			return updateCounterOriginal.apply(this, arguments);
		};
	}

	window.addEventListener("load", function () {
		log("loaded", window.location.pathname + window.location.search,
			"live elements: " + Array.prototype.map.call(document.querySelectorAll("[id^=live-]"), function (el) { return el.id; }).join(","),
			updateState());
	});

	// Links stay in the column when they lead to the same module, otherwise they open in a new column.
	window.addEventListener("click", function (e) {
		if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
			return;
		}

		const link = e.target.closest ? e.target.closest("a[href]") : null;
		if (!link || link.target || link.hasAttribute("download") || link.matches(noInterceptSelector)) {
			return;
		}

		const href = link.getAttribute("href");
		if (href.charAt(0) === "#" || /^(javascript|mailto|tel):/i.test(href)) {
			return;
		}

		const url = new URL(link.href, document.baseURI);
		e.preventDefault();

		if (url.origin !== window.location.origin) {
			window.open(url.href, "_blank", "noopener,noreferrer");
		} else if (url.pathname === window.location.pathname) {
			window.location.href = withMode(url.href);
		} else {
			url.searchParams.delete("mode");
			post({ action: "open", url: url.pathname + url.search });
		}
	});

	document.addEventListener("DOMContentLoaded", function () {
		// With the live update enabled new posts are inserted by main.js and are visible right away.
		// Otherwise the ping counter (updateCounter() in main.js) tells the deck that a reload is needed.
		const autoInsert = typeof updateContent !== "undefined" && Number(updateContent) > 0;
		// "net-update" is used by the network timelines, "column-update" by the other pages (see columnUpdate() in main.js).
		["net-update", "column-update"].forEach(function (id) {
			const counter = document.createElement("span");
			counter.id = id;
			counter.hidden = true;
			document.body.appendChild(counter);
			new MutationObserver(function () {
				log(id + " counter", counter.className, counter.textContent, "autoInsert=" + autoInsert);
				if (!autoInsert) {
					post({ action: counter.classList.contains("show") ? "unseen" : "seen" });
				}
			}).observe(counter, { attributes: true, attributeFilter: ["class"] });
		});

		// GET forms (filters, search) and the composer have to keep the column mode.
		document.querySelectorAll("form").forEach(function (form) {
			const action = form.getAttribute("action") || "";
			if ((form.method || "get").toLowerCase() === "get") {
				const input = document.createElement("input");
				input.type = "hidden";
				input.name = "mode";
				input.value = MODE;
				form.appendChild(input);
			} else if (/^compose(\/|\?|$)/.test(action)) {
				form.setAttribute("action", withMode(action));
			}
		});

		const params = new URLSearchParams(window.location.search);
		if (params.has("posted")) {
			params.delete("posted");
			const query = params.toString();
			window.history.replaceState(null, "", window.location.pathname + (query ? "?" + query : ""));
			post({ action: "posted" });
		}
	});
})();
