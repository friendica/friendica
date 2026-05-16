{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
{{if $warningtext|count}}
	<div id="admin-warning-message-wrapper">
		{{foreach $warningtext as $wt}}
		<p class="warning-message">{{$wt nofilter}}</p>
		{{/foreach}}
	</div>
{{/if}}

	<dl>
		<dt>{{$queues.label}}</dt>
		<dd><a href="{{$baseurl}}/admin/queue/deferred">{{$queues.deferred}}</a> - <a href="{{$baseurl}}/admin/queue">{{$queues.workerq}}</a></dd>
	</dl>

	<dl>
		<dt>{{$addons.0}}</dt>

		{{foreach $addons.1 as $a}}
			<dd><a href="{{$baseurl}}/admin/addons/{{$a.0}}/">{{$a.1.name}}</a></dd>
		{{/foreach}}

	</dl>

	<dl>
		<dt>{{$version_label}}</dt>
		<dd> {{$platform}} '{{$codename}}' {{$VERSION}} - {{$build}}</dt>
	</dl>

	<dl>
		<dt>{{$serversettings.label}}</dt>
		<dd>
			<table>
				<tbody>
					<tr><td colspan="2"><b>PHP</b></td></tr>
					{{foreach $serversettings.php as $k => $p}}
						<tr><td>{{$k}}</td><td>{{$p}}</td></tr>
					{{/foreach}}
					<tr><td colspan="2"><b>MySQL / MariaDB</b></td></tr>
					{{foreach $serversettings.mysql as $k => $p}}
						<tr><td>{{$k}}</td><td>{{$p}}</td></tr>
					{{/foreach}}
				</tbody>
			</table>
		</dd>
	</dl>

</div>
