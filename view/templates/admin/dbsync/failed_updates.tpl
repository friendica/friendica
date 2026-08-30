{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id="adminpage">
	<h2>{{$banner}}</h2>
	<div class="settings-section">
	<div id="failed_updates_desc">{{$desc nofilter}}</div>

	{{if $failed}}
	{{foreach $failed as $f}}
		<h4>{{$f}}</h4>

		<ul>
			<li><a href="{{$baseurl}}/admin/dbsync/mark/{{$f}}">{{$mark}}</a></li>
			<li><a href="{{$baseurl}}/admin/dbsync/update/{{$f}}">{{$apply}}</a></li>
		</ul>

		<hr />
	{{/foreach}}
	{{/if}}
	</div>
</div>
