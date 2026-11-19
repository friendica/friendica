{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>

	{{if $single && $single.info}}<p>{{$single.info}}</p>{{/if}}

	<form method="post" action="{{$action}}" class="group-toolbar" up-submit>
		<input type="hidden" name="form_security_token" value="{{$form_token}}">
		<span class="group-toolbar-start">
			{{if $single}}<a href="groups" class="btn btn-default" data-spa-back><i class="ri ri-arrow-left-line" aria-hidden="true"></i> {{$back}}</a>{{/if}}
			<a href="{{$discover_link}}" class="btn btn-default"><i class="ri ri-compass-3-line" aria-hidden="true"></i> {{$discover}}</a>
		</span>
		{{if $groups}}<button type="submit" class="btn btn-default"><i class="ri ri-check-double-line" aria-hidden="true"></i> {{$mark_seen}}</button>{{/if}}
	</form>

	{{if !$groups}}
		<p>{{$no_groups}}</p>
	{{else}}
		<input type="search" class="form-control overview-filter" placeholder="{{$filter}}" aria-label="{{$filter}}">
		{{foreach $groups as $server}}
		{{if !$single}}
		<div class="group-list-server">
			<h2><a href="groups/{{$server.host}}" title="{{$server.host}}">{{$server.name}}</a></h2>
			{{if $server.info}}<p>{{$server.info}}</p>{{/if}}
		</div>
		{{/if}}
		<ul class="group-list">
			{{foreach $server.groups as $group}}
			<li class="group-list-entry" data-filter-item>
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
