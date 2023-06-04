<link rel="stylesheet" type="text/css" href="{{$baseurl}}/view/asset/fullcalendar/dist/fullcalendar.min.css?v={{$smarty.const.FRIENDICA_VERSION}}" />
<link rel="stylesheet" type="text/css" href="{{$baseurl}}/view/asset/fullcalendar/dist/fullcalendar.print.min.css?v={{$smarty.const.FRIENDICA_VERSION}}" media="print" />
<script type="text/javascript" src="{{$baseurl}}/view/asset/moment/min/moment-with-locales.min.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>
<script type="text/javascript" src="{{$baseurl}}/view/asset/fullcalendar/dist/fullcalendar.min.js?v={{$smarty.const.FRIENDICA_VERSION}}"></script>

<script>
	function showEvent(eventid) {
		$.get(
			'{{$event_api}}/' + eventid,
			function(data){
				$.colorbox({html:data});
			}
		);
	}

	function doEventPreview() {
		$('#event-edit-preview').val(1);
		$.post('events', $('#event-edit-form').serialize(), function(data) {
			$.colorbox({ html: data });
		});
		$('#event-edit-preview').val(0);
	}

	// disable the input for the finish date if it is not available
	function enableDisableFinishDate() {
		if( $('#id_nofinish').is(':checked'))
			$('#id_finish_text').prop("disabled", true);
		else
			$('#id_finish_text').prop("disabled", false);
	}

	$(document).ready(function() {
		$('#events-calendar').fullCalendar({
			defaultView: '{{$i18n.defaultView|escape:'quotes'}}',
			firstDay: '{{$i18n.firstDay|escape:'quotes'}}',
			monthNames: [
				'{{$i18n.January|escape:'quotes'}}',
				'{{$i18n.February|escape:'quotes'}}',
				'{{$i18n.March|escape:'quotes'}}',
				'{{$i18n.April|escape:'quotes'}}',
				'{{$i18n.May|escape:'quotes'}}',
				'{{$i18n.June|escape:'quotes'}}',
				'{{$i18n.July|escape:'quotes'}}',
				'{{$i18n.August|escape:'quotes'}}',
				'{{$i18n.September|escape:'quotes'}}',
				'{{$i18n.October|escape:'quotes'}}',
				'{{$i18n.November|escape:'quotes'}}',
				'{{$i18n.December|escape:'quotes'}}'
			],
			monthNamesShort: [
				'{{$i18n.Jan|escape:'quotes'}}',
				'{{$i18n.Feb|escape:'quotes'}}',
				'{{$i18n.Mar|escape:'quotes'}}',
				'{{$i18n.Apr|escape:'quotes'}}',
				'{{$i18n.May|escape:'quotes'}}',
				'{{$i18n.Jun|escape:'quotes'}}',
				'{{$i18n.Jul|escape:'quotes'}}',
				'{{$i18n.Aug|escape:'quotes'}}',
				'{{$i18n.Sep|escape:'quotes'}}',
				'{{$i18n.Oct|escape:'quotes'}}',
				'{{$i18n.Nov|escape:'quotes'}}',
				'{{$i18n.Dec|escape:'quotes'}}'
			],
			dayNames: [
				'{{$i18n.Sunday|escape:'quotes'}}',
				'{{$i18n.Monday|escape:'quotes'}}',
				'{{$i18n.Tuesday|escape:'quotes'}}',
				'{{$i18n.Wednesday|escape:'quotes'}}',
				'{{$i18n.Thursday|escape:'quotes'}}',
				'{{$i18n.Friday|escape:'quotes'}}',
				'{{$i18n.Saturday|escape:'quotes'}}'
			],
			dayNamesShort: [
				'{{$i18n.Sun|escape:'quotes'}}',
				'{{$i18n.Mon|escape:'quotes'}}',
				'{{$i18n.Tue|escape:'quotes'}}',
				'{{$i18n.Wed|escape:'quotes'}}',
				'{{$i18n.Thu|escape:'quotes'}}',
				'{{$i18n.Fri|escape:'quotes'}}',
				'{{$i18n.Sat|escape:'quotes'}}'
			],
			allDayText: '{{$i18n.allday|escape:'quotes'}}',
			noEventsMessage: '{{$i18n.noevent|escape:'quotes'}}',
			buttonText: {
				today: '{{$i18n.today|escape:'quotes'}}',
				month: '{{$i18n.month|escape:'quotes'}}',
				week: '{{$i18n.week|escape:'quotes'}}',
				day: '{{$i18n.day|escape:'quotes'}}'
			},
			events: '{{$calendar_api}}',
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			timeFormat: 'H:mm',
			eventClick: function(calEvent) {
				showEvent(calEvent.id);
			},
			loading: function(isLoading) {
				if(!isLoading) {
					$('td.fc-day').dblclick(function() { window.location.href='/calendar/event/new?start=' + $(this).data('date'); });
				}
			},

			eventRender: function(event, element, view) {
				if (event.item['author-name']==null) return;
				switch(view.name){
					case "month":
						element.find(".fc-title").html(
							"{0}".format(
								event.title
							));
						break;
					case "agendaWeek":
						element.find(".fc-title").html(
							"{0}<p>{1}</p><p>{2}</p>".format(
								event.item['author-name'],
								event.item.desc,
								event.item.location
							));
						break;
					case "agendaDay":
						element.find(".fc-title").html(
							"{0}<p>{1}</p><p>{2}</p>".format(
								event.item['author-name'],
								event.item.desc,
								event.item.location
							));
						break;
				}
			}

		})

		// show event popup
		let hash = location.hash.split("-");
		if (hash.length === 2 && hash[0] === "#link") showEvent(hash[1]);
	});
</script>

<script language="javascript" type="text/javascript">
	$(document).ready(function() {
		$("#comment-edit-text-desc").bbco_autocomplete('bbcode');

		$('#id_share').change(function() {

			if ($('#id_share').is(':checked')) {
				$('#acl-wrapper').show();
			}
			else {
				$('#acl-wrapper').hide();
			}
		}).trigger('change');

		$('#contact_allow, #contact_deny, #circle_allow, #circle_deny').change(function() {
			let selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #circle_allow option:selected, #circle_deny option:selected').each( function() {
				selstr = $(this).html();
				$('#jot-public').hide();
			});
			if(selstr == null) {
				$('#jot-public').show();
			}
		}).trigger('change');

		// disable the finish time input if the user disable it
		$('#id_nofinish').change(function() {
			enableDisableFinishDate()
		}).trigger('change');
	});
</script>

