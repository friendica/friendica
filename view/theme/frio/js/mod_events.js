// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPLv3-or-later
/**
 * @file view/theme/frio/js/mod_events.js
 * Initialization of the fullCalendar and format the output.
 */

$(document).ready(function () {
	let $body = $("body");

	// start the fullCalendar
	$("#events-calendar").fullCalendar({
		firstDay: aStr.firstDay,
		monthNames: aStr["monthNames"],
		monthNamesShort: aStr["monthNamesShort"],
		dayNames: aStr["dayNames"],
		dayNamesShort: aStr["dayNamesShort"],
		allDayText: aStr.allday,
		noEventsMessage: aStr.noevent,
		buttonText: {
			today: aStr.today,
			month: aStr.month,
			week: aStr.week,
			day: aStr.day,
		},
		events: calendar_api,
		header: {
			left: "",
			//	center: 'title',
			right: "",
		},
		timeFormat: "H:mm",
		eventClick: function (calEvent, jsEvent, view) {
			showEvent(calEvent.id);
		},
		loading: function (isLoading, view) {
			if (!isLoading) {
				$("td.fc-day").dblclick(function () {
					addToModal("calendar/event/new?start=" + $(this).data("date"));
				});
			}
		},
		defaultView: aStr.defaultView,
		aspectRatio: 1,
		eventRender: function (event, element, view) {
			switch (view.name) {
				case "month":
					element
						.find(".fc-title")
						.html(
							"<span class='item-desc'>{2}</span>".format(
								event.item["author-avatar"],
								event.item["author-name"],
								event.title,
								event.desc,
								event.location,
							),
						);
					break;
				case "agendaWeek":
					if (event.item["author-name"] == null) return;
					element
						.find(".fc-title")
						.html(
							"<img src='{0}' style='height:12px; width:12px'>{1}<p>{2}</p><p>{3}</p>".format(
								event.item["author-avatar"],
								event.item["author-name"],
								event.desc,
								htmlToText(event.location),
							),
						);
					break;
				case "agendaDay":
					if (event.item["author-name"] == null) return;
					element
						.find(".fc-title")
						.html(
							"<img src='{0}' style='height:24px;width:24px'>{1}<p>{2}</p><p>{3}</p>".format(
								event.item["author-avatar"],
								event.item["author-name"],
								event.desc,
								htmlToText(event.location),
							),
						);
					break;
				case "listMonth":
					element.find(".fc-list-item-title").html(formatListViewEvent(event));
					break;
			}
		},
		eventAfterRender: function (event, element) {
			$(element).popover({
				content: eventHoverHtmlContent(event),
				container: "body",
				html: true,
				trigger: "hover",
				placement: "auto",
				template:
					'<div class="popover hovercard event-card"><div class="arrow"></div><div class="popover-content hovercard-content"></div></div>',
				sanitizeFn: function (content) {
					return DOMPurify.sanitize(content);
				},
			});
		},
	});

	// echo the title
	var view = $("#events-calendar").fullCalendar("getView");
	$("#fc-title").text(view.title);

	// show event popup
	var hash = location.hash.split("-");
	if (hash.length == 2 && hash[0] == "#link") showEvent(hash[1]);

	// event_edit

	// Go to the permissions tab if the checkbox is checked.
	$body
		.on("click", "#id_share", function () {
			if ($("#id_share").is(":checked") && !$("#id_share").attr("disabled")) {
				$("#acl-wrapper").show();
				$("a#event-perms-lnk").parent("li").show();
				toggleEventNav("a#event-perms-lnk");
				eventAclActive();
			} else {
				$("#acl-wrapper").hide();
				$("a#event-perms-lnk").parent("li").hide();
			}
		})
		.trigger("change");

	// Disable the finish time input if the user disable it.
	$body
		.on("change", "#id_nofinish", function () {
			enableDisableFinishDate();
		})
		.trigger("change");

	// JS for the permission section.
	$("#contact_allow, #contact_deny, #circle_allow, #circle_deny")
		.change(function () {
			var selstr;
			$(
				"#contact_allow option:selected, #contact_deny option:selected, #circle_allow option:selected, #circle_deny option:selected",
			).each(function () {
				selstr = $(this).html();
				$("#jot-public").hide();
			});
			if (selstr == null) {
				$("#jot-public").show();
			}
		})
		.trigger("change");

	// Change the event nav menu.tabs on click.
	$body.on("click", "#event-nav > li > a", function (e) {
		e.preventDefault();
		toggleEventNav(this);
	});

	// This is experimental. We maybe can make use of it to inject
	// some js code while the event modal opens.
	//$body.on('show.bs.modal', function () {
	//	enableDisableFinishDate();
	//});

	// Clear some elements (e.g. the event-preview container) when
	// selecting a event nav link so it don't appear more than once.
	$body.on("click", "#event-nav a", function (e) {
		$("#event-preview").empty();
		e.preventDefault();
	});

});

// loads the event into a modal
function showEvent(eventid) {
	addToModal(event_api + '/' + eventid);
}

function changeView(action, viewName) {
	$("#events-calendar").fullCalendar(action, viewName);
	var view = $("#events-calendar").fullCalendar("getView");
	$("#fc-title").text(view.title);
}

// The template for the bootstrap popover for displaying the event title and
// author (it's the nearly the same template we use in frio for the contact
// hover cards. So be careful when changing the css)
function eventHoverBodyTemplate() {
	var template =
		'\
		<div class="event-card-basic-content media">\
			<div class="event-card-details">\
				<div class="event-card-header">\
					<div class="event-card-left-date">\
						<span class="event-date-wrapper medium">\
							<span class="event-card-short-month">{5}</span>\
							<span class="event-card-short-date">{6}</span>\
						</span>\
					</div>\
					<div class="event-card-content media-body">\
						<div class="event-card-title">{2}</div>\
						<div class="event-property"><span class="event-card-date">{4}</span>{3}\
						{1}\
					</div>\
				</div>\
				<div class="clearfix"></div>\
			</div>\
		</div>';

	return template;
}

// The template for presenting the event location in the event hover-card
function eventHoverLocationTemplate() {
	var template =
		'<span aria-hidden="true"> · </span>\
			<span class="event-card-location"> {0}</span></div>';
	return template;
}

function eventHoverProfileNameTemplate() {
	var template =
		'\
			<div class="event-card-profile-name profile-entry-name">\
				<a href="{0}" class="userinfo">{1}</a>\
			</div>';
	return template;
}
// transform the event data to html so we can use it in the event hover-card
function eventHoverHtmlContent(event) {
	var eventLocation = "";
	var eventProfileName = "";
	// Get the Browser language
	var locale = window.navigator.userLanguage || window.navigator.language;
	var data = "";

	// Use the browser language for date formatting
	moment.locale(locale);

	// format dates to different styles
	var startDate = event.start.format('dd HH:mm');
	var monthShort = event.start.format('MMM');
	var dayNumberStart = event.start.format('DD');

	var formattedDate = startDate;

	// We only need the to format the end date if the event does have
	// a finish date.
	if (event.nofinish === 0 && event.end !== null) {
		var dayNumberEnd = event.end.format('DD');
		var endTime = event.end.format('HH:mm');

		formattedDate = startDate + " - " + endTime;

		// use a different Format (15. Feb - 18. Feb) if the events end date
		// is not the start date
		if (dayNumberStart !== dayNumberEnd) {
			formattedDate = event.start.format('Do MMM') + ' - ' + event.end.format('Do MMM');
		}
	}

	// Get the html template
	data = eventHoverBodyTemplate();

	// Get only template data if there exists location data
	if (event.location) {
		var eventLocationText = htmlToText(event.location);
		// Get the html template for formatting the location
		var eventLocationTemplate = eventHoverLocationTemplate();
		// Format the event location data according to the event location
		// template
		eventLocation = eventLocationTemplate.format(eventLocationText);
	}

	// Get only template data if there exists a profile name
	if (event.item["author-name"]) {
		// Get the template
		var eventProfileNameTemplate = eventHoverProfileNameTemplate();
		// Insert the data into the template
		eventProfileName = eventProfileNameTemplate.format(event.item["author-link"], event.item["author-name"]);
	}

	// Format the event data according to the event hover template
	var formatted = data.format(
		event.item["author-avatar"], // this isn't used at the present time
		eventProfileName,
		event.title,
		eventLocation,
		formattedDate,
		monthShort.replace(".", ""), // Get rid of possible dots in the string
		dayNumberStart,
	);

	return formatted;
}

// transform the list view event element into formatted html
function formatListViewEvent(event) {
	// The basic template for list view
	var template =
		'<td class="fc-list-item-title fc-widget-content">\
				<hr class="separator"></hr>\
				<div class="event-card">\
					<div class="popover-content hovercard-content">{0}</div>\
				</div>\
			</td>';
	// Use the formation of the event hover and insert it in the base list view template
	var formatted = template.format(eventHoverHtmlContent(event));

	return formatted;
}

// event_edit

// Load the html of the actual event and incect the output to the
// event-edit section.
function doEventPreview() {
	$("#event-edit-preview").val(1);
	$.post("calendar/api/create", $("#event-edit-form").serialize(), function (data) {
		$("#event-preview").append(data);
	});
	$("#event-edit-preview").val(0);
}

// The following functions show/hide the specific event-edit content
// in dependence of the selected nav.
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

	// Make sure jot text does have really the active class (we do this because there are some
	// other events which trigger jot text.
	toggleEventNav($("#event-edit-lnk"));
}

function eventDescActive() {
	$("#event-edit-wrapper, #event-preview, #event-acl-wrapper").hide();
	$("#event-desc-wrapper").show();
}

// Give the active "event-nav" list element the class "active".
function toggleEventNav(elm) {
	// Select all li of #event-nav and remove the active class.
	$(elm).closest("#event-nav").children("li").removeClass("active");
	// Add the active class to the parent of the link which was selected.
	$(elm).parent("li").addClass("active");
}

// Disable the input for the finish date if it is not available.
function enableDisableFinishDate() {
	if ($("#id_nofinish").is(":checked")) $("#id_finish_text").prop("disabled", true);
	else $("#id_finish_text").prop("disabled", false);
}

// @license-end
