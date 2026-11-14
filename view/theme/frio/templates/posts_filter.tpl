{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="panel posts-filter">
<ul class="nav nav-tabs">
	{{foreach $filters as $filter}}
		<li id="{{$filter.id}}" class="{{$filter.sel}}"><a href="{{$filter.url}}"{{if $filter.title}} title="{{$filter.title}}"{{/if}}>{{$filter.label}}</a></li>
	{{/foreach}}
</ul>
</div>
