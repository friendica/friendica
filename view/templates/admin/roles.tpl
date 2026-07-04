{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$intro}}</p>

	<h2>{{$admin_title}}</h2>
	{{if $admin_users}}
		<table>
			<thead>
				<tr>
					<th>{{$user_header}}</th>
					<th>{{$email_header}}</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $admin_users as $user}}
					<tr>
						<td>{{$user.nickname}}</td>
						<td>{{$user.email}}</td>
					</tr>
				{{/foreach}}
			</tbody>
		</table>
	{{else}}
		<p>{{$no_admin_users}}</p>
	{{/if}}

	<h2>{{$moderator_title}}</h2>
	{{if $moderation_users}}
		<table>
			<thead>
				<tr>
					<th>{{$user_header}}</th>
					<th>{{$email_header}}</th>
					<th>{{$source_header}}</th>
					<th>{{$actions_header}}</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $moderation_users as $user}}
					<tr>
						<td>{{$user.nickname}}</td>
						<td>{{$user.email}}</td>
						<td>
							{{if $user.source == 'admin'}}{{$admin_source}}
							{{elseif $user.source == 'moderator'}}{{$moderator_source}}
							{{else}}{{$combined_source}}{{/if}}
						</td>
						<td>
							{{if $user.can_remove}}
								<form action="{{$baseurl}}/admin/roles" method="post">
									<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
									<input type="hidden" name="roles_action" value="remove">
									<input type="hidden" name="moderator_uid" value="{{$user.uid}}">
									<input type="submit" name="page_roles" value="{{$remove}}">
								</form>
							{{else}}
								{{$cannot_remove_admin}}
							{{/if}}
						</td>
					</tr>
				{{/foreach}}
			</tbody>
		</table>
	{{else}}
		<p>{{$no_moderator_users}}</p>
	{{/if}}

	<h2>{{$assign_title}}</h2>
	{{if $available_users}}
		<form action="{{$baseurl}}/admin/roles" method="post">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<input type="hidden" name="roles_action" value="add">

			<label for="moderator_uid">{{$assign_label}}</label>
			<select id="moderator_uid" name="moderator_uid" required>
				{{foreach $available_users as $user}}
					<option value="{{$user.uid}}">{{$user.nickname}} ({{$user.email}})</option>
				{{/foreach}}
			</select>

			<div class="submit"><input type="submit" name="page_roles" value="{{$assign_button}}"></div>
		</form>
	{{else}}
		<p>{{$no_assignable_users}}</p>
	{{/if}}
</div>
