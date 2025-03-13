{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<nav>
	<span id="sidebar-accounts-inflated" class="widget inflated fakelink">
		<button class="fakelink" onclick="openCloseWidget('sidebar-accounts', 'sidebar-accounts-inflated');" aria-expanded="false">
			<h3>{{$title}}</h3>
		</button>
	</span>
	<div id="sidebar-accounts" class="widget">
		<button class="fakelink" onclick="openCloseWidget('sidebar-accounts', 'sidebar-accounts-inflated');" aria-expanded="true">
			<h3>{{$title}}</h3>
		</button>
		<ul class="sidebar-accounts-ul">
			<li class="sidebar-accounts-li{{if !$accounttype}} selected{{/if}}"><a href="{{$content}}">{{$all}}</a></li>
			<li class="sidebar-accounts-li{{if $accounttype == 'person'}} selected{{/if}}"><a href="{{$content}}/person">{{$person}}</a></li>
			<li class="sidebar-accounts-li{{if $accounttype == 'organisation'}} selected{{/if}}"><a href="{{$content}}/organisation">{{$organisation}}</a></li>
			<li class="sidebar-accounts-li{{if $accounttype == 'news'}} selected{{/if}}"><a href="{{$content}}/news">{{$news}}</a></li>
			<li class="sidebar-accounts-li{{if $accounttype == 'community'}} selected{{/if}}"><a href="{{$content}}/community">{{$community}}</a></li>
		</ul>
	</div>
</nav>
<script>
	initWidget('sidebar-accounts', 'sidebar-accounts-inflated');
</script>