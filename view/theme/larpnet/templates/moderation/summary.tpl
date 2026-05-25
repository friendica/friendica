{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id='adminpage-summery' class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	<div id="admin-summary-wrapper">
		{{* Number of pending registrations. *}}
		<div id="admin-summary-pending" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$pending.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">{{$pending.1}}</div>
		</div>

		{{* Number of registered users *}}
		<div id="admin-summary-users" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$users.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">{{$users.1}}</div>
		</div>

		{{* Account types of registered users. *}}
		{{foreach $accounts as $p}}
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$p.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">{{if $p.1}}{{$p.1}}{{else}}0{{/if}}</div>
		</div>
		{{/foreach}}

	</div>

	<div class="clear"></div>

</div>
