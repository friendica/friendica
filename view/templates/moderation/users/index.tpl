{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script>
	function confirm_delete(uname) {
		return confirm("{{$confirm_delete}}".format(uname));
	}

	function confirm_delete_multi() {
		return confirm("{{$confirm_delete_multi}}");
	}

	function selectall(cls) {
		$("." + cls).attr('checked', 'checked');
		return false;
	}
</script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>

	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table id="users">
			<thead>
			<tr>
				<th></th>
				{{foreach $th_users as $th}}
					<th>
						<a href="{{$baseurl}}/moderation/users?o={{if $order_direction_users == "+"}}-{{/if}}{{$th.1}}">
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
				{{/foreach}}
				<th></th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			{{foreach $users as $u}}
				<tr>
					<td><img class="icon" src="{{$u.micro}}" alt="{{$u.nickname}}" title="{{$u.nickname}}"></td>
					<td class="name"><a href="{{$u.url}}" title="{{$u.nickname}}">{{$u.name}}</a></td>
					<td class="email">{{$u.email}}</td>
					<td class="register_date">{{$u.register_date}}</td>
					<td class="last_activity">{{$u.last_activity}}</td>
					<td class="lastitem_date">{{$u.lastitem_date}}</td>
					<td class="acct-type-col">
						{{if $u.page_flags_raw==0 && $u.account_type_raw > 0}}
							{{if $u.account_type_raw==1}}
								{{$acct_icon = "ri-building-4-line"}} {{* ACCOUNT_TYPE_ORGANISATION *}}
							{{else if $u.account_type_raw==2}}
								{{$acct_icon = "ri-newspaper-line"}}  {{* ACCOUNT_TYPE_NEWS *}}
							{{else if $u.account_type_raw==4}}
								{{$acct_icon = "ri-broadcast-line"}}
							{{else}}
								{{$acct_icon = ""}}
							{{/if}}
						{{else}}
							{{if $u.page_flags_raw==0}}
								{{$acct_icon = "ri-user-line"}}		  {{* PERSON NORMAL *}}
							{{else if $u.page_flags_raw==1}}
								{{$acct_icon = "ri-megaphone-line"}}  {{* PERSON SOAPBOX *}}
							{{else if $u.page_flags_raw==2}}
								{{$acct_icon = "ri-team-line"}}		  {{* PUBLIC GROUP *}}
							{{else if $u.page_flags_raw==3}}
								{{$acct_icon = "ri-heart-line"}}	  {{* PERSON FREELOVE *}}
							{{else if $u.page_flags_raw==4}}
								{{$acct_icon = "ri-broadcast-line"}}  {{* PAGE BLOG *}}
							{{else if $u.page_flags_raw==5}}
								{{$acct_icon = "ri-spy-line"}}	      {{* GROUP PRIVATE *}}
							{{else if $u.page_flags_raw==6}}
								{{$acct_icon = "ri-group-3-line"}}	  {{* GROUP RESTRICTED *}}
							{{else}}
								{{$acct_icon = ""}}
							{{/if}}
						{{/if}}
						<span class="acct-type"><i class="ri {{$acct_icon}}" aria-hidden="true" data-acct="{{$u.account_type_raw}}" data-flag="{{$u.page_flags_raw}}" title="{{if $u.page_flags && $u.page_flags_raw !=0}}{{$u.page_flags}}{{else}}{{$u.account_type}}{{/if}}"></i> <span>{{if $u.page_flags && $u.page_flags_raw !=0}}{{$u.page_flags}}{{else}}{{$u.account_type}}{{/if}}</span></span>
						{{if $u.is_admin}}<span class="acct-type"><i class="ri ri-medal-2-fill text-primary" title="{{$siteadmin}}"></i> <span>{{$siteadmin}}</span>{{else if $u.is_mod}}<span class="acct-type"><i class="ri ri-shield-user-line" title="{{$moderator}}"></i> <span>{{$moderator}}</span>{{/if}}
						{{if $u.blocked}}<span class="acct-type"><i class="ri ri-forbid-2-line text-danger" title="{{$blocked}}"></i> <span>{{$blocked}}</span></span>{{/if}}
						{{if $u.deleted}}<span class="acct-type"><i class="ri ri-user-unfollow-line" title="{{$h_deleted}}"></i> <span>{{$h_deleted}}</span></span>{{/if}}
						{{if $u.account_expired}}<span class="acct-type"><i class="ri ri-time-line text-warning" title="{{$accountexpired}}"></i> <span>{{$accountexpired}}</span></span>{{/if}}
					</td>
					<td class="checkbox">
						{{if $u.is_deletable}}
						<input type="checkbox" class="users_ckbx" id="id_user_{{$u.uid}}" name="user[]" value="{{$u.uid}}"/>
						{{else}}
							&nbsp;
						{{/if}}
					</td>

					<td class="tools">
						{{if $u.is_deletable}}
							<a href="{{$baseurl}}/moderation/users/block/{{$u.uid}}?t={{$form_security_token}}" title="{{if $u.blocked}}{{$unblock}}{{else}}{{$block}}{{/if}}">
								<span class="icon block {{if $u.blocked==0}}dim{{/if}}"></span>
							</a>
							<a href="{{$baseurl}}/moderation/users/delete/{{$u.uid}}?t={{$form_security_token}}" title="{{$delete}}" onclick="return confirm_delete('{{$u.name}}')">
								<span class="icon drop"></span>
							</a>
						{{else}}
							&nbsp;
						{{/if}}
					</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
		<div class="selectall"><a href="#" onclick="return selectall('users_ckbx');">{{$select_all}}</a></div>
		<div class="submit">
			<input type="submit" name="page_users_block" value="{{$block}}"/>
			<input type="submit" name="page_users_unblock" value="{{$unblock}}"/>
			<input type="submit" name="page_users_delete" value="{{$delete}}" onclick="return confirm_delete_multi()"/>
		</div>
	</form>
	{{$pager nofilter}}
	<p>
		<a href="{{$base_url}}/moderation/users/create">{{$h_newuser}}</a>
	</p>
</div>
