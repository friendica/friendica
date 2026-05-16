{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav>
	{{if $type == "channel"}}
		{{assign var="icon" value="fa-th-large"}}
	{{else if $type == "accounttype"}}
		{{assign var="icon" value="fa-umbrella"}}
	{{else if $type == "rel"}}
		{{assign var="icon" value="fa-exchange"}}
	{{else if $type == "circle"}}
		{{assign var="icon" value="fa-user-circle"}}
	{{else}} {{* fallback to type="file" *}}
		{{assign var="icon" value="fa-folder"}}
	{{/if}}
	<span id="{{$type}}-sidebar-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('{{$type}}-sidebar', '{{$type}}-sidebar-inflated');" aria-expanded="false">
			<h3>
				<i class="fa {{$icon}}" aria-hidden="true"></i>
				{{$title}}
			</h3>
		</button>
	</span>
	<div id="{{$type}}-sidebar" class="widget">
		<button class="fakelink" onclick="openCloseWidget('{{$type}}-sidebar', '{{$type}}-sidebar-inflated');" aria-expanded="true">
			<h3>
				<i class="fa {{$icon}}" aria-hidden="true"></i>
			{{$title}}
			</h3>
		</button>
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
</nav>
<script>
	initWidget('{{$type}}-sidebar', '{{$type}}-sidebar-inflated');
</script>
