{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<!-- NOTE: Place "sidebar-widget-list" only on one element: The one that should be expanded/collapsed -->
<nav id='{{$type}}-sidebar' class="widget">
	{{if $type == "channel"}}
		{{assign var="icon" value="ri-broadcast-line"}}
	{{else if $type == "accounttype"}}
		{{assign var="icon" value="ri-shapes-line"}}
	{{else if $type == "rel"}}
		{{assign var="icon" value="ri-arrow-left-right-line"}}
	{{else if $type == "circle"}}
		{{assign var="icon" value="ri-bubble-chart-line"}}
	{{else if $type == "nets"}}
		{{assign var="icon" value="ri-message-2-line"}}
	{{else}} {{* fallback to type="file" *}}
		{{assign var="icon" value="ri-folder-line"}}
	{{/if}}
	<div>
		<!-- TODO: Modify aria-expanded and set it to true/false in JS? -->
		<button class="widget-btn fakelink" onclick="openCloseWidget('{{$type}}-sidebar');" aria-expanded="false">
			<h3>
				<i class="ri {{$icon}}" aria-hidden="true"></i>
				{{$title}}
			</h3>
		</button>
		<div id="{{$type}}-sidebar" class="sidebar-widget-list">
			<div id="{{$type}}-desc">{{$desc nofilter}}</div>
			<ul class="{{$type}}-ul">
				{{if $all_label}}
					<li {{if !is_null($selected) && !$selected}}class="selected" {{/if}}><a href="{{$base}}" class="{{$type}}-link{{if !$selected}} {{$type}}-selected{{/if}} {{$type}}-all">{{$all_label}}</a>
					</li>
				{{/if}}
				{{foreach $options as $option}}
					<li {{if $selected == $option.ref}}class="selected" {{/if}}><a href="{{$base}}{{$type}}={{$option.ref}}" class="{{$type}}-link{{if $selected == $option.ref}} {{$type}}-selected{{/if}}">{{$option.name}}</a>
					</li>
				{{/foreach}}
			</ul>
		</div>
	</div>
</nav>
<script>
	initWidget('{{$type}}-sidebar');
</script>
