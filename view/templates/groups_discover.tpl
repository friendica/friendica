{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<p>
		<a href="groups" class="btn btn-default" data-spa-back><i class="ri ri-arrow-left-line" aria-hidden="true"></i> {{$back}}</a>
	</p>
	<h1>{{$title}}</h1>
	{{if $info}}<p>{{$info}}</p>{{/if}}

	<form id="group-discover-search" action="{{$action}}" method="get">
		<div class="input-group">
			<input type="text" name="search" class="form-control" value="{{$search}}" placeholder="{{$find_desc}}" aria-label="{{$find_desc}}">
			<span class="input-group-btn">
				<button type="submit" class="btn btn-default"><i class="ri ri-search-line" aria-hidden="true"></i> {{$find}}</button>
			</span>
		</div>
	</form>

	{{if !$groups}}
		<p>{{$no_groups}}</p>
	{{else}}
		<table id="group-discover" class="table">
			<thead>
				<tr>
					<th colspan="2">{{$group}}</th>
					<th>{{$latest}}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{{foreach $groups as $group}}
				<tr>
					<td class="group-overview-avatar">
						<a href="{{$group.link}}"><img src="{{$group.thumb}}" alt="{{$group.name}}"></a>
					</td>
					<td class="group-overview-title">
						<a href="{{$group.link}}"><strong>{{$group.name}}</strong></a>
						{{if $group.private}}<i class="ri ri-lock-line" title="{{$private}}" aria-label="{{$private}}"></i>{{/if}}
						{{if $group.server}}<div class="group-discover-server" title="{{$group.host}}">{{$group.server}}</div>{{/if}}
						<div>{{$group.about}}</div>
					</td>
					<td class="group-overview-latest">{{$group.latest}}</td>
					<td class="group-discover-join">
						<a href="{{$group.follow}}" class="btn btn-default"><i class="ri ri-user-add-line" aria-hidden="true"></i> {{$join}}</a>
					</td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
	{{/if}}

	{{$paginate nofilter}}
</div>
