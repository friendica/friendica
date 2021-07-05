$(function(){

	/* column filter */
	$("a[data-filter]").on("click", function(ev) {
		var filter = this.dataset.filter;
		var value = this.dataset.filterValue;
		var re = RegExp(filter+"=[a-z]*");
		var newhref = location.href;
		if (!location.href.indexOf("?") < 0) {
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


	function show_details_for_element(element) {
		var $modal = $("#logdetail");

		$modal[0].dataset.rowId = element.id;

		var tr = $modal.find(".main-data tbody tr")[0];
		tr.innerHTML = element.innerHTML;
		
		var data = JSON.parse(element.dataset.source);
		$modal.find(".source-data td").each(function(i,elm){
			var k = elm.dataset.value;
			elm.innerText = data[k];
		});

		var elm = $modal.find(".event-data")[0];
		elm.innerHTML = "";
		var data = element.dataset.data;
		if (data !== "") {
			elm.innerHTML = "<h3>Data</h3>";
			data = JSON.parse(data);
			elm.innerHTML += recursive_details("", data);
		}

		$("[data-previous").prop("disabled", $(element).prev().length == 0);
		$("[data-next").prop("disabled", $(element).next().length == 0);
		
		$modal.modal({})
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
