/**
 * @file view/theme/frio/js/mod_events.js
 * @brief Initialization of the fullCalendar and format the output.
 */

$(document).ready(function() {
	// start the fullCalendar
	$('#events-calendar').fullCalendar({
		firstDay: aStr.firstDay,
		monthNames: aStr['monthNames'],
		monthNamesShort: aStr['monthNamesShort'],
		dayNames: aStr['dayNames'],
		dayNamesShort: aStr['dayNamesShort'],
		allDayText: aStr.allday,
		noEventsMessage: aStr.noevent,
		buttonText: {
			today: aStr.today,
			month: aStr.month,
			week: aStr.week,
			day: aStr.day
		},
		events: baseurl + moduleUrl + '/json/',
		header: {
			left: '',
		//	center: 'title',
			right: ''
		},			
		timeFormat: 'H:mm',
		eventClick: function(calEvent, jsEvent, view) {
			showEvent(calEvent.id);
		},
		loading: function(isLoading, view) {
			if(!isLoading) {
				$('td.fc-day').dblclick(function() { addToModal('/events/new?start='+$(this).data('date')); });
			}
		},
		defaultView: 'month',
		aspectRatio: 1,
		eventRender: function(event, element, view) {
			//console.log(view.name);
			switch(view.name){
				case "month":
					element.find(".fc-title").html(
						"<span class='item-desc'>{2}</span>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.title,
							event.item.desc,
							event.item.location
					));
					break;
				case "agendaWeek":
					if (event.item['author-name'] == null) return;
					element.find(".fc-title").html(
						"<img src='{0}' style='height:12px; width:12px'>{1}<p>{2}</p><p>{3}</p>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.item.desc,
							htmlToText(event.item.location)
					));
				break;
				case "agendaDay":
					if (event.item['author-name'] == null) return;
					element.find(".fc-title").html(
						"<img src='{0}' style='height:24px;width:24px'>{1}<p>{2}</p><p>{3}</p>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.item.desc,
							htmlToText(event.item.location)
					));
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
				template: '<div class="popover hovercard event-card"><div class="arrow"></div><div class="popover-content hovercard-content"></div></div>',
			});

		}

	})

	// center on date
	var args=location.href.replace(baseurl,"").split("/");
	if (modparams == 2) {
		if (args.length>=5) {
			$("#events-calendar").fullCalendar('gotoDate',args[3] , args[4]-1);
		}
	} else {
		if (args.length>=4) {
			$("#events-calendar").fullCalendar('gotoDate',args[2] , args[3]-1);
		}
	}

	// echo the title
	var view = $('#events-calendar').fullCalendar('getView');
	$('#fc-title').text(view.title);

	// show event popup
	var hash = location.hash.split("-")
	if (hash.length==2 && hash[0]=="#link") showEvent(hash[1]);

});

// loads the event into a modal
function showEvent(eventid) {
		addToModal(baseurl + moduleUrl + '/?id=' + eventid);
}

function changeView(action, viewName) {
	$('#events-calendar').fullCalendar(action, viewName);
	var view = $('#events-calendar').fullCalendar('getView');
	$('#fc-title').text(view.title);
}

// The template for the bootstrap popover for displaying the event title and
// author (it's the nearly the same template we use in frio for the contact
// hover cards. So be careful when changing the css)
function eventHoverBodyTemplate() {
	var template = '\
		<div class="event-card-basic-content media">\
			<div class="hover-card-details">\
				<div class="hover-card-header left-align">\
					<div class="event-hover-left-date left-align">\
						<span class="event-date-wrapper medium">\
							<span class="event-hover-short-month">{5}</span>\
							<span class="event-hover-short-date">{6}</span>\
						</span>\
					</div>\
					<div class="event-card-content media-body">\
						<div class="event-hover-title">{2}</div>\
						<div class="event-property"><span class="event-hover-date">{4}</span>{3}\
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
	var template = '<span class="event-hover-location"> {0}</span></div>';
	return template;
}

function eventHoverProfileNameTemplate() {
	var template = '\
			<div class="event-hover-profile-name profile-entry-name">\
				<span class="left-align1"><a href="{0}" class="userinfo">{1}</a></span>\
			</div>';
	return template;
}
// transform the event data to html so we can use it in the event hover-card 
function eventHoverHtmlContent(event) {
	var eventLocation = '';
	var eventProfileName = '';
	// Get the Browser language
	var locale = window.navigator.userLanguage || window.navigator.language;
	var data = '';

	// Use the browser language for date formatting
	moment.locale(locale);

	// format dates to different styles
	var startDate = moment(event.item.start).format('dd HH:mm');
	var endDate = moment(event.item.finsih).format('dd HH:mm');
	var monthShort = moment(event.item.start).format('MMM');
	var dayNumberStart = moment(event.item.start).format('DD');
	var dayNumberEnd = moment(event.item.finish).format('DD');
	var startTime = moment(event.item.start).format('HH:mm');
	var endTime = moment(event.item.finish).format('HH:mm');
	var monthNumber;

	var formattedDate = startDate;

	// We only need the to format the end date if the event does have
	// a finish date. 
	if (event.item.nofinish == 0) {
		formattedDate = startDate + ' - ' + endTime;

		// use a different Format (15. Feb - 18. Feb) if the events end date
		// is not the start date
		if ( dayNumberStart != dayNumberEnd) {
			formattedDate = moment(event.item.start).format('Do MMM') + 
					' - ' +
					moment(event.item.finish).format('Do MMM');
		}
	}

	// Get the html template
	data = eventHoverBodyTemplate();

	// Get only template data if there exists location data
	if (event.item.location) {
		var eventLocationText = htmlToText(event.item.location);
		// Get the the html template for formatting the location
		var eventLocationTemplate = eventHoverLocationTemplate();
		// Format the event location data according to the the event location
		// template
		eventLocation = eventLocationTemplate.format(
					eventLocationText
		);
	}

	// Get only template data if there exists a profile name
	if (event.item['author-name']) {
		// Get the template
		var eventProfileNameTemplate = eventHoverProfileNameTemplate();
		// Insert the data into the template
		eventProfileName = eventProfileNameTemplate.format(
					event.item['author-link'],
					event.item['author-name']
		);
	}

	// Format the event data according to the event hover template
	var formatted = data.format(
				event.item['author-avatar'], // this isn't used at the present time
				eventProfileName,
				event.title,
				eventLocation,
				formattedDate,
				monthShort.replace('.', ''), // Get rid of possible dots in the string
				dayNumberStart
			);

	return formatted;
}

// transform the the list view event element into formatted html
function formatListViewEvent(event) {
	// The basic template for list view
	var template = '<td class="fc-list-item-title fc-widget-content">\
				<hr class="seperator"></hr>\
				<div class="event-card">\
					<div class="popover-content hovercard-content">{0}</div>\
				</div>\
			</td>';
	// Use the formation of the event hover and insert it in the base list view template
	var formatted = template.format(eventHoverHtmlContent(event));

	return formatted;
}
