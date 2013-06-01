<script>
	function confirm_delete(uname){
		return confirm( "{{$confirm_delete}}".format(uname));
	}
	function confirm_delete_multi(){
		return confirm("{{$confirm_delete_multi}}");
	}
	function selectall(cls){
		$("."+cls).attr('checked','checked');
		return false;
	}
</script>
<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
	
	<form action="{{$baseurl}}/admin/users" method="post">
        <input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		
		<h3>{{$h_pending}}</h3>
		{{if $pending}}
			<table id='pending'>
				<thead>
				<tr>
					{{foreach $th_pending as $th}}<th>{{$th}}</th>{{/foreach}}
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
			{{foreach $pending as $u}}
				<tr>
					<td class="created">{{$u.created}}</td>
					<td class="name">{{$u.name}}</td>
					<td class="email">{{$u.email}}</td>
					<td class="checkbox"><input type="checkbox" class="pending_ckbx" id="id_pending_{{$u.hash}}" name="pending[]" value="{{$u.hash}}" /></td>
					<td class="tools">
						<a href="{{$baseurl}}/regmod/allow/{{$u.hash}}" title='{{$approve}}'><span class='tool like'></span></a>
						<a href="{{$baseurl}}/regmod/deny/{{$u.hash}}" title='{{$deny}}'><span class='tool dislike'></span></a>
					</td>
				</tr>
			{{/foreach}}
				</tbody>
			</table>
			<div class='selectall'><a href='#' onclick="return selectall('pending_ckbx');">{{$select_all}}</a></div>
			<div class="submit"><input type="submit" name="page_users_deny" value="{{$deny}}"/> <input type="submit" name="page_users_approve" value="{{$approve}}" /></div>			
		{{else}}
			<p>{{$no_pending}}</p>
		{{/if}}
	
	
		
	
		<h3>{{$h_users}}</h3>
		{{if $users}}
			<table id='users'>
				<thead>
				<tr>
					<th></th>
					{{foreach $th_users as $th}}<th>{{$th}}</th>{{/foreach}}
					<th></th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				{{foreach $users as $u}}
					<tr>
						<td><img src="{{$u.micro}}" alt="{{$u.nickname}}" title="{{$u.nickname}}"></td>
						<td class='name'><a href="{{$u.url}}" title="{{$u.nickname}}" >{{$u.name}}</a></td>
						<td class='email'>{{$u.email}}</td>
						<td class='register_date'>{{$u.register_date}}</td>
						<td class='login_date'>{{$u.login_date}}</td>
						<td class='lastitem_date'>{{$u.lastitem_date}}</td>
						<td class='login_date'>{{$u.page_flags}} {{if $u.is_admin}}({{$siteadmin}}){{/if}} {{if $u.account_expired}}({{$accountexpired}}){{/if}}</td>
						<td class="checkbox"> 
                                    {{if $u.is_admin}}
                                        &nbsp;
                                    {{else}}
                                        <input type="checkbox" class="users_ckbx" id="id_user_{{$u.uid}}" name="user[]" value="{{$u.uid}}"/></td>
                                    {{/if}}
						<td class="tools">
                                    {{if $u.is_admin}}
                                        &nbsp;
                                    {{else}}
                                        <a href="{{$baseurl}}/admin/users/block/{{$u.uid}}?t={{$form_security_token}}" title='{{if $u.blocked}}{{$unblock}}{{else}}{{$block}}{{/if}}'><span class='icon block {{if $u.blocked==0}}dim{{/if}}'></span></a>
                                        <a href="{{$baseurl}}/admin/users/delete/{{$u.uid}}?t={{$form_security_token}}" title='{{$delete}}' onclick="return confirm_delete('{{$u.name}}')"><span class='icon drop'></span></a>
                                    {{/if}}
						</td>
					</tr>
				{{/foreach}}
				</tbody>
			</table>
			<div class='selectall'><a href='#' onclick="return selectall('users_ckbx');">{{$select_all}}</a></div>
			<div class="submit"><input type="submit" name="page_users_block" value="{{$block}}/{{$unblock}}" /> <input type="submit" name="page_users_delete" value="{{$delete}}" onclick="return confirm_delete_multi()" /></div>						
		{{else}}
			NO USERS?!?
		{{/if}}
	</form>
</div>
