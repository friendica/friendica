{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="event-wrapper">
	<div class="event">
		<div class="media">
			<div class="event-owner media-left">
				{{if $event.item.author_name}}
				<a href="{{$event.item.author_link}}"><img src="{{$event.item.author_avatar}}" /></a>
				<a href="{{$event.item.author_link}}">{{$event.item.author_name}}</a>
				{{/if}}
			</div>
			<div class="media-body">
				{{$event.html nofilter}}
			</div>
		</div>

		<div class="event-buttons">
			{{if $event.edit}}
				<a class="btn btn-primary" href="{{$event.edit.0}}">
					<i class="ri ri-pencil-line" aria-hidden="true"></i>
					{{$event.edit.1}}
				</a>
			{{/if}}
			{{if $event.copy}}
				<a class="btn btn-default" href="{{$event.copy.0}}">
					<i class="ri ri-file-copy-line" aria-hidden="true"></i>
					{{$event.copy.1}}
				</a>
				{{/if}}
			{{if $event.drop}}
				<a href="{{$event.drop.0}}" onclick="return confirmDelete();" class="drop-event-link btn btn-default">
					<i class="ri ri-delete-bin-line" aria-hidden="true"></i>
					{{$event.drop.1}}
				</a>
			{{/if}}
			{{if $event.plink.orig}}
				<a href="{{$event.plink.orig}}" class="plink-event-link btn btn-primary pull-right" aria-label="{{$event.plink.1}}">
					<i class="ri ri-external-link-line" aria-hidden="true"></i>
					{{$event.plink.orig_title}}
				</a>
			{{/if}}
		</div>
		<div class="clear"></div>
	</div>
</div>
