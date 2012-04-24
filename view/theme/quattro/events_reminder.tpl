<link rel='stylesheet' type='text/css' href='$baseurl/library/fullcalendar/fullcalendar.css' />
<script language="javascript" type="text/javascript"
          src="$baseurl/library/fullcalendar/fullcalendar.min.js"></script>
<script>
	// start calendar from yesterday
	var yesterday= new Date()
	yesterday.setDate(yesterday.getDate()-1)
	
	function showEvent(eventid) {
		$.get(
			'$baseurl/events/?id='+eventid,
			function(data){
				$.fancybox(data);
			}
		);			
	}
	$(document).ready(function() {
		$('#events-reminder').fullCalendar({
			firstDay: yesterday.getDay(),
			year: yesterday.getFullYear(),
			month: yesterday.getMonth(),
			date: yesterday.getDate(),
			events: '$baseurl/events/json/',
			header: {
				left: '',
				center: '',
				right: ''
			},			
			timeFormat: 'H(:mm)',
			defaultView: 'basicWeek',
			height: 50,
			eventClick: function(calEvent, jsEvent, view) {
				showEvent(calEvent.id);
			}
		});
	});
</script>
<div id="events-reminder"></div>
<br>
