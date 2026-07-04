{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!-- NOTE: Place "sidebar-widget-list" only on one element: The one that should be expanded/collapsed -->
<nav id="community-no-sharer-sidebar" class="widget fakelink">
	<button class="widget-btn fakelink" onclick="openCloseWidget('community-no-sharer-sidebar');" aria-expanded="false">
		<h3>
			<i class="ri ri-user-line" aria-hidden="true"></i>
			{{$title}}
		</h3>
	</button>
	<ul class="sidebar-community-no-sharer-ul sidebar-widget-list">
		<li class="sidebar-community-no-sharer-li{{if !$no_sharer}} selected{{/if}}"><a href="{{$base}}/{{$path_all}}">{{$all}}</a></li>
		<li class="sidebar-community-no-sharer-li{{if $no_sharer}} selected{{/if}}"><a href="{{$base}}/{{$path_no_sharer}}">{{$no_sharer_label}}</a></li>
	</ul>
</nav>
<script>
	initWidget('community-no-sharer-sidebar');
</script>
