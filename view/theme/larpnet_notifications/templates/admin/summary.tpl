{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id='adminpage-summery' class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	{{if $warningtext|count}}
	<div id="admin-warning-message-wrapper" class="alert alert-warning">
		{{foreach $warningtext as $wt}}
		<p>{{$wt nofilter}}</p>
		{{/foreach}}
	</div>
	{{/if}}

	<div id="admin-summary-wrapper">
		{{* The work queues short statistic. *}}
		<div id="admin-summary-queues" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$queues.label}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry"><a href="{{$baseurl}}/admin/queue/deferred">{{$queues.deferred}}</a> - <a href="{{$baseurl}}/admin/queue">{{$queues.workerq}}</a></div>
		</div>

		{{* List enabled addons. *}}
		<div id="admin-summary-addons" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$addons.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">
				{{foreach $addons.1 as $p}}
				<a href="{{$baseurl}}/admin/addons/{{$p}}/">{{$p}}</a><br>
				{{/foreach}}
			</div>
		</div>

		{{* The Friendica version. *}}
		<div id="admin-summary-version" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$version_label}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">{{$platform}} '{{$codename}}' {{$VERSION}} - {{$build}}</div>
		</div>

		{{* Server Settings. *}}
		<div id="admin-summary-php" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$serversettings.label}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">
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

	<div class="clear"></div>

</div>
