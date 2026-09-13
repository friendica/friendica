{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script>
	function confirm_delete(uname) {
		return confirm("{{$confirm_delete}}".format(uname));
	}

	function confirm_delete_multi() {
		return confirm("{{$confirm_delete_multi}}");
	}

	function selectall(cls) {
		$("." + cls).attr('checked', 'checked');
		return false;
	}
</script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>

	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<table id="deleted">
			<thead>
			<tr>
				<th></th>
			{{foreach $th_deleted as $th}}
				<th>{{$th}}</th>
			{{/foreach}}
			</tr>
			</thead>
			<tbody>
			{{foreach $users as $u}}
				<tr>
					<td><img class="icon" src="{{$u.micro}}" alt="{{$u.nickname}}" title="{{$u.nickname}}"></td>
					<td class="name"><a href="{{$u.url}}" title="{{$u.nickname}}">{{$u.name}}</a></td>
					<td class="email">{{$u.email}}</td>
					<td class="register_date">{{$u.register_date}}</td>
					<td class="last_activity">{{$u.last_activity}}</td>
					<td class="lastitem_date">{{$u.lastitem_date}}</td>
					<td class="deleted_date">{{$u.deleted}}</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
		{{$pager nofilter}}
	</form>
</div>
