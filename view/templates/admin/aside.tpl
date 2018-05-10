<script>
	// update pending count //
	$(function(){

		$("nav").bind('nav-update',  function(e,data){
			var elm = $('#pending-update');
			var register = $(data).find('register').text();
			if (register=="0") { register=""; elm.hide();} else { elm.show(); }
			elm.html(register);
		});
	});
</script>

{{foreach $subpages as $page}}
<h4>{{$page.0}}</h4>
<ul class="admin linklist" role="menu">
{{foreach $page.1 as $item}}
	<li class='admin link button {{$item.2}}' role="menuitem"><a href='{{$item.0}}'>{{$item.1}}</a></li>
{{/foreach}}
</ul>
{{/foreach}}



{{if $admin.update}}
<ul class='admin linklist'>
	<li class='admin link button {{$admin.update.2}}'><a href='{{$admin.update.0}}'>{{$admin.update.1}}</a></li>
</ul>
{{/if}}


{{if $admin.addons_admin}}<h4>{{$plugadmtxt}}</h4>{{/if}}
<ul class='admin linklist'>
	{{foreach $admin.addons_admin as $l}}
	<li class='admin link button {{$l.2}}'><a href='{{$l.0}}'>{{$l.1}}</a></li>
	{{/foreach}}
</ul>
	
	
