{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="media-usage">
	<h3>{{$title}}</h3>
	{{if $posts}}
		<ul>
			{{foreach $posts as $post}}
			<li><a href="{{$post.url}}">{{$post.created}}</a> {{$post.text}}</li>
			{{/foreach}}
		</ul>
	{{else}}
		<p>{{$no_posts}}</p>
	{{/if}}
</div>
