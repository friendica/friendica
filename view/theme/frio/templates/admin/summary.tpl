{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id='adminpage-summary' class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	{{if $warningtext|count}}
	<div id="admin-warning-message-wrapper" class="alert alert-warning">
		{{foreach $warningtext as $wt}}
		<p>{{$wt nofilter}}</p>
		{{/foreach}}
	</div>
	{{/if}}

	<div id="admin-summary-wrapper">
		{{* The work queues short statistic and the friendica version. *}}
		<table id="admin-summary-queues" class="table">
			<tr>
				<td>{{$queues.label}}</td>
				<td class="admin-summary-entry"><a href="{{$baseurl}}/admin/queue/deferred">{{$queues.deferred}}</a> - <a href="{{$baseurl}}/admin/queue">{{$queues.workerq}}</a></td>
			</tr>
			<tr>
				<td>{{$version_label}}</td>
				<td class="admin-summary-entry">{{$platform}} '{{$codename}}' {{$VERSION}} - {{$build}}</td>
			</tr>
		</table>

		{{* List enabled addons. *}}
		<div id="admin-summary-addons">
			<h2>{{$addons.0}}</h2>
			<table class="table">
				{{foreach $addons.1 as $a}}
				<tr>
					<td><a href="{{$baseurl}}/admin/addons/{{$a.0}}/">{{$a.1.name}}</a></td>
					<td>{{$a.1.description}}</td>
				</tr>
				{{/foreach}}
			</table>
		</div>
		<p><a class="btn btn-primary" href="/admin/addons">{{$link_enable_addons}}</a></p>

		{{* Server Settings. *}}
		<div id="admin-summary-php">
			<h2>{{$serversettings.label}}</h2>
			<div class="admin-summary-entry">
				<table class="table">
				<tbody>
					<tr class="info"><td colspan="2">PHP</td></tr>
					{{foreach $serversettings.php as $k => $p}}
						<tr><td>{{$k}}</td><td>{{$p}}</td></tr>
					{{/foreach}}
					<tr class="info"><td colspan="2">MySQL / MariaDB</td></tr>
					{{foreach $serversettings.mysql as $k => $p}}
						<tr><td>{{$k}}</td><td>{{$p}}</td></tr>
					{{/foreach}}
				</tbody>
				</table>
			</div>
		</div>

	</div>

</div>
