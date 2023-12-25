<script type="text/javascript" src="view/theme/frio/js/mod_admin.js?v={{constant('\Friendica\App::VERSION')}}"></script>
<link rel="stylesheet" href="view/theme/frio/css/mod_admin.css?v={{constant('\Friendica\App::VERSION')}}" type="text/css" media="screen"/>

<div id="admin-users" class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}} ({{$count}})</h1>

	<form action="{{$baseurl}}/{{$query_string}}" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

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
			{{foreach $users as $u}}
				<tr>
					<td><img class="avatar-nano" src="{{$u.micro}}" title="{{$u.nickname}}"></td>
					<td><a href="{{$u.url}}" title="{{$u.nickname}}">{{$u.name}}</a></td>
					<td>{{$u.email}}</td>
					<td>{{$u.deleted}}</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
		{{$pager nofilter}}
	</form>
</div>
