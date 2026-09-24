{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<p>
		<a href="groups/discover" class="btn btn-default"><i class="ri ri-compass-3-line" aria-hidden="true"></i> {{$discover}}</a>
	</p>
	<h1>{{$title}}</h1>

	{{if !$groups}}
		<p>{{$no_groups}}</p>
	{{else}}
		{{foreach $groups as $server}}
		<div class="group-list-server">
			<h2>
				<span title="{{$server.host}}">{{$server.name}}</span>
				{{if $server.gsid}}<a href="groups/discover/{{$server.gsid}}" class="group-list-discover" title="{{$discover_server}}"><i class="ri ri-compass-3-line" aria-hidden="true"></i> {{$discover_short}}</a>{{/if}}
			</h2>
			{{if $server.info}}<p>{{$server.info}}</p>{{/if}}
		</div>
		<ul class="group-list">
			{{foreach $server.groups as $group}}
			<li class="group-list-entry">
				<a href="{{$group.profile}}" class="group-list-avatar"><img src="{{$group.thumb}}" alt="{{$group.name}}"></a>
				<div class="group-list-title">
					<a href="{{$group.link}}" class="group-list-name">{{$group.name}}</a>
					<div class="group-list-about group-list-description">{{$group.about}}</div>
				</div>
				<div class="group-list-stats">
					<div class="group-list-stat"><span class="group-list-count">{{$group.threads}}</span> {{$threads}}</div>
					<div class="group-list-stat{{if $group.unread}} group-list-stat-unread{{/if}}"><span class="group-list-count">{{$group.unread}}</span> {{$unread}}</div>
				</div>
				{{include file="group/latest.tpl" latest=$group.latest empty=$no_posts}}
			</li>
			{{/foreach}}
		</ul>
		{{/foreach}}
	{{/if}}
</div>
