{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	{{$tabs nofilter}}
	{{include file="section_title.tpl" title=$title pullright=1}}

	{{* The link to create a new event *}}
	{{if $new_event.0}}
	<div class="pull-right" id="new-event-link">
		<button type="button" class="btn-link page-action faded-icon" onclick="addToModal('{{$new_event.0}}')" title="{{$new_event.1}}" data-toggle="tooltip">
			<i class="fa fa-plus"></i>
		</button>
	</div>
	{{/if}}

	{{* We create our own fullcalendar header (with title & calendar view *}}
	<div id="fc-header" class="clear">
		<div id="fc-header-right" class="pull-right">
			{{* The dropdown to change the calendar view *}}
			<ul class="nav nav-pills">
				<li class="dropdown pull-right">
					<button class="btn btn-link dropdown-toggle" type="button" id="event-calendar-views" data-toggle="dropdown" aria-expanded="false">
						<i class="fa fa-angle-down" aria-hidden="true"></i> {{$view}}
					</button>
					<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="event-calendar-views">
						<li role="presentation">
							<button role="menuitem" type="button" class="btn-link" onclick="changeView('changeView', 'month');$('#events-calendar').fullCalendar('option', {contentHeight: '', aspectRatio: 1});">{{$month}}</button>
						</li>
						<li role="presentation">
							<button role="menuitem" type="button" class="btn-link" onclick="changeView('changeView', 'agendaWeek');$('#events-calendar').fullCalendar('option', 'contentHeight', 'auto');">{{$week}}</button>
						</li>
						<li role="presentation">
							<button role="menuitem" type="button" class="btn-link" onclick="changeView('changeView', 'agendaDay');$('#events-calendar').fullCalendar('option', 'contentHeight', 'auto');">{{$day}}</button>
						</li>
						<li role="presentation">
							<button role="menuitem" type="button" class="btn-link" onclick="changeView('changeView', 'listMonth');$('#events-calendar').fullCalendar('option', 'contentHeight', 'auto');">{{$list}}</button>
						</li>
					</ul>
				</li>
			</ul>
		</div>

		{{* The buttons to change the month/weeks/days *}}
		<div id="fc-fc-header-left" class="btn-group">
			<button class="btn btn-eventnav" onclick="changeView('prev', false);" title="{{$prev}}"><i class="fa fa-angle-up" aria-hidden="true"></i></button>
			<button class="btn btn-eventnav btn-separator" onclick="changeView('next', false);" title="{{$next}}"><i class="fa fa-angle-down" aria-hidden="true"></i></button>
			<button class="btn btn-eventnav btn-separator" onclick="changeView('today', false);" title="{{$today}}"><i class="fa fa-bullseye" aria-hidden="true"></i></button>
		</div>

		{{* The title (e.g. name of the mont/week/day) *}}
		<div id="event-calendar-title"><h4 id="fc-title"></h4></div>

	</div>

	{{* This is the container where the fullCalendar is inserted through js *}}
	<div id="events-calendar"></div>
</div>
