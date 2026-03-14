{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}} <a href="help/user/two-factor-authentication" title="{{$help_label}}" class="btn btn-default btn-sm"><i aria-hidden="true" class="fa fa-question fa-2x"></i></a></h1>
	<div>{{$message nofilter}}</div>

	<ul class="recovery-codes">
{{foreach $recovery_codes as $recovery_code}}
		<li>
			{{if $recovery_code.used}}<s>{{/if}}
				{{$recovery_code.code}}
			{{if $recovery_code.used}}</s>{{/if}}
		</li>
{{/foreach}}
	</ul>

{{if $verified}}
	<form action="settings/2fa/recovery?t={{$password_security_token}}" method="post">
		<h2>{{$regenerate_label}}</h2>
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<div>{{$regenerate_message}}</div>

		<div class="form-group pull-right settings-submit-wrapper">
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="regenerate">{{$regenerate_label}}</button>
		</div>
	</form>
{{else}}
	<p class="text-right"><a href="settings/2fa/verify?t={{$password_security_token}}" class="btn btn-primary">{{$verify_label}}</a></p>
{{/if}}
</div>
