{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper overview-page">
	<h1>{{$title}}</h1>
	{{if $intro}}<p>{{$intro}}</p>{{/if}}

	{{if $links}}
		<p class="overview-links">
			{{foreach $links as $link}}
				<a href="{{$link.href}}" class="btn btn-default">{{$link.label}}</a>
			{{/foreach}}
		</p>
	{{/if}}

	{{if $add}}
		<form class="overview-add" action="{{$add.action}}" method="{{$add.method}}">
			{{foreach $add.hidden as $hidden}}
				<input type="hidden" name="{{$hidden.name}}" value="{{$hidden.value}}">
			{{/foreach}}
			<input type="text" name="{{$add.name}}" class="form-control" placeholder="{{$add.placeholder}}" aria-label="{{$add.placeholder}}" required>
			<button type="submit" class="btn btn-primary">{{$add.submit}}</button>
		</form>
	{{/if}}

	{{if $items}}
		<input type="search" class="form-control overview-filter" placeholder="{{$filter}}" aria-label="{{$filter}}" oninput="filterOverviewList(this)">
		<ul class="overview-list">
			{{foreach $items as $item}}
				<li class="overview-entry{{if $item.selected}} selected{{/if}}" data-filter-item>
					<div class="overview-title">
						<a href="{{$item.href}}" class="overview-name">{{$item.name}}</a>
						{{if $item.description}}<div class="overview-description">{{$item.description}}</div>{{/if}}
					</div>
					{{if $item.actions}}
						<div class="overview-actions">
							{{foreach $item.actions as $action}}
								<a href="{{$action.href}}" class="overview-action" title="{{$action.label}}"{{if $action.confirm}} onclick="return confirmDelete();"{{/if}}>
									<i class="ri {{$action.icon}}" aria-hidden="true"></i>
									<span class="sr-only">{{$action.label}}</span>
								</a>
							{{/foreach}}
						</div>
					{{/if}}
				</li>
			{{/foreach}}
		</ul>
	{{else}}
		<p>{{$empty}}</p>
	{{/if}}
</div>
