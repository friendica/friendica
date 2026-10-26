{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $latest}}
	<div class="group-list-latest{{if $latest.unseen}} unseen{{/if}}">
		<div class="group-list-latest-meta">
			<a href="{{$latest.author_link}}" title="{{$latest.author}}"><img src="{{$latest.thumb}}" alt="{{$latest.author}}"></a>
			<a href="{{$latest.author_link}}">{{$latest.author}}</a>, <a href="{{$latest.link}}">{{$latest.received}}</a>
		</div>
		<a href="{{$latest.link}}" class="group-list-excerpt">{{$latest.excerpt}}</a>
	</div>
{{else}}
	<div class="group-list-latest">{{$empty}}</div>
{{/if}}
