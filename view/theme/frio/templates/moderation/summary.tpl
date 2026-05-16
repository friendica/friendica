{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div id='adminpage-summary' class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	<div id="admin-summary-wrapper">
		<table class="table">
			{{* Number of pending registrations. *}}
			<tr>
				<td>{{$pending.0}}</td>
				<td>{{$pending.1}}</td>
			</tr>

			{{* Number of registered users *}}
			<tr>
				<td>{{$users.0}}</td>
				<td>{{$users.1}}</td>
			</tr>
		</table>

		{{* Account types of registered users. *}}
		<h2>{{$account_type_header}}</h2>
		<table class="table">
			{{foreach $accounts as $p}}
			<tr>
				<td>{{$p.0}}</td>
				<td>{{if $p.1}}{{$p.1}}{{else}}0{{/if}}</td>
			</tr>
			{{/foreach}}
		</table>
	</div>
</div>
