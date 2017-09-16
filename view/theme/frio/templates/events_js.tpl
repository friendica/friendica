<div class="generic-page-wrapper">
	{{$tabs}}
	{{include file="section_title.tpl" title=$title}}

	{{* The link to create a new event *}}
	<div id="new-event-link"><button type="button" class="btn-link" onclick="addToModal('{{$new_event.0}}')" >{{$new_event.1}}</button></div>

	{{* We create our own fullcallendar header (with title & calendar view *}}
	<div id="fc-header">
		<div id="fc-header-right" class="pull-right">
			{{* The dropdown to change the callendar view *}}
			<ul class="nav nav-pills">
				<li class="dropdown pull-right">
					<button type="button" class="btn btn-link btn-sm dropdown-toggle" type="button" id="event-calendar-views" data-toggle="dropdown" aria-expanded="true">
						<i class="fa fa-angle-down"></i> {{$view}}
					</button>
					<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="event-calendar-views">
						<li role="menuitem">
							<button type="button" class="btn-link" onclick="changeView('changeView', 'month');$('#events-calendar').fullCalendar('option', {contentHeight: '', aspectRatio: 1});">{{$month}}</button>
						</li>
						<li role="menuitem">
							<button type="button" class="btn-link" onclick="changeView('changeView', 'agendaWeek');$('#events-calendar').fullCalendar('option', 'contentHeight', 'auto');">{{$week}}</button>
						</li>
						<li role="menuitem">
							<button type="button" class="btn-link" onclick="changeView('changeView', 'agendaDay');$('#events-calendar').fullCalendar('option', 'contentHeight', 'auto');">{{$day}}</button>
						</li>
						<li role="menuitem">
							<button type="button" class="btn-link" onclick="changeView('changeView', 'listMonth');$('#events-calendar').fullCalendar('option', 'contentHeight', 'auto');">{{$list}}</button>
						</li>
					</ul>
				</li>
			</ul>
		</div>

		{{* The buttons to change the month/weeks/days *}}
		<div id="fc-fc-header-left" class="btn-group">
			<button class="btn btn-eventnav" onclick="changeView('prev', false);" title="{{$previous.1}}"><i class="fa fa-angle-up" aria-hidden="true"></i></i></button>
			<button class="btn btn-eventnav btn-separator" onclick="changeView('next', false);" title="{{$next.1}}"><i class="fa fa-angle-down" aria-hidden="true"></i></i></button>
			<button class="btn btn-eventnav btn-separator" onclick="changeView('today', false);" title="{{$today}}"><i class="fa fa-bullseye"></i></button>
		</div>

		{{* The title (e.g. name of the mont/week/day) *}}
		<div id="event-calendar-title"><h4 id="fc-title"></h4></div>

	</div>

	{{* This is the container where the fullCalendar is inserted through js *}}
	<div id="events-calendar"></div>
</div>
