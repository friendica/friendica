// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// Runs again after every SPA navigation, since the log table and the dialog are replaced then
window.onDocumentReady("#logdetail", function () {

	/* column filter */
	$("a[data-filter]").off(".logs").on("click.logs", function(ev) {
		var filter = this.dataset.filter;
		var value = this.dataset.filterValue;
		var re = RegExp(filter+"=[a-z_]*");
		var newhref = location.href;
		if (location.href.indexOf("?") < 0) {
			newhref = location.href + "?" + filter + "=" + value;
		} else if (location.href.match(re)) {
			newhref = location.href.replace(re, filter+"="+value);
		} else {
			newhref = location.href + "&" + filter + "=" + value;
		}
		location.href = newhref;
		return false;
	});

	/* log details dialog */
	$(".log-event").off(".logs").on("click.logs", function(ev) {
		show_details_for_element(ev.currentTarget);
	});
	$(".log-event").on("keydown.logs", function(ev) {
		if (ev.keyCode == 13 || ev.keyCode == 32) {
			show_details_for_element(ev.currentTarget);
		}
	});


	$("[data-previous]").off(".logs").on("click.logs", function(ev){ 
		var currentid = document.getElementById("logdetail").dataset.rowId;
		var $elm = $("#" + currentid).prev();
		if ($elm.length == 0) return;
		show_details_for_element($elm[0]);
	});

	$("[data-next]").off(".logs").on("click.logs", function(ev){ 
		var currentid = document.getElementById("logdetail").dataset.rowId;
		var $elm = $("#" + currentid).next();
		if ($elm.length == 0) return;
		show_details_for_element($elm[0]);
	});


	const $modal = $("#logdetail");

	$modal.off(".logs").on("hidden.bs.modal.logs", function(ev){
		document
			.querySelectorAll('[aria-expanded="true"]')
			.forEach(elm => elm.setAttribute("aria-expanded", false))
	});

	function show_details_for_element(element) {
		$modal[0].dataset.rowId = element.id;

		var tr = $modal.find(".main-data tbody tr")[0];
		tr.innerHTML = element.innerHTML;
		
		var source = element.dataset.source !== "" ? JSON.parse(element.dataset.source) : {};
		var data = element.dataset.data !== "" ? JSON.parse(element.dataset.data) : {};

		// The worker id is part of the context, but it belongs to the source information
		source.worker_id = data.worker_id;
		delete data.worker_id;

		$modal.find(".source-data td").each(function(i,elm){
			var v = source[elm.dataset.value] ?? "";
			if (elm.dataset.search !== undefined && v !== "") {
				elm.innerHTML = "";
				$("<a>").attr("href", location.pathname + "?q=" + encodeURIComponent('"' + v + '"')).text(v).appendTo(elm);
			} else {
				elm.innerText = v;
			}
		});

		const event_stack = $modal.find(".event-stack")[0];
		const event_stack_header = $modal.find(".event-stack-header")[0];
		event_stack.innerText = (source.stack ?? "").split(", ").join("\n");
		event_stack.hidden = event_stack_header.hidden = !source.stack;

		var elm = $modal.find(".event-data")[0];
		const event_data_header = $modal.find(".event-data-header")[0];

		// Cleanup event data
		event_data_header.hidden = true;
		elm.innerHTML = "";

		// Fill out event data
		if (Object.keys(data).length > 0) {
			event_data_header.hidden = false;
			elm.innerHTML += recursive_details("", data);
		}

		$("[data-previous").prop("disabled", $(element).prev().length == 0);
		$("[data-next").prop("disabled", $(element).next().length == 0);
		
		$modal.modal({})
		element.setAttribute("aria-expanded", true);
	}

	function recursive_details(s, data, lev=0) {
		for(var k in data) {
			if (data.hasOwnProperty(k)) {
				var v = data[k];
				var open = lev > 1 ? "" : "open";
				s += "<details " + open + "><summary>" + k + "</summary>";
				if (typeof v === 'object' && v !== null) {
					s = recursive_details(s, v, lev+1);
				} else {
					s +=  $("<pre>").text(v)[0].outerHTML;
				}
				s += "</details>";
			}
		}
		return s;
	}
});
