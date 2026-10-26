{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<ul role="menubar" class="tabs posts-filter">
	{{foreach $filters as $filter}}
		<li role="menuitem" id="{{$filter.id}}"><a href="{{$filter.url}}" class="tab button {{$filter.sel}}"{{if $filter.title}} title="{{$filter.title}}"{{/if}}>{{$filter.label}}</a></li>
	{{/foreach}}
</ul>
