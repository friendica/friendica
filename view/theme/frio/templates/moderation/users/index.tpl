{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script type="text/javascript" src="view/theme/frio/js/mod_admin.js?v={{$VERSION}}"></script>
<link rel="stylesheet" href="view/theme/frio/css/mod_admin.css?v={{$VERSION}}" type="text/css" media="screen"/>

<div id="admin-users" class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>
	<p>
		<a href="{{$base_url}}/moderation/users/create" class="btn btn-primary"><i class="ri ri-user-add-line"></i> {{$h_newuser}}</a>
	</p>
	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table id="users" class="table table-hover">
			<thead>
				<tr>
					<th>
						<div class="checkbox">
							<input type="checkbox" id="admin-settings-users-select" class="selecttoggle" data-select-class="users_ckbx"/>
							<label for="admin-settings-users-select"></label>
						</div>
					</th>
					<th></th>
					{{foreach $th_users as $k=>$th}}
						{{if $k < 2 || $order_users == $th.1 || ($k==5 && !in_array($order_users,[$th_users.2.1, $th_users.3.1, $th_users.4.1])) }}
						<th class="th-{{$k}}">
							<a href="{{$baseurl}}/moderation/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th.1}}" class="table-order">
								{{if $order_users == $th.1}}
									{{if $order_direction_users == "+"}}
									&#8595;
									{{else}}
									&#8593;
									{{/if}}
								{{else}}
								&#8597;
								{{/if}}
								{{$th.0}}
							</a>
						</th>
						{{/if}}
					{{/foreach}}
					<th></th>
				</tr>
			</thead>
			<tbody>
			{{foreach $users as $u}}
				<tr id="user-{{$u.uid}}" class="{{if $u.blocked != 0}}blocked{{/if}}">
					<td>
						{{if $u.is_deletable}}
						<div class="checkbox">
							<input type="checkbox" class="users_ckbx" id="id_user_{{$u.uid}}" name="user[]" value="{{$u.uid}}"/>
							<label for="id_user_{{$u.uid}}"></label>
						</div>
						{{else}}
						&nbsp;
						{{/if}}
					</td>
					<td><img class="avatar-nano" src="{{$u.micro}}" title="{{$u.nickname}}"></td>
					<td><a href="{{$u.url}}" title="{{$u.nickname}}"> {{$u.name}}</a></td>
					<td>{{$u.email}}</td>
				{{if $order_users == $th_users.2.1}}
					<td>{{$u.register_date}}</td>
				{{/if}}

				{{if $order_users == $th_users.3.1}}
					<td>{{$u.login_date}}</td>
				{{/if}}

				{{if $order_users == $th_users.4.1}}
					<td>{{$u.lastitem_date}}</td>
				{{/if}}

				{{if !in_array($order_users,[$th_users.2.1, $th_users.3.1, $th_users.4.1]) }}
					<td>
						{{if $u.page_flags_raw==0 && $u.account_type_raw > 0}}
						<i class="ri
							{{if $u.account_type_raw==1}}ri-building-4-line{{/if}}		{{* ORGANIZATION PAGE *}}
							{{if $u.account_type_raw==2}}ri-newspaper-line{{/if}}	    {{* NEWS PAGE *}}
							" data-flag="0" data-acct="{{$u.account_type_raw}}" title="{{$u.account_type}}">
						</i>
						{{else}}
						<i class="ri
							{{if $u.page_flags_raw==0}}ri-user-line{{/if}}		    {{* PERSON NORMAL *}}
							{{if $u.page_flags_raw==1}}ri-megaphone-line{{/if}}		{{* PERSON SOAPBOX *}}
							{{if $u.page_flags_raw==2}}ri-team-line{{/if}}		    {{* PUBLIC GROUP *}}
							{{if $u.page_flags_raw==3}}ri-heart-line{{/if}}		    {{* PERSON FREELOVE *}}
							{{if $u.page_flags_raw==4}}ri-broadcast-line{{/if}}		{{* PAGE BLOG *}}
							{{if $u.page_flags_raw==5}}ri-spy-line{{/if}}	        {{* GROUP PRIVATE *}}
							{{if $u.page_flags_raw==6}}ri-group-3-line{{/if}}		{{* GROUP RESTRICTED *}}
							" data-flag="{{$u.page_flags_raw}}" data-acct="{{$u.account_type_raw}}" title="{{$u.page_flags}}">
						</i>
						{{/if}}
						{{if $u.is_admin}}<i class="ri ri-key-line text-primary" title="{{$siteadmin}}"></i>{{/if}}
						{{if $u.blocked}}<i class="ri ri-forbid-2-line text-danger" title="{{$blocked}}"></i>{{/if}}
						{{if $u.deleted}}<i class="ri ri-user-unfollow-line" title="{{$h_deleted}}"></i>{{/if}}
						{{if $u.account_expired}}<i class="ri ri-time-line text-warning" title="{{$accountexpired}}"></i>{{/if}}
					</td>
				{{/if}}

					<td class="text-right">
						<button type="button" class="btn-link admin-settings-action-link" onclick="return details({{$u.uid}})"><span class="caret"></span></button>
					</td>
				</tr>
				<tr id="user-{{$u.uid}}-detail" class=" details hidden {{if $u.blocked != 0}}blocked{{/if}}">
					<td>&nbsp;</td>
					<td colspan="4">
					{{if $order_users != $th_users.2.1}}
						<p>
							<a href="{{$baseurl}}/moderation/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.2.1}}" class="btn-link table-order">
							&#8597; {{$th_users.2.0}}</a> : {{$u.register_date}}
						</p>
					{{/if}}

					{{if $order_users != $th_users.3.1}}
						<p>
							<a href="{{$baseurl}}/moderation/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.3.1}}" class="btn-link table-order">
								&#8597; {{$th_users.3.0}}</a> : {{$u.login_date}}
						</p>
					{{/if}}

					{{if $order_users != $th_users.4.1}}
						<p>
							<a href="{{$baseurl}}/moderation/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.4.1}}" class="btn-link table-order">
								&#8597; {{$th_users.4.0}}</a> : {{$u.lastitem_date}}
						</p>
					{{/if}}

					{{if in_array($order_users,[$th_users.2.1, $th_users.3.1, $th_users.4.1]) }}
						<p>
							<a href="{{$baseurl}}/moderation/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.5.1}}" class="btn-link table-order">
								&#8597; {{$th_users.5.0}}</a> : {{$u.page_type.0}}{{if $u.page_flags_raw==0 && $u.account_type_raw > 0}}, {{$u.account_type.0}}{{/if}} {{if $u.is_admin}}({{$siteadmin}}){{/if}} {{if $u.account_expired}}({{$accountexpired}}){{/if}}
						</p>
					{{/if}}

					</td>
					<td class="text-right">
				{{if $u.is_deletable}}
					{{if $u.blocked}}
						<a href="{{$baseurl}}/moderation/users/unblock/{{$u.uid}}?t={{$form_security_token}}" class="admin-settings-action-link" title="{{$unblock}}">
							<i class="ri ri-checkbox-circle-line" aria-hidden="true"></i>
						</a>
					{{else}}
						<a href="{{$baseurl}}/moderation/users/block/{{$u.uid}}?t={{$form_security_token}}" class="admin-settings-action-link" title="{{$block}}">
							<i class="ri ri-forbid-2-line" aria-hidden="true"></i>
						</a>
					{{/if}}
						<a href="{{$baseurl}}/moderation/users/delete/{{$u.uid}}?t={{$form_security_token}}" class="admin-settings-action-link" title="{{$delete}}" onclick="return confirm_delete('{{$confirm_delete}}','{{$u.name}}')">
							<i class="ri ri-delete-bin-line" aria-hidden="true"></i>
						</a>
				{{else}}
						&nbsp;
				{{/if}}
					</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
		<div class="panel-footer">
			<button type="submit" name="page_users_block" value="1" class="btn btn-warning">
				<i class="ri ri-forbid-2-line" aria-hidden="true"></i> {{$block}}
			</button>
			<button type="submit" name="page_users_unblock" value="1" class="btn btn-default">
				<i class="ri ri-checkbox-circle-line" aria-hidden="true"></i> {{$unblock}}
			</button>
			<button type="submit" name="page_users_delete" value="1" class="btn btn-danger" onclick="return confirm_delete('{{$confirm_delete_multi}}')">
				<i class="ri ri-delete-bin-line" aria-hidden="true"></i> {{$delete}}
			</button>
		</div>
		{{$pager nofilter}}
	</form>
</div>
