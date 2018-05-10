<script type="text/javascript" src="view/theme/frio/js/mod_admin.js"></script>
<link rel="stylesheet" href="view/theme/frio/css/mod_admin.css" type="text/css" media="screen"/>

<div id="admin-users" class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/users" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		{{* We organize the settings in collapsable panel-groups *}}
		<div class="panel-group panel-group-settings" id="admin-settings" role="tablist" aria-multiselectable="true">

			<!--
				**
				*
				*		PENDING Users table
				*
				**
			-->
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="admin-settings-pending">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-pending-collapse" aria-expanded="{{if count($pending) > 0}}true{{else}}false{{/if}}" aria-controls="admin-settings-pending-collapse">
							{{$h_pending}} ({{count($pending)}})
						</a>
					</h4>
				</div>

				<div id="admin-settings-pending-collapse" class="panel-collapse collapse {{if count($pending) > 0}}in{{/if}}" role="tabpanel" aria-labelledby="admin-settings-pending">
					{{if $pending}}
					<table id="pending" class="table table-hover">
						<thead>
							<tr>
								<th></th>
								{{foreach $th_pending as $th}}<th>{{$th}}</th>{{/foreach}}
								<th></th>
							</tr>
						</thead>
						<tbody>
						{{foreach $pending as $u}}
							<tr>
								<td>
									<div class="checkbox">
										<input type="checkbox" class="pending_ckbx" id="id_pending_{{$u.hash}}" name="pending[]" value="{{$u.hash}}" />
										<label for="id_pending_{{$u.hash}}"></label>
									</div>
								</td>
								<td>{{$u.created}}</td>
								<td>{{$u.name}}</td>
								<td>{{$u.email}}</td>
								<td>
									<a href="{{$baseurl}}/regmod/allow/{{$u.hash}}" class="admin-settings-action-link" title="{{$approve}}"><i class="fa fa-check" aria-hidden="true"></i></a>
									<a href="{{$baseurl}}/regmod/deny/{{$u.hash}}" class="admin-settings-action-link" title="{{$deny}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
								</td>
							</tr>
							{{if $u.note}}
							<tr class="details">
								<td></td>
								<th>{{$pendingnotetext}}</th>
								<td colspan="4">{{$u.note}}</td>
							</tr>
							{{/if}}
						{{/foreach}}
						</tbody>
					</table>
					<div class="panel-footer">
						<div class="row">
							<div class="col-xs-3 admin-settings-footer-elements">
								<div class="checkbox">
									<input type="checkbox" id="admin-settings-pending-select" class="selecttoggle" data-select-class="pending_ckbx"/>
									<label for="admin-settings-pending-select"></label>
								</div>
							</div>
							<div class="col-xs-9 admin-settings-footer-elements text-right">
								<button type="submit" name="page_users_deny" value="1" class="btn btn-primary">
									<i class="fa fa-trash-o" aria-hidden="true"></i> {{$deny}}
								</button>
								<button type="submit" name="page_users_approve" value="1" class="btn btn-warinig">
									<i class="fa fa-check" aria-hidden="true"></i> {{$approve}}
								</button>
							</div>
						</div>
					</div>
					{{else}}
					<div class="panel-body text-center text-muted">{{$no_pending}}</div>
					{{/if}}
				</div>
			</div>

			<!--
				**
				*
				*		USERS Table
				*
				**
			-->
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="admin-settings-user">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-user-collapse" aria-expanded="false" aria-controls="admin-settings-user-collapse">
							{{$h_users}} ({{count($users)}})
						</a>
					</h4>
				</div>

				<div id="admin-settings-user-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-user">

					{{if $users}}
					<table id="users" class="table table-hover">
						<thead>
							<tr>
								<th></th>
								<th></th>
								{{foreach $th_users as $k=>$th}}
									{{if $k < 2 || $order_users == $th.1 || ($k==5 && !in_array($order_users,[$th_users.2.1, $th_users.3.1, $th_users.4.1])) }}
									<th class="th-{{$k}}">
										<button type="button" data-order-url="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th.1}}" class="btn-link table-order">
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
										</button>
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
									<i class="fa
										{{if $u.page_flags_raw==0}}fa-user{{/if}}		{{* PAGE_NORMAL *}}
										{{if $u.page_flags_raw==1}}fa-bullhorn{{/if}}		{{* PAGE_SOAPBOX *}}
										{{if $u.page_flags_raw==2}}fa-users{{/if}}		{{* PAGE_COMMUNITY *}}
										{{if $u.page_flags_raw==3}}fa-heart{{/if}}		{{* PAGE_FREELOVE *}}
										{{if $u.page_flags_raw==4}}fa-rss{{/if}}		{{* PAGE_BLOG *}}
										{{if $u.page_flags_raw==5}}fa-user-secret{{/if}}	{{* PAGE_PRVGROUP *}}
										" title="{{$u.page_flags}}">
									</i>
									{{if $u.page_flags_raw==0 && $u.account_type_raw > 0}}
									<i class="fa
										{{if $u.account_type_raw==1}}fa-sitemap{{/if}}		{{* ACCOUNT_TYPE_ORGANISATION *}}
										{{if $u.account_type_raw==2}}fa-newspaper-o{{/if}}	{{* ACCOUNT_TYPE_NEWS *}}
										{{if $u.account_type_raw==3}}fa-comments{{/if}}		{{* ACCOUNT_TYPE_COMMUNITY *}}
										" title="{{$u.account_type}}">
									</i>
									{{/if}}
									{{if $u.is_admin}}<i class="fa fa-user-md text-primary" title="{{$siteadmin}}"></i>{{/if}}
									{{if $u.account_expired}}<i class="fa fa-clock-o text-warning" title="{{$accountexpired}}"></i>{{/if}}
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
										<button type="button" data-order-url="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.2.1}}" class="btn-link table-order">
										&#8597; {{$th_users.2.0}}</button> : {{$u.register_date}}
									</p>
									{{/if}}

									{{if $order_users != $th_users.3.1}}
									<p>
										<button type="button" data-order-url="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.3.1}}" class="btn-link table-order">
											&#8597; {{$th_users.3.0}}</button> : {{$u.login_date}}
									</p>
									{{/if}}

									{{if $order_users != $th_users.4.1}}
									<p>
										<button type="button" data-order-url="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.4.1}}" class="btn-link table-order">
											&#8597; {{$th_users.4.0}}</button> : {{$u.lastitem_date}}
									</p>
									{{/if}}

									{{if in_array($order_users,[$th_users.2.1, $th_users.3.1, $th_users.4.1]) }}
									<p>
										<button type="button" data-order-url="{{$baseurl}}/admin/users/?o={{if $order_direction_users == "+"}}-{{/if}}{{$th_users.5.1}}" class="btn-link table-order">
											&#8597; {{$th_users.5.0}}</button> : {{$u.page_flags}}{{if $u.page_flags_raw==0 && $u.account_type_raw > 0}}, {{$u.account_type}}{{/if}} {{if $u.is_admin}}({{$siteadmin}}){{/if}} {{if $u.account_expired}}({{$accountexpired}}){{/if}}
									</p>
									{{/if}}

								</td>
								<td class="text-right">
									{{if $u.is_deletable}}
									<a href="{{$baseurl}}/admin/users/block/{{$u.uid}}?t={{$form_security_token}}" class="admin-settings-action-link"title="{{if $u.blocked}}{{$unblock}}{{else}}{{$block}}{{/if}}">
										{{if $u.blocked == 0}}
										<i class="fa fa-ban" aria-hidden="true"></i>
										{{else}}
										<i class="fa fa-circle-o" aria-hidden="true"></i>
										{{/if}}
									</a>
									<a href="{{$baseurl}}/admin/users/delete/{{$u.uid}}?t={{$form_security_token}}" class="admin-settings-action-link" title="{{$delete}}" onclick="return confirm_delete('{{$confirm_delete}}','{{$u.name}}')">
										<i class="fa fa-trash" aria-hidden="true"></i>
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
						<div class="row">
							<div class="col-xs-3 admin-settings-footer-elements">
								<div class="checkbox">
									<input type="checkbox" id="admin-settings-users-select" class="selecttoggle" data-select-class="users_ckbx"/>
									<label for="admin-settings-users-select"></label>
								</div>
							</div>
							<div class="col-xs-9 admin-settings-footer-elements text-right">
								<button type="submit" name="page_users_block" value="1" class="btn btn-warning">
									<i class="fa fa-ban" aria-hidden="true"></i> {{$block}} / <i class="fa fa-circle-o" aria-hidden="true"></i> {{$unblock}}
								</button>
								<button type="submit" name="page_users_delete" value="1" class="btn btn-danger" onclick="return confirm_delete('{{$confirm_delete_multi}}')">
									<i class="fa fa-trash" aria-hidden="true"></i> {{$delete}}
								</button>
							</div>
						</div>
					</div>
					{{else}}
					<div class="panel-body text-center bg-danger">NO USERS?!?</div>
					{{/if}}
				</div>
			</div>


			<!--
				**
				*
				*		DELETED Users table
				*
				**
			-->
			{{if $deleted}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="admin-settings-deleted">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-deleted-collapse" aria-expanded="false" aria-controls="admin-settings-deleted-collapse">
							{{$h_deleted}} ({{count($deleted)}})
						</a>
					</h4>
				</div>

				<div id="admin-settings-deleted-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-deleted">
					<table id="deleted" class="table table-hover">
						<thead>
							<tr>
								<th></th>
								{{foreach $th_deleted as $k=>$th}}
									{{if in_array($k,[0,1,5])}}
									<th>{{$th}}</th>
									{{/if}}
								{{/foreach}}
							</tr>
						</thead>
						<tbody>
						{{foreach $deleted as $u}}
							<tr>
								<td><img class="avatar-nano" src="{{$u.micro}}" title="{{$u.nickname}}"></td>
								<td><a href="{{$u.url}}" title="{{$u.nickname}}" >{{$u.name}}</a></td>
								<td>{{$u.email}}</td>
								<td>{{$u.deleted}}</td>
							</tr>
						{{/foreach}}
						</tbody>
					</table>
				</div>
			</div>
			{{/if}}



			<!--
				**
				*
				*		NEW USER Form
				*
				**
			-->
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="admin-settings-new-user">
					<h4>
						<a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-new-user-collapse" aria-expanded="false" aria-controls="admin-settings-new-user-collapse">
							{{$h_newuser}}
						</a>
					</h4>
				</div>

				<div id="admin-settings-new-user-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-new-user">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$newusername}}
						{{include file="field_input.tpl" field=$newusernickname}}
						{{include file="field_input.tpl" field=$newuseremail}}
					</div>
					<div class="panel-footer text-right">
						<button type="submit" class="btn btn-primary">{{$submit}}</button>
					</div>


				</div>
			</div>
		</div>
	</form>
</div>
