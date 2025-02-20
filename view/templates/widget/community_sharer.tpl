{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav>
	<span id="sidebar-community-no-sharer-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('sidebar-community-no-sharer', 'sidebar-community-no-sharer-inflated');" aria-expanded="false">
			<h3>{{$title}}</h3>
		</button>
	</span>
	<div id="sidebar-community-no-sharer" class="widget">
		<button class="fakelink" onclick="openCloseWidget('sidebar-community-no-sharer', 'sidebar-community-no-sharer-inflated');" aria-expanded="true">
			<h3>{{$title}}</h3>
		</button>
		<ul class="sidebar-community-no-sharer-ul">
			<li class="sidebar-community-no-sharer-li{{if !$no_sharer}} selected{{/if}}"><a href="{{$base}}/{{$path_all}}">{{$all}}</a></li>
			<li class="sidebar-community-no-sharer-li{{if $no_sharer}} selected{{/if}}"><a href="{{$base}}/{{$path_no_sharer}}">{{$no_sharer_label}}</a></li>
		</ul>
	</div>
</nav>
<script>
	initWidget('sidebar-community-no-sharer', 'sidebar-community-no-sharer-inflated');
</script>