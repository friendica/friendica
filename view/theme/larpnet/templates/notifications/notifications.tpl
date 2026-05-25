{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script type="text/javascript" src="../../frameworks/jquery-color/jquery.color.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="../../js/mod_notifications.js?v={{$VERSION}}"></script>

<div class="generic-page-wrapper">
	{{include file="section_title.tpl" title=$header}}

	{{if $tabs }}{{include file="common_tabs.tpl"}}{{/if}}

	<div class="notif-network-wrapper">
		{{* The "show ignored" link *}}
		{{if $showLink}}<a href="{{$showLink.href}}" id="notifications-show-hide-link">{{$showLink.text}}</a>{{/if}}

		{{* The notifications *}}
		{{if $notifications}}
		<ul class="notif-network-list media-list">
		{{foreach $notifications as $notification}}
			<li>{{$notification nofilter}}</li>
		{{/foreach}}
		</ul>
		{{/if}}

		{{* If no notifications messages available *}}
		{{if $noContent}}
		<div class="notification_nocontent">{{$noContent}}</div>
		{{/if}}
	</div>

	{{* The pager *}}
	{{$paginate nofilter}}
</div>
