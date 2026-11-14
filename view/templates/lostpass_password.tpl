{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h3>{{$title}}</h3>

<p id="lostpass-desc">
{{$desc}}
</p>

{{if $errors}}
<div role="alert">
{{foreach $errors as $error}}
	<p class="alert alert-danger">{{$error}}</p>
{{/foreach}}
</div>
{{/if}}

<form action="{{$action}}" method="post">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
	{{include file="field_password.tpl" field=$password}}
	{{include file="field_password.tpl" field=$confirm}}
	<div id="login-submit-wrapper">
		<input type="submit" name="submit" id="lostpass-submit-button" class="btn btn-primary" value="{{$submit}}" />
	</div>
	<div id="login-submit-end"></div>
</form>
