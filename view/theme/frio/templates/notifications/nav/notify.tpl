{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<li class="notification-{{if !$notify.seen}}un{{/if}}seen notif-entry">
	<div class="notif-entry-wrapper media">
		<div class="notif-photo-wrapper media-object pull-left" aria-hidden="true">
			<a href="{{$notify.contact.url}}" class="userinfo click-card" tabIndex="-1"><img data-src="{{$notify.contact.photo}}" alt="" loading="lazy"></a>
		</div>
		<a href="{{$notify.href}}" class="notif-desc-wrapper media-body">
			<div>
			{{$notify.richtext nofilter}}
			<time class="notif-when time" data-toggle="tooltip" title="{{$notify.localdate}}">{{$notify.ago}}</time>
			<div class="clear"></div>
		</a>
	</div>
</li>
