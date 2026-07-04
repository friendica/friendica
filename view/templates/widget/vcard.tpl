{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="vcard h-card">
	<div class="fn p-name" dir="auto">{{$contact.name}}</div>
	{{if $contact.addr}}<div class="p-addr">{{$contact.addr}}</div>{{/if}}
	{{if $is_admin}}<div class="badge badge-admin"><i class="ri ri-medal-2-fill" aria-hidden="true"></i> {{$admin_title}}</div>{{/if}}
	{{if $is_mod}}<div class="badge badge-mod"><i class="ri ri-shield-user-line" aria-hidden="true"></i> {{$moderator_title}}</div>{{/if}}

	{{if $url}}
	<div id="profile-photo-wrapper"><a href="{{$url}}"><img class="vcard-photo photo u-photo" style="width: 175px; height: 175px;" src="{{$photo}}" alt="{{$name}}" /></a></div>
	{{else}}
	<div id="profile-photo-wrapper"><img class="vcard-photo photo u-photo" style="width: 175px; height: 175px;" src="{{$photo}}" alt="{{$name}}" /></div>
	{{/if}}
	{{if $account_type == 1}}
		{{$acct_icon = "ri-building-4-line"}}
	{{else if $account_type == 2}}
		{{$acct_icon = "ri-newspaper-line"}}
	{{else if $account_type == 3 && $page_flags == 2 || $account_type == 3 && $manually_approve == 0}}
		{{$acct_icon = "ri-team-line"}}
		{{$page_flags = 2}}
	{{else if $account_type == 3 && $page_flags == 5 || $account_type == 3 && $manually_approve == 1 && $private == 1}}
		{{$acct_icon = "ri-spy-line"}}
		{{$page_flags = 5}}
	{{else if $account_type == 3 && $page_flags == 6 || $account_type == 3 && $manually_approve == 1 && $private == 0}}
		{{$acct_icon = "ri-group-3-line"}}
		{{$page_flags = 6}}
	{{else if $account_type == 4}}
		{{$acct_icon = "ri-broadcast-line"}}
	{{else}}
		{{$acct_icon = ''}}
	{{/if}}
	{{if $account_type_name}}<div class="account-type" data-acct="{{$account_type}}" data-flag="{{$page_flags}}">(<i class="ri {{$acct_icon}}" aria-hidden="true"></i> {{$account_type_name}})</div>{{/if}}

	{{if $about}}<div class="title p-about" dir="auto">{{$about nofilter}}</div>{{/if}}
	{{if $contact.xmpp}}
		<dl class="xmpp">
		<dt class="xmpp-label">{{$xmpp}}</dt>
		<dd class="xmpp-data">{{$contact.xmpp}}</dd>
		</dl>
	{{/if}}
	{{if $contact.matrix}}
		<dl class="matrix">
		<dt class="matrix-label">{{$matrix}}</dt>
		<dd class="matrix-data">{{$contact.matrix}}</dd>
		</dl>
	{{/if}}
	{{if $contact.location}}
		<dl class="location" dir="auto">
			<dt class="location-label">{{$location}}</dt>
			<dd class="adr h-adr">
				<p class="p-location">{{$contact.location}}</p>
			</dd>
		</dl>
	{{/if}}
	{{if $network_link}}<dl class="network"><dt class="network-label">{{$network}}</dt><dd class="x-network">{{$network_link nofilter}}</dd></dl>{{/if}}

	<div id="profile-extra-links">
		<ul>
			{{if $follow_link}}
				<li><a id="dfrn-request-link" href="{{$follow_link}}">{{$follow}}</a></li>
			{{/if}}
			{{if $unfollow_link}}
				<li><a id="dfrn-request-link" href="{{$unfollow_link}}">{{$unfollow}}</a></li>
			{{/if}}
			{{if $wallmessage_link}}
				<li><a id="wallmessage-link" href="{{$wallmessage_link}}">{{$wallmessage}}</a></li>
			{{/if}}
			{{if $showgroup_link}}
				<li><a id="showgroup-link" href="{{$showgroup_link}}">{{$showgroup}}</a></li>
			{{/if}}
		</ul>
	</div>

	<div id="profile-vcard-break"></div>
</div>
