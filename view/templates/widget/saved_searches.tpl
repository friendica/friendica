{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!-- NOTE: Place "sidebar-widget-list" only on one element: The one that should be expanded/collapsed -->
{{* Overridden by Frio *}}
<nav id="saved-search-sidebar" class="widget">
	<button class="widget-btn fakelink" onclick="openCloseWidget('saved-search-sidebar');" aria-expanded="false">
		<h3 id="search">
			<i class="ri ri-search-line" aria-hidden="true"></i>
			{{$title}}
		</h3>
	</button>
	{{$searchbox nofilter}}

	<ul id="saved-search-list" class="sidebar-widget-list">
		{{foreach $saved as $search}}
			<li class="saved-search-li clear">
				<a href="search/saved/remove?term={{$search.encodedterm}}&amp;return_url={{$return_url}}" title="{{$search.delete}}" onclick="return confirmDelete();" id="drop-saved-search-term-{{$search.id}}" class="iconspacer savedsearchdrop"></a>
				<a href="{{$search.searchpath}}" id="saved-search-term-{{$search.id}}" class="savedsearchterm">{{$search.term}}</a>
			</li>
		{{/foreach}}
	</ul>
</nav>
<script>
	initWidget('saved-search-sidebar');
</script>
