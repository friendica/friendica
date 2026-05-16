{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}} <a href="help/user/two-factor-authentication" title="{{$help_label}}" class="btn btn-default btn-sm"><i aria-hidden="true" class="fa fa-question fa-2x"></i></a></h1>
	<div>{{$message nofilter}}</div>

	<div class="text-center">
		{{$qrcode_image nofilter}}
	</div>

	<form action="settings/2fa/verify?t={{$password_security_token}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{include file="field_input.tpl" field=$verify_code}}

		<div class="form-group settings-submit-wrapper">
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="verify">{{$verify_label}}</button>
		</div>
	</form>

	<div>{{$qrcode_url_message nofilter}}</div>

	<div>{{$manual_message nofilter}}</div>
</div>
