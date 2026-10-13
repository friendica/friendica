{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav id="sidebar-menu" class="widget">
	<ul class="sidebar-menu-list">
		{{foreach $entries as $entry}}
			<li class="sidebar-menu-entry{{if $entry.selected}} selected{{/if}}">
				<a href="{{$entry.url}}"{{if $entry.selected}} aria-current="page"{{/if}}>
					<i class="ri {{$entry.icon}}" aria-hidden="true"></i>
					{{$entry.label}}
				</a>
			</li>
		{{/foreach}}
	</ul>
</nav>
