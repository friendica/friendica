{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>
	
	<p>{{$info}}</p>
	<table class="table">
		<tr>
			<th>{{$id_header}}</th>
			<th>{{$command_header}}</th>
			<th>{{$param_header}}</th>
			<th>{{$created_header}}</th>
			{{if ($status == "deferred") }}<th>{{$next_try_header}}</th>{{/if}}
			<th>{{$prio_header}}</th>
		</tr>
		{{foreach $entries as $e}}
		<tr>
			<td>{{$e.id}}</td>
			<td>{{$e.command}}</td>
			<td>{{$e.parameter}}</td>
			<td>{{$e.created}}</td>
			{{if ($status == "deferred") }}<td>{{$e.next_try}}</td>{{/if}}
			<td>{{$e.priority}}</td>
		</tr>
		{{/foreach}}
	</table>
</div>
