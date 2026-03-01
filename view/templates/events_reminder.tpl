{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{if $count}}
	<div id="event-notice" class="birthday-notice fakelink {{$classtoday}}" onclick="openClose('event-wrapper');">{{$event_reminders}} ({{$count}})</div>
	<div id="event-wrapper" style="display: none;"><div id="event-title">{{$event_title}}</div>
		<div id="event-title-end"></div>
		{{foreach $events as $event}}
			<div class="event-list" id="event-{{$event.id}}">
				<a data-fancybox href="javascript:" data-type="ajax" data-src="calendar/event/show/{{$event.id}}" href="calendar/event/show/{{$event.id}}">{{$event.title}}</a>
			  - {{$event.date}}
			</div>
		{{/foreach}}
	</div>
{{/if}}

