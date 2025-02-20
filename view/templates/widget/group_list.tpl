{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script>
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
<nav id="group-list-sidebar-frame">
	<span id="group-list-sidebar-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('group-list-sidebar', 'group-list-sidebar-inflated');"
			aria-expanded="false">
			<h3>{{$title}}</h3>
		</button>
	</span>
	<div id="group-list-sidebar" class="widget">
		<div id="sidebar-group-header" class="sidebar-widget-header">
			<button class="fakelink" onclick="openCloseWidget('group-list-sidebar', 'group-list-sidebar-inflated');" aria-expanded="true">
				<h3>{{$title}}</h3>
			</button>
			<a class="group-new-tool pull-right widget-action faded-icon" id="sidebar-new-group"
				href="{{$new_group_page}}" data-toggle="tooltip" title="{{$create_new_group}}">
				<i class="fa fa-plus" aria-hidden="true"></i>
			</a>
		</div>
		<div id="sidebar-group-list" class="sidebar-widget-list">
			{{* The list of available groups *}}
			<ul id="group-list-sidebar-ul">
				{{foreach $groups as $group}}
					{{if $group.id <= $visible_groups}}
						<li class="group-widget-entry group-{{$group.cid}}" id="group-widget-entry-{{$group.id}}">
							<span class="notify badge pull-right"></span>
							<a href="{{$group.external_url}}" title="{{$group.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
								<img class="group-list-img" src="{{$group.micro}}" alt="{{$group.link_desc}}" />
							</a>
							<a class="group-widget-link" id="group-widget-link-{{$group.id}}" href="{{$group.url}}">{{$group.name}}</a>
						</li>
					{{/if}}

					{{if $group.id > $visible_groups}}
						<li class="group-widget-entry group-{{$group.cid}}" id="group-widget-entry-extended-{{$group.id}}" style="display: none;">
							<span class="notify badge pull-right"></span>
							<a href="{{$group.external_url}}" title="{{$group.link_desc}}" class="label sparkle" target="_blank" rel="noopener noreferrer">
								<img class="group-list-img" src="{{$group.micro}}" alt="{{$group.link_desc}}" />
							</a>
							<a class="group-widget-link" id="group-widget-link-{{$group.id}}" href="{{$group.url}}">{{$group.name}}</a>
						</li>
					{{/if}}
				{{/foreach}}

				{{if $total > $visible_groups }}
					<li onclick="showHideGroupList(); return false;" id="group-widget-collapse" class="group-widget-link fakelink tool">{{$showmore}}</li>
				{{/if}}
			</ul>
		</div>
	</div>
</nav>
<script>
	initWidget('group-list-sidebar', 'group-list-sidebar-inflated');
</script>