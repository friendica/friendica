{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="admin-users" class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	<p>{{$description}}</p>

	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{include file="field_input.tpl" field=$newusername label=false}}
		{{include file="field_input.tpl" field=$newusernickname label=false}}
		{{include file="field_input.tpl" field=$newuseremail label=false}}
		<p>
			<button type="submit" class="btn btn-primary">{{$submit}}</button>
		</p>
	</form>
</div>
