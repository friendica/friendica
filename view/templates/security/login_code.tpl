{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper login-code-wrapper">
	<h1>{{$title}}</h1>

{{foreach $notices as $notice}}
	<div class="alert alert-warning" role="alert">{{$notice}}</div>
{{/foreach}}
{{foreach $infos as $info}}
	<div class="alert alert-info" role="status">{{$info}}</div>
{{/foreach}}

	<p>{{$desc}}</p>

	<form id="login-code-form" action="login/code" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<input type="hidden" name="return_path" value="{{$return_path}}">

{{if $step == 'verify'}}
		{{include file="field_input.tpl" field=$code}}
		{{include file="field_checkbox.tpl" field=$remember}}

		<div class="form-group settings-submit-wrapper">
			<button type="submit" name="action" id="login-code-submit-button" class="btn btn-primary" value="verify">{{$submit}}</button>
		</div>
		<p><a href="{{$new_path}}">{{$again}}</a></p>
{{else}}
		{{include file="field_input.tpl" field=$username}}

		<div class="form-group settings-submit-wrapper">
			<button type="submit" name="action" id="login-code-submit-button" class="btn btn-primary" value="request">{{$submit}}</button>
		</div>
{{/if}}
	</form>

	<p><a href="login">{{$back}}</a></p>
</div>
