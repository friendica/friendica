{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav>
	<span id="saved-search-list-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('saved-search-list', 'saved-search-list-inflated');" aria-expanded="false">
			<h3>{{$title}}</h3>
		</button>
	</span>
	<div class="widget" id="saved-search-list">
		<button class="fakelink" onclick="openCloseWidget('saved-search-list', 'saved-search-list-inflated');" aria-expanded="true">
			<h3 id="search">{{$title}}</h3>
		</button>
		{{$searchbox nofilter}}

		<ul id="saved-search-ul">
			{{foreach $saved as $search}}
				<li class="saved-search-li clear">
					<a href="search/saved/remove?term={{$search.encodedterm}}&amp;return_url={{$return_url}}" title="{{$search.delete}}" onclick="return confirmDelete();" id="drop-saved-search-term-{{$search.id}}" class="iconspacer savedsearchdrop"></a>
					<a href="{{$search.searchpath}}" id="saved-search-term-{{$search.id}}" class="savedsearchterm">{{$search.term}}</a>
				</li>
			{{/foreach}}
		</ul>
		<div class="clear"></div>
	</div>>
</nav>
<script>
	initWidget('saved-search-list', 'saved-search-list-inflated');
</script>