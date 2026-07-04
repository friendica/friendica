{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!-- NOTE: Place "sidebar-widget-list" only on one element: The one that should be expanded/collapsed -->
<nav id="tagblock" class="tagblock widget">
	<button class="widget-btn fakelink" onclick="openCloseWidget('tagblock');" aria-expanded="false">
		<h3>
				<i class="ri ri-hashtag" aria-hidden="true"></i>
				{{$title}}
		</h3>
	</button>

	<div class="tag-cloud" class="sidebar-widget-list">
		{{foreach $tags as $tag}}
				<a href="{{$tag.url}}" class="tag{{$tag.level}}">#{{$tag.name}}</a>
		{{/foreach}}
	</div>
	<div class="tagblock-widget-end clear"></div>
</nav>
<script>
	initWidget('tagblock');
</script>
