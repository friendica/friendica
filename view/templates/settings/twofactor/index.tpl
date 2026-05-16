{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}} <a href="help/user/two-factor-authentication" title="{{$help_label}}" class="btn btn-default btn-sm"><i aria-hidden="true" class="fa fa-question fa-2x"></i></a></h1>
	<div>{{$message nofilter}}</div>
	<h2>{{$status_title}}</h2>
	<p><strong>{{$auth_app_label}}</strong>: {{$app_status}} </p>
{{if $has_secret && $verified}}
	<div>{{$configured_message nofilter}}</div>
{{/if}}
{{if $has_secret && !$verified}}
	<div>{{$not_configured_message nofilter}}</div>
{{/if}}

{{if $has_secret && $verified}}
	<h2>{{$recovery_codes_title}}</h2>
	<p><strong>{{$recovery_codes_remaining}}</strong>: {{$recovery_codes_count}}</p>
	<div>{{$recovery_codes_message nofilter}}</div>
{{/if}}

	<form action="settings/2fa" method="post">
		<h2>{{$action_title}}</h2>
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{include file="field_password.tpl" field=$password}}

{{if $has_secret && $verified}}
		<p><button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="recovery">{{$recovery_codes_label}}</button></p>
		<p><button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="app_specific">{{$app_specific_passwords_label}}</button></p>
		<p><button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="trusted">{{$trusted_browsers_label}}</button></p>
{{/if}}
{{if !$has_secret}}
		<p><button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="enable">{{$enable_label}}</button></p>
{{else}}
		<p><button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="disable">{{$disable_label}}</button></p>
{{/if}}
{{if $has_secret && !$verified}}
		<p><button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="configure">{{$configure_label}}</button></p>
{{/if}}
	</form>
</div>
