{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<form action="folders/rename" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<input type="hidden" name="oldname" value="{{$oldname}}">
		{{include file="field_input.tpl" field=$newname}}
		<button type="submit" class="btn btn-primary">{{$submit}}</button>
		<a href="folders" class="btn btn-default">{{$cancel}}</a>
	</form>
</div>
