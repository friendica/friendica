{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!-- NOTE: Place "sidebar-widget-list" only on one element: The one that should be expanded/collapsed -->
<nav id="trending-tags-sidebar" class="widget">
	<!-- TODO: To myself: Figure out if the button element should contain all the widget actions or not -->
	<button class="widget-btn fakelink" style="display: flex; justify-content: space-between; width: 100%;" onclick="openCloseWidget('trending-tags-sidebar');" aria-expanded="false">
		<h3>
				<i class="ri ri-hashtag" aria-hidden="true"></i>
			{{$title}}
		</h3>
		<small>{{$subtitle}}</small>
	</button>
	<div id="trending-tags-list" class="sidebar-widget-list">
		<ul id="tags-list">
			{{section name=ol loop=$tags max=10}}
				<li style="margin-bottom: 5px;">
					<a href="search?tag={{$tags[ol].term}}" style="text-decoration: none; color: inherit;">
						<i class="ri ri-hashtag" aria-hidden="true"></i> {{$tags[ol].term}}
					</a>
				</li>
			{{/section}}
		</ul>
		{{if $tags|count > 10}}
			<div style="text-align: left; margin-top: 10px;">
				<a href="#" onclick="toggleTags(event)" role="button" aria-expanded="false" aria-controls="more-tags" style="text-decoration: none; color: inherit; cursor: pointer; display: inline-flex; align-items: center; font-weight: bold;">
				<i id="caret-icon" class="ri ri-arrow-right-s-line" aria-hidden="true" style="margin-right: 5px;"></i>
					<span id="link-text">{{$showmore}}</span>
				</a>
			</div>
			<ul id="more-tags" style="display:none;">
				{{section name=ul loop=$tags start=10}}
					<li style="margin-bottom: 5px;">
						<a href="search?tag={{$tags[ul].term}}" style="text-decoration: none; color: inherit;">
							<i class="ri ri-hashtag" aria-hidden="true"></i> {{$tags[ul].term}}
						</a>
					</li>
				{{/section}}
			</ul>
		{{/if}}
	</div>
</nav>

<script>
	/* TODO: Consider generalising and moving to e.g. main.js for reuse by any widget */
	function toggleTags(event) {
		event.preventDefault();
		var moreTags = document.getElementById('more-tags');
		var link = event.target.closest('a');
		var caretIcon = document.getElementById('caret-icon');
		var linkText = document.getElementById('link-text');

		if (moreTags.style.display === 'none') {
			moreTags.style.display = 'block';
			linkText.textContent = '{{$showless}}';
			link.setAttribute('aria-expanded', 'true');
				caretIcon.className = 'ri ri-arrow-down-s-line';
			} else {
				moreTags.style.display = 'none';
				linkText.textContent = '{{$showmore}}';
				link.setAttribute('aria-expanded', 'false');
				caretIcon.className = 'ri ri-arrow-right-s-line';
		}
	}

	initWidget('trending-tags-sidebar');
</script>
