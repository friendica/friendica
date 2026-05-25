// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

$(function(){

	/* column filter */
	$("a[data-filter]").on("click", function(ev) {
		var filter = this.dataset.filter;
		var value = this.dataset.filterValue;
		var re = RegExp(filter+"=[a-z]*");
		var newhref = location.href;
		if (location.href.indexOf("?") < 0) {
			newhref = location.href + "?" + filter + "=" + value;
		} else if (location.href.match(re)) {
			newhref = location.href.replace(RegExp(filter+"=[a-z]*"), filter+"="+value);
		} else {
			newhref = location.href + "&" + filter + "=" + value;
		}
		location.href = newhref;
		return false;
	});

	/* log details dialog */
	$(".log-event").on("click", function(ev) {
		show_details_for_element(ev.currentTarget);
	});
	$(".log-event").on("keydown", function(ev) {
		if (ev.keyCode == 13 || ev.keyCode == 32) {
			show_details_for_element(ev.currentTarget);
		}
	});


	$("[data-previous").on("click", function(ev){ 
		var currentid = document.getElementById("logdetail").dataset.rowId;
		var $elm = $("#" + currentid).prev();
		if ($elm.length == 0) return;
		show_details_for_element($elm[0]);
	});

	$("[data-next").on("click", function(ev){ 
		var currentid = document.getElementById("logdetail").dataset.rowId;
		var $elm = $("#" + currentid).next();
		if ($elm.length == 0) return;
		show_details_for_element($elm[0]);
	});


	const $modal = $("#logdetail");

	$modal.on("hidden.bs.modal", function(ev){
		document
			.querySelectorAll('[aria-expanded="true"]')
			.forEach(elm => elm.setAttribute("aria-expanded", false))
	});

	function show_details_for_element(element) {
		$modal[0].dataset.rowId = element.id;

		var tr = $modal.find(".main-data tbody tr")[0];
		tr.innerHTML = element.innerHTML;
		
		var data = JSON.parse(element.dataset.source);
		$modal.find(".source-data td").each(function(i,elm){
			var k = elm.dataset.value;
			elm.innerText = data[k];
		});

		var elm = $modal.find(".event-data")[0];
		const event_data_header = $modal.find(".event-data-header")[0];

		// Cleanup event data
		event_data_header.hidden = true;
		elm.innerHTML = "";

		// Fill out event data
		var data = element.dataset.data;
		if (data !== "") {
			event_data_header.hidden = false;
			data = JSON.parse(data);
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
