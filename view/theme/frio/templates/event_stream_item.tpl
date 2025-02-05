{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="vevent event-card">
	<div class="vevent-header">
		<div class="event-card-details">
			{{* The part with the event data (start, end, location, title *}}
			<div class="event-card-header">
				{{* On the left we will present the date in short form (e.g. Oct 15) *}}
				<div class="event-card-left-date">
					<span class="event-date-wrapper medium">
						<span class="event-card-short-month">{{$month_short}}</span>
						<span class="event-card-short-date">{{$date_short}}</span>
					</span>
				</div>
				<div class="event-card-content media-body">
					<div class="event-title event-card-title summary event-summary">{{$title nofilter}}</div>

					{{* If there is a map, we insert a button for showing/hiding the map *}}
					{{if $location.map}}<button id="event-map-btn-{{$id}}" class="event-map-btn btn-link fakelink nav nav-pills preferences" data-map-id="event-location-map-{{$id}}" data-show-label="{{$show_map_label}}" data-hide-label="{{$hide_map_label}}">{{$map_btn_label}}</button>{{/if}}
					<div class="event-property">
						<span class="event-date">
							<span class="event-start dtstart" title="{{$dtstart_title}}">{{$start_short}}</span>
							{{if $finish}} - <span class="event-end dtend" title="{{$dtend_title}}">{{if $same_date}}{{$end_time}}{{else}}{{$end_short}}{{/if}}</span>{{/if}}
						</span>
						{{if $location.name}}
						<span aria-hidden="true"> · </span>
						<span class="event-location event-card-location">{{$location.name}}</span>
						{{/if}}
					</div>
					<div class="event-card-profile-name profile-entry-name">
						<a href="{{$author_link}}" class="userinfo hover-card">{{$author_name}}</a>
					</div>
					{{if $location.map}}
					<div id="event-location-map-{{$id}}" class="event-location-map">{{$location.map nofilter}}</div>
					{{/if}}
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
	<div class="clearfix"></div>

	{{* The content of the event description *}}
	{{if $description}}
	<div class="description event-description">
		{{$description nofilter}}
	</div>
	{{/if}}
</div>
