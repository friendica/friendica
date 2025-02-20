{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav>
	<span id="tagblock-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('tagblock', 'tagblock-inflated');" aria-expanded="false">
			<h3>{{$title}}</h3>
		</button>
	</span>
	<div id="tagblock" class="tagblock widget">
		<button class="fakelink" onclick="openCloseWidget('tagblock', 'tagblock-inflated');" aria-expanded="true">
			<h3>{{$title}}</h3>
		</button>

		<div class="tag-cloud">
			{{foreach $tags as $tag}}
				<span class="tags">
					<span class="tag{{$tag.level}}">#</span><a href="{{$tag.url}}" class="tag{{$tag.level}}">{{$tag.name}}</a>
				</span>
			{{/foreach}}
		</div>
		<div class="tagblock-widget-end clear"></div>
	</div>
</nav>
<script>
	initWidget('tagblock', 'tagblock-inflated');
</script>