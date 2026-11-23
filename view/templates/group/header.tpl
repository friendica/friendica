{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="group-header">
	<a href="{{$header.profile}}" class="group-header-avatar"><img src="{{$header.thumb}}" alt="{{$header.title}}"></a>
	<div>
		<h1><a href="{{$header.profile}}">{{$header.title}}</a></h1>
		<span class="group-header-stat"><strong>{{$header.threads_count}}</strong> {{$header.threads_label}}</span>
		{{if !$header.readonly}}<span class="group-header-stat"><strong>{{$header.unread_count}}</strong> {{$header.unread_label}}</span>{{/if}}
	</div>
</div>
{{if $header.about}}<div class="group-header-about">{{$header.about nofilter}}</div>{{/if}}
