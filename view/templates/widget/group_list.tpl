{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!-- NOTE: Place "sidebar-widget-list" only on one element: The one that should be expanded/collapsed -->
<script>
	/* TODO: Consider making a reusable version of this in e.g. main.js instead */
	function showHideGroupList() {
		if ($("li[id^='group-widget-entry-extended-']").is(':visible')) {
			$("li[id^='group-widget-entry-extended-']").hide();
			$("li#group-widget-collapse").html('{{$showmore}}');

		} else {
			$("li[id^='group-widget-entry-extended-']").show();
			$("li#group-widget-collapse").html('{{$showless}}');
		}
	}
</script>
<nav id="group-sidebar" class="widget">
	<button class="widget-btn fakelink" onclick="openCloseWidget('group-sidebar');" aria-expanded="false">
		<h3>
			<i class="ri ri-discuss-line" aria-hidden="true"></i>
			{{$title}}
		</h3>
	</button>
	<a class="pull-right widget-action widget-action-top faded-icon" id="sidebar-new-group"
		href="{{$new_group_page}}" data-toggle="tooltip" title="{{$create_new_group}}">
		<i class="ri ri-add-line" aria-hidden="true"></i>
	</a>
	{{if $addon_group_directory_enabled}}
		<a class="pull-right widget-action widget-action-top faded-icon" id="sidebar-group-directory"
			href="/groupdirectory" data-toggle="tooltip" title="{{$visit_groupdirectory}}">
			<i class="ri ri-search-line" aria-hidden="true"></i>
		</a>
	{{/if}}

	<div id="group-list-sidebar" class="sidebar-widget-list">
		{{* The list of available groups *}}
		<ul id="group-list-sidebar-ul">
			{{foreach $groups as $group}}
				{{if $group.id <= $visible_groups}}
					<li class="group-widget-entry group-{{$group.cid}}" id="group-widget-entry-{{$group.id}}">
						<span class="notify badge pull-right"></span>
						<a href="{{$group.external_url}}" title="{{$group.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
							<img src="{{$group.micro}}" alt="{{$group.link_desc}}" />
						</a>
						<a class="group-widget-link" id="group-widget-link-{{$group.id}}" href="{{$group.url}}">{{$group.name}}</a>
					</li>
				{{/if}}

				{{if $group.id > $visible_groups}}
					<li class="group-widget-entry group-{{$group.cid}}" id="group-widget-entry-extended-{{$group.id}}" style="display: none;">
						<span class="notify badge pull-right"></span>
						<a href="{{$group.external_url}}" title="{{$group.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
							<img src="{{$group.micro}}" alt="{{$group.link_desc}}" />
						</a>
						<a class="group-widget-link" id="group-widget-link-{{$group.id}}" href="{{$group.url}}">{{$group.name}}</a>
					</li>
				{{/if}}
			{{/foreach}}

			{{if $total > $visible_groups }}
				<li onclick="showHideGroupList(); return false;" id="group-widget-collapse" class="group-widget-link widget-show-more fakelink tool">{{$showmore}}</li>
			{{/if}}
		</ul>
	</div>
</nav>
<script>
	initWidget('group-sidebar');
</script>
