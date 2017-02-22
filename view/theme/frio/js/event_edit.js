$(document).ready(function() { 
	// go to the permissions tab if the checkbox is checked
	$('body').on("change", "#id_share", function() {
		if ($('#id_share').is(':checked') && !( $('#id_share').attr("disabled"))) { 
			$('#acl-wrapper').show();
			$("a#event-perms-lnk").parent("li").show();
			toggleEventNav("a#event-perms-lnk");
			eventAclActive();
		}
		else {
			$('#acl-wrapper').hide();
			$("a#event-perms-lnk").parent("li").hide();
		}
	}).trigger('change');

	// disable the finish time input if the user disable it
	$('body').on("change", "#id_nofinish", function() {
		enableDisableFinishDate()
	}).trigger('change');

	// js for the permission sextion
	$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
		var selstr;
		$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
			selstr = $(this).text();
			$('#jot-public').hide();
		});
		if (selstr == null) {
			$('#jot-public').show();
		}

	}).trigger('change');

	// Change the event nav menu.tabs on click
	$("body").on("click", "#event-nav > li > a", function(e){
		e.preventDefault();
		toggleEventNav(this);
	});

	// this is experimental. We maybe can make use of it to inject
	// some js code while the event modal opens
	//$('body').on('show.bs.modal', function () {
	//	enableDisableFinishDate();
	//});

	// clear some elements (e.g. the event-preview container) when
	// selecting a event nav link so it don't appear more than once
	$('body').on("click", "#event-nav a", function(e) {
		$("#event-preview").empty();
		e.preventDefault();
	});

});

// Load the html of the actual event and incect the output to the
// event-edit section
function doEventPreview() {
	$('#event-edit-preview').val(1);
	$.post('events',$('#event-edit-form').serialize(), function(data) {
		$("#event-preview").append(data);
	});
	$('#event-edit-preview').val(0);
}


// this function load the content of the edit url into a modal
function eventEdit(url) {
	var char = qOrAmp(url);
	url = url + char + 'mode=none';

	$.get(url, function(data) {
		$("#modal-body").empty();
		$("#modal-body").append(data);
	}).done(function() {
		loadModalTitle();
	});
}

// the following functions show/hide the specific event-edit content 
// in dependence of the selected nav
function eventAclActive() {
	$("#event-edit-wrapper, #event-preview, #event-desc-wrapper").hide();
	$("#event-acl-wrapper").show();
}


function eventPreviewActive() {
	$("#event-acl-wrapper, #event-edit-wrapper, #event-desc-wrapper").hide();
	$("#event-preview").show();
	doEventPreview();
}

function eventEditActive() {
	$("#event-acl-wrapper, #event-preview, #event-desc-wrapper").hide();
	$("#event-edit-wrapper").show();

	//make sure jot text does have really the active class (we do this because there are some
	// other events which trigger jot text
	toggleEventNav($("#event-edit-lnk"));
}

function eventDescActive() {
	$("#event-edit-wrapper, #event-preview, #event-acl-wrapper").hide();
	$("#event-desc-wrapper").show();
}

// Give the active "event-nav" list element the class "active"
function toggleEventNav (elm) {
	// select all li of #event-nav and remove the active class
	$(elm).closest("#event-nav").children("li").removeClass("active");
	// add the active class to the parent of the link which was selected
	$(elm).parent("li").addClass("active");
}



// disable the input for the finish date if it is not available
function enableDisableFinishDate() {
	if( $('#id_nofinish').is(':checked'))
		$('#id_finish_text').prop("disabled", true);
	else
		$('#id_finish_text').prop("disabled", false);
}
