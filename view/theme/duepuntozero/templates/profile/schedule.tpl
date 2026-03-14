{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<table id="application-block" class="table table-condensed table-striped">
		<thead>
			<tr>
				<th>{{$scheduled_at}}</th>
				<th>{{$content}}</th>
			</tr>
		</thead>
		<tbody>
			{{foreach $schedule as $row}}
			<tr>
				<td>{{$row.scheduled_at}}</td>
				<td>{{$row.content}}</td>
				<td>
					<form action="{{$baseurl}}/profile/{{$nickname}}/schedule" method="post">
						<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
						<button type="submit" name="delete" value="{{$row.id}}" title="{{$delete}}" class="icon s22 delete"></button>
					</form>
				</td>
			</tr>
			{{/foreach}}
		</tbody>
	</table>
</div>
