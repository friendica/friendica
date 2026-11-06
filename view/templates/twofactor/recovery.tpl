{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<div>{{$message nofilter}}</div>

{{if $errors}}
	<div class="panel panel-danger">
		<div class="panel-heading">{{$errors_label}}</div>
		<ul class="list-group">
	{{foreach $errors as $error}}
			<li class="list-group-item">{{$error}}</li>
	{{/foreach}}
		</ul>
	</div>
{{/if}}

	<form action="" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{include file="field_input.tpl" field=$recovery_code}}

		<div class="form-group settings-submit-wrapper">
			<button type="submit" name="action" id="confirm-submit-button" class="btn btn-primary confirm-button" value="recover">{{$recovery_label}}</button>
		</div>
	</form>
</div>
