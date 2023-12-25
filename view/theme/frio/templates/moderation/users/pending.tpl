<script type="text/javascript" src="view/theme/frio/js/mod_admin.js?v={{constant('\Friendica\App::VERSION')}}"></script>
<link rel="stylesheet" href="view/theme/frio/css/mod_admin.css?v={{constant('\Friendica\App::VERSION')}}" type="text/css" media="screen"/>

<div id="admin-users" class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>

	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<table id="pending" class="table table-hover">
			<thead>
				<tr>
					<th>
						<div class="checkbox">
							<input type="checkbox" id="admin-settings-pending-select" class="selecttoggle" data-select-class="pending_ckbx"/>
							<label for="admin-settings-pending-select"></label>
						</div>
					</th>
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
						<a href="{{$baseurl}}/moderation/users/pending/allow/{{$u.uid}}?t={{$form_security_token}}" class="admin-settings-action-link btn btn-sm btn-primary" title="{{$approve}}"><i class="fa fa-check" aria-hidden="true"></i></a>
						<a href="{{$baseurl}}/moderation/users/pending/deny/{{$u.uid}}?t={{$form_security_token}}" class="admin-settings-action-link btn btn-sm btn-warning" title="{{$deny}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
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
		<button type="submit" name="page_users_approve" value="1" class="btn btn-primary">
			<i class="fa fa-check" aria-hidden="true"></i> {{$approve}}
		</button>
		<button type="submit" name="page_users_deny" value="1" class="btn btn-warning">
			<i class="fa fa-trash-o" aria-hidden="true"></i> {{$deny}}
		</button>
		{{$pager nofilter}}
	</form>
</div>
