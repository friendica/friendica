{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav>
	<span id="trending-tags-sidebar-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('trending-tags-sidebar', 'trending-tags-sidebar-inflated');" aria-expanded="false">
			<h3>{{$title}}</h3>
		</button>
	</span>
	<div id="trending-tags-sidebar" class="widget">
		<button class="fakelink" onclick="openCloseWidget('trending-tags-sidebar', 'trending-tags-sidebar-inflated');">
			<h3>{{$title}}</h3>
		</button>
		<ul id="tags-list" style="list-style-type: none; padding: 0; margin: 0;">
			{{section name=ol loop=$tags max=10}}
				<li style="margin-bottom: 5px;">
					<a href="search?tag={{$tags[ol].term}}" style="text-decoration: none; color: inherit;">
						<i class="fa fa-hashtag" aria-hidden="true"></i> {{$tags[ol].term}}
					</a>
				</li>
			{{/section}}
		</ul>
		{{if $tags|count > 10}}
			<div style="text-align: left; margin-top: 10px;">
				<a href="#" onclick="toggleTags(event)" role="button" aria-expanded="false" aria-controls="more-tags" style="text-decoration: none; color: inherit; cursor: pointer; display: inline-flex; align-items: center; font-weight: bold;">
					<i id="caret-icon" class="fa fa-caret-right" aria-hidden="true" style="margin-right: 5px;"></i>
					<span id="link-text">Show More</span>
				</a>
			</div>
			<ul id="more-tags" style="display:none; list-style-type: none; padding: 0; margin: 0;">
				{{section name=ul loop=$tags start=10}}
					<li style="margin-bottom: 5px;">
						<a href="search?tag={{$tags[ul].term}}" style="text-decoration: none; color: inherit;">
							<i class="fa fa-hashtag" aria-hidden="true"></i> {{$tags[ul].term}}
						</a>
					</li>
				{{/section}}
			</ul>
		{{/if}}
	</div>
</nav>

<script>
	function toggleTags(event) {
		event.preventDefault();
		var moreTags = document.getElementById('more-tags');
		var link = event.target.closest('a');
		var caretIcon = document.getElementById('caret-icon');
		var linkText = document.getElementById('link-text');

		if (moreTags.style.display === 'none') {
			moreTags.style.display = 'block';
			linkText.textContent = 'Show Less';
			link.setAttribute('aria-expanded', 'true');
			caretIcon.className = 'fa fa-caret-down';
		} else {
			moreTags.style.display = 'none';
			linkText.textContent = 'Show More';
			link.setAttribute('aria-expanded', 'false');
			caretIcon.className = 'fa fa-caret-right';
		}
	}

	initWidget('trending-tags-sidebar', 'trending-tags-sidebar-inflated');
</script>