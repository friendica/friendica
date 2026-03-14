{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}} <a href="help/user/two-factor-authentication" title="{{$help_label}}" class="btn btn-default btn-sm"><i aria-hidden="true" class="fa fa-question fa-2x"></i></a></h1>
	<div>{{$message nofilter}}</div>

	<form action="settings/2fa/trusted?t={{$password_security_token}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table class="trusted-passwords table table-hover table-condensed table-striped">
			<thead>
				<tr>
					<th>{{$device_label}}</th>
					<th>{{$os_label}}</th>
					<th>{{$browser_label}}</th>
					<th>{{$trusted_label}}</th>
					<th>{{$created_label}}</th>
					<th>{{$last_used_label}}</th>
					<th><button type="submit" name="action" class="btn btn-primary btn-small" value="remove_all">{{$remove_all_label}}</button></th>
				</tr>
			</thead>
			<tbody>
{{foreach $trusted_browsers as $trusted_browser}}
				<tr{{if $generated_trusted_browser && $trusted_browser.id == $generated_trusted_browser.id}} class="success"{{/if}}>
					<td>
		                {{$trusted_browser.device}}
					</td>
					<td>
		                {{$trusted_browser.os}}
					</td>
					<td>
		                {{$trusted_browser.browser}}
					</td>
					<td>
                        {{$trusted_browser.trusted_labeled}}
					</td>
					<td>
						<time class="time" title="{{$trusted_browser.created_local}}" data-toggle="tooltip" datetime="{{$trusted_browser.created_utc}}">{{$trusted_browser.created_ago}}</time>
					</td>
					<td>
						<time class="time" title="{{$trusted_browser.last_used_local}}" data-toggle="tooltip" datetime="{{$trusted_browser.last_used_utc}}">{{$trusted_browser.last_used_ago}}</time>
					</td>
					<td>
						<button type="submit" name="remove_id" class="btn btn-default btn-small" value="{{$trusted_browser.cookie_hash}}">{{$remove_label}}</button>
					</td>
				</tr>
{{/foreach}}
			</tbody>
		</table>
	</form>
</div>
