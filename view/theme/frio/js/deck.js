/**
 * @file view/theme/frio/js/deck.js
 * Deck (/deck): every column is a page embedded as an iframe in the column mode.
 *
 * @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
 * SPDX-FileCopyrightText: 2010-2026 the Friendica project
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
(function () {
	"use strict";

	const STORAGE_KEY = "friendica.deck";
	const host = document.getElementById("deck-host");
	if (!host) {
		return;
	}

	const config = JSON.parse(host.dataset.config);
	const scroller = document.getElementById("deck-scroller");
	const addMenu = document.getElementById("deck-add-menu");
	const composeModal = document.getElementById("deck-compose-modal");
	const composeFrame = document.getElementById("deck-compose-frame");
	let columns = [];

	/** Diagnostic output, enabled with localStorage.setItem("friendica.deck.debug", "1"). */
	function log() {
		try {
			if (window.localStorage.getItem("friendica.deck.debug")) {
				console.debug.apply(console, ["[deck]"].concat(Array.prototype.slice.call(arguments)));
			}
		} catch (e) {
			// Storage is not available, no logging.
		}
	}

	host.style.backgroundColor = window.getComputedStyle(document.body).backgroundColor;

	/** Places the host directly below the fixed navigation bars. */
	function placeBelowNavigation() {
		let top = 0;
		["topbar-first", "topbar-second"].forEach(function (id) {
			const bar = document.getElementById(id);
			if (bar) {
				top = Math.max(top, bar.getBoundingClientRect().bottom);
			}
		});

		if (top > 0) {
			host.style.top = top + "px";
		}
	}

	placeBelowNavigation();
	window.addEventListener("resize", placeBelowNavigation);

	/** Returns path and query of a same-origin URL without the column mode parameters, or null. */
	function normalize(path) {
		try {
			const url = new URL(path, baseurl + "/");
			if (url.origin !== window.location.origin) {
				return null;
			}
			url.searchParams.delete("mode");
			url.searchParams.delete("posted");
			return url.pathname + url.search;
		} catch (e) {
			return null;
		}
	}

	function frameUrl(path) {
		const url = new URL(path, window.location.origin);
		url.searchParams.set("mode", "column");
		return url.pathname + url.search;
	}

	function isCompose(path) {
		return /\/compose(\/|$)/.test(path.split("?")[0]);
	}

	function load() {
		try {
			const stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY));
			if (Array.isArray(stored)) {
				return stored.filter(function (column) {
					return column && normalize(column.url) && !isCompose(column.url);
				});
			}
		} catch (e) {
			// Storage is not available or broken, use the defaults.
		}

		return config.defaults.map(function (path) {
			const entry = config.timelines.concat(config.pages).find(function (item) {
				return item.path === path;
			});
			return { url: normalize(path), title: entry ? entry.title : "" };
		});
	}

	function save() {
		try {
			window.localStorage.setItem(STORAGE_KEY, JSON.stringify(columns.map(function (column) {
				return { url: column.url, title: column.title };
			})));
		} catch (e) {
			// Not persisting is acceptable.
		}
	}

	function button(icon, label, onClick) {
		const element = document.createElement("button");
		element.type = "button";
		element.className = "btn btn-link";
		element.title = label;
		element.setAttribute("aria-label", label);
		element.innerHTML = '<i class="' + icon + '" aria-hidden="true"></i>';
		element.addEventListener("click", function () {
			onClick();
			// Don't keep the button highlighted by the focus after the action.
			element.blur();
		});
		return element;
	}

	/** Fills a dropdown menu with all page types a column can show. */
	function fillMenu(menu, onSelect) {
		function item(label, title, onClick) {
			const li = document.createElement("li");
			const link = document.createElement("a");
			link.href = "#";
			link.textContent = label;
			if (title) {
				link.title = title;
			}
			link.addEventListener("click", function (e) {
				e.preventDefault();
				onClick();
			});
			li.appendChild(link);
			menu.appendChild(li);
		}

		function separator() {
			const li = document.createElement("li");
			li.className = "divider";
			li.setAttribute("role", "separator");
			menu.appendChild(li);
		}

		config.timelines.forEach(function (entry) {
			item(entry.title, entry.description, function () { onSelect(entry.path, entry.title); });
		});

		if (config.timelines.length) {
			separator();
		}

		config.pages.forEach(function (entry) {
			item(entry.title, "", function () { onSelect(entry.path, entry.title); });
		});

		[config.circles, config.groups, config.searches].forEach(function (entries) {
			if (entries && entries.length) {
				separator();
				entries.forEach(function (entry) {
					item(entry.title, "", function () { onSelect(entry.path, entry.title); });
				});
			}
		});

		separator();

		item(config.l10n.search.title + "…", "", function () {
			const term = window.prompt(config.l10n.search.prompt);
			if (term) {
				onSelect("search?q=" + encodeURIComponent(term), config.l10n.search.title + ": " + term);
			}
		});

		item(config.l10n.custom.title + "…", "", function () {
			const path = window.prompt(config.l10n.custom.prompt);
			if (path) {
				onSelect(path, "");
			}
		});
	}

	function move(column, offset) {
		const index = columns.indexOf(column);
		const target = index + offset;
		if (target < 0 || target >= columns.length) {
			return;
		}

		columns.splice(index, 1);
		columns.splice(target, 0, column);
		scroller.insertBefore(column.element, scroller.children[target + (offset > 0 ? 1 : 0)] || null);
		save();
	}

	function remove(column) {
		columns.splice(columns.indexOf(column), 1);
		column.element.remove();
		save();
	}

	function reload(column) {
		try {
			column.frame.contentWindow.location.reload();
		} catch (e) {
			column.frame.src = frameUrl(column.url);
		}
	}

	/** Marks the reload button of a column when there are new posts that aren't inserted automatically. */
	function setUnseen(column, unseen) {
		column.reloadButton.classList.toggle("has-new", unseen);
	}

	function scrollToTop(column) {
		try {
			column.frame.contentWindow.scrollTo({ top: 0, behavior: "smooth" });
		} catch (e) {
			// Frame not accessible yet, nothing to scroll.
		}
	}

	/** Shows another page in an existing column. */
	function change(column, path, titleText) {
		const url = normalize(path);
		if (!url) {
			return;
		}

		column.url = url;
		column.title = titleText;
		column.titleElement.textContent = titleText;
		column.frame.src = frameUrl(url);
		save();
	}

	function createColumn(data) {
		const element = document.createElement("section");
		element.className = "friendica-column";

		const header = document.createElement("div");
		header.className = "friendica-column-header";

		const switcher = document.createElement("div");
		switcher.className = "friendica-column-switcher dropdown";

		const toggle = document.createElement("button");
		toggle.type = "button";
		toggle.className = "btn btn-link friendica-column-title dropdown-toggle";
		toggle.title = config.l10n.change;
		toggle.setAttribute("data-toggle", "dropdown");
		toggle.setAttribute("aria-haspopup", "true");
		toggle.setAttribute("aria-expanded", "false");

		const title = document.createElement("span");
		title.textContent = data.title || "";
		toggle.append(title, " ", Object.assign(document.createElement("span"), { className: "caret" }));

		const menu = document.createElement("ul");
		menu.className = "dropdown-menu";
		switcher.append(toggle, menu);

		const frame = document.createElement("iframe");
		frame.src = frameUrl(data.url);

		const column = { url: data.url, title: data.title || "", element: element, frame: frame, titleElement: title };

		fillMenu(menu, function (path, titleText) { change(column, path, titleText); });

		column.reloadButton = button("ri-refresh-line", config.l10n.reload, function () { reload(column); });

		header.append(
			switcher,
			button("ri-arrow-up-line", config.l10n.scrollTop, function () { scrollToTop(column); }),
			column.reloadButton,
			button("ri-arrow-left-s-line", config.l10n.moveLeft, function () { move(column, -1); }),
			button("ri-arrow-right-s-line", config.l10n.moveRight, function () { move(column, 1); }),
			button("ri-close-line", config.l10n.remove, function () { remove(column); })
		);
		element.append(header, frame);

		frame.addEventListener("load", function () {
			setUnseen(column, false);
			try {
				// Follow the navigation inside the column so it is restored on the next visit.
				const current = normalize(frame.contentWindow.location.href);
				if (current) {
					column.url = current;
				}
				if (!column.title && frame.contentDocument.title) {
					title.textContent = frame.contentDocument.title;
				}
				save();
			} catch (e) {
				// Not available yet, keep the stored values.
			}
		});

		return column;
	}

	/** Adds a column, directly right of "after" if given, at the end otherwise. */
	function add(path, titleText, after) {
		const url = normalize(path);
		if (!url) {
			return;
		}

		const column = createColumn({ url: url, title: titleText });
		const index = after ? columns.indexOf(after) + 1 : columns.length;
		columns.splice(index, 0, column);
		scroller.insertBefore(column.element, scroller.children[index] || null);
		column.element.scrollIntoView({ behavior: "smooth", inline: "nearest", block: "nearest" });
		save();
	}

	function closeCompose() {
		window.jQuery(composeModal).modal("hide");
		composeFrame.src = "about:blank";
	}

	fillMenu(addMenu, function (path, titleText) { add(path, titleText); });

	document.getElementById("deck-new-post").addEventListener("click", function () {
		composeFrame.src = frameUrl(this.dataset.composeUrl);
		window.jQuery(composeModal).modal("show");
	});

	window.addEventListener("message", function (e) {
		if (e.origin !== window.location.origin || !e.data || !e.data.friendicaColumns) {
			return;
		}

		const source = columns.find(function (column) {
			return column.frame.contentWindow === e.source;
		});

		if (e.data.action === "log") {
			if (source) {
				log.apply(null, ["column " + columns.indexOf(source)].concat(e.data.args));
			}
			return;
		}

		log("message", e.data.action, source ? "column " + columns.indexOf(source) : "unknown source", e.data);

		if (e.data.action === "open" && source) {
			add(e.data.url, "", source);
		} else if (e.data.action === "unseen" && source) {
			setUnseen(source, true);
		} else if (e.data.action === "seen" && source) {
			setUnseen(source, false);
		} else if (e.data.action === "posted") {
			// A new post changes the timelines.
			closeCompose();
			columns.forEach(reload);
		}
	});

	columns = load().map(createColumn);
	columns.forEach(function (column) {
		scroller.appendChild(column.element);
	});
})();
