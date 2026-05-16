{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}} <a href="help/user/two-factor-authentication" title="{{$help_label}}" class="btn btn-default btn-sm"><i aria-hidden="true" class="fa fa-question fa-2x"></i></a></h1>
	<div>{{$message nofilter}}</div>

{{if $generated_app_specific_password}}
	<div class="panel panel-success">
		<div class="panel-heading">
			✅ {{$generated_app_specific_password.plaintext_password}}
		</div>
		<div class="panel-body">
            {{$generated_message}}
		</div>
	</div>
{{/if}}

	<form action="settings/2fa/app_specific?t={{$password_security_token}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table class="app-specific-passwords table table-hover table-condensed table-striped">
			<thead>
				<tr>
					<th>{{$description_label}}</th>
					<th>{{$last_used_label}}</th>
					<th><button type="submit" name="action" class="btn btn-primary btn-small" value="revoke_all">{{$revoke_all_label}}</button></th>
				</tr>
			</thead>
			<tbody>
{{foreach $app_specific_passwords as $app_specific_password}}
				<tr{{if $generated_app_specific_password && $app_specific_password.id == $generated_app_specific_password.id}} class="success"{{/if}}>
					<td>
		                {{$app_specific_password.description}}
					</td>
					<td>
						<time class="time" title="{{$app_specific_password.local}}" data-toggle="tooltip" datetime="{{$app_specific_password.utc}}">{{$app_specific_password.ago}}</time>
					</td>
					<td>
						<button type="submit" name="revoke_id" class="btn btn-default btn-small" value="{{$app_specific_password.id}}">{{$revoke_label}}</button>
					</td>
				</tr>
{{/foreach}}
			</tbody>
		</table>
	</form>
	<form action="settings/2fa/app_specific?t={{$password_security_token}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<h3>{{$generate_title}}</h3>
		<p>{{$generate_message}}</p>
		<div class="form-group">
			<label for="app-specific-password-description">{{$description_label}}</label>
			<input type="text" maxlength="255" name="description" id="app-specific-password-description" class="form-control" placeholder="{{$description_placeholder_label}}" required/>
		</div>
		<p>
			<button type="submit" name="action" class="btn btn-large btn-primary" value="generate">{{$generate_label}}</button>
		</p>
	</form>
</div>
