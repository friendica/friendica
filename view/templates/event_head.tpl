
<link rel='stylesheet' type='text/css' href='{{$baseurl}}/library/fullcalendar/fullcalendar.css' />
<script type="text/javascript" src="{{$baseurl}}/library/moment/moment.min.js"></script>
<script type="text/javascript" src="{{$baseurl}}/library/moment/locales.min.js"></script>
<script language="javascript" type="text/javascript" src="{{$baseurl}}/library/fullcalendar/fullcalendar.min.js"></script>

<script>
	function showEvent(eventid) {
		$.get(
			'{{$baseurl}}{{$module_url}}/?id='+eventid,
			function(data){
				$.colorbox({html:data});
			}
		);
	}

	function doEventPreview() {
		$('#event-edit-preview').val(1);
		$.post('events',$('#event-edit-form').serialize(), function(data) {
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
			events: '{{$baseurl}}{{$module_url}}/json/',
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			timeFormat: 'H(:mm)',
			eventClick: function(calEvent, jsEvent, view) {
				showEvent(calEvent.id);
			},
			loading: function(isLoading, view) {
				if(!isLoading) {
					$('td.fc-day').dblclick(function() { window.location.href='/events/new?start='+$(this).data('date'); });
				}
			},

			eventRender: function(event, element, view) {
				if (event.item['author-name']==null) return;
				switch(view.name){
					case "month":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:10px;width:10px'>{1} : {2}".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.title
					));
					break;
					case "agendaWeek":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:12px; width:12px'>{1}<p>{2}</p><p>{3}</p>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.item.desc,
							event.item.location
					));
					break;
					case "agendaDay":
					element.find(".fc-title").html(
						"<img src='{0}' style='height:24px;width:24px'>{1}<p>{2}</p><p>{3}</p>".format(
							event.item['author-avatar'],
							event.item['author-name'],
							event.item.desc,
							event.item.location
					));
					break;
				}
			}

		})

		// center on date
		var args=location.href.replace(baseurl,"").split("/");
{{if $modparams == 2}}
		if (args.length>=5) {
			$("#events-calendar").fullCalendar('gotoDate',args[3] , args[4]-1);
		}
{{else}}
		if (args.length>=4) {
			$("#events-calendar").fullCalendar('gotoDate',args[2] , args[3]-1);
		}
{{/if}}

		// show event popup
		var hash = location.hash.split("-")
		if (hash.length==2 && hash[0]=="#link") showEvent(hash[1]);

	});
</script>

{{if $editselect != 'none'}}
<script language="javascript" type="text/javascript"
	  src="{{$baseurl}}/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js"></script>
<script language="javascript" type="text/javascript">

	tinyMCE.init({
		theme : "advanced",
		mode : "textareas",
		plugins : "bbcode,paste",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "center",
		theme_advanced_blockformats : "blockquote,code",
		theme_advanced_resizing : true,
		gecko_spellcheck : true,
		paste_text_sticky : true,
		entity_encoding : "raw",
		add_unload_trigger : false,
		remove_linebreaks : false,
		forced_root_block : 'div',
		content_css: "{{$baseurl}}/view/custom_tinymce.css",
		theme_advanced_path : false,
		setup : function(ed) {
			ed.onInit.add(function(ed) {
				ed.pasteAsPlainText = true;
			});
		}

	});

	$(document).ready(function() {
		$('.comment-edit-bb').hide();
	});
{{else}}
	<script language="javascript" type="text/javascript">
{{/if}}

	$(document).ready(function() {
{{if $editselect = 'none'}}
		$("#comment-edit-text-desc").bbco_autocomplete('bbcode');
{{/if}}

		$('#id_share').change(function() {

			if ($('#id_share').is(':checked')) {
				$('#acl-wrapper').show();
			}
			else {
				$('#acl-wrapper').hide();
			}
		}).trigger('change');

		$('#contact_allow, #contact_deny, #group_allow, #group_deny').change(function() {
			var selstr;
			$('#contact_allow option:selected, #contact_deny option:selected, #group_allow option:selected, #group_deny option:selected').each( function() {
				selstr = $(this).text();
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

