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

<h4><a href="{{$admurl}}">{{$admtxt}}</a></h4>
<ul class='admin linklist'>
{{foreach $subpages as $page}}
	<li class='admin link button {{$page.2}}'><a href='{{$page.0}}'>{{$page.1}}</a></li>
{{/foreach}}
</ul>

{{if $admin.update}}
<ul class='admin linklist'>
	<li class='admin link button {{$admin.update.2}}'><a href='{{$admin.update.0}}'>{{$admin.update.1}}</a></li>
	<li class='admin link button {{$admin.update.2}}'><a href='https://kakste.com/profile/inthegit'>Important Changes</a></li>
</ul>
{{/if}}


{{if $admin.addons_admin}}<h4>{{$plugadmtxt}}</h4>{{/if}}
<ul class='admin linklist'>
	{{foreach $admin.addons_admin as $l}}
	<li class='admin link button {{$l.2}}'><a href='{{$l.0}}'>{{$l.1}}</a></li>
	{{/foreach}}
</ul>
	
	
<h4>{{$logtxt}}</h4>
<ul class='admin linklist'>
	<li class='admin link button {{$admin.logs.2}}'><a href='{{$admin.logs.0}}'>{{$admin.logs.1}}</a></li>
	<li class='admin link button {{$admin.viewlogs.2}}'><a href='{{$admin.viewlogs.0}}'>{{$admin.viewlogs.1}}</a></li>
</ul>

<h4>{{$diagnosticstxt}}</h4>
<ul class='admin linklist'>
	<li class='admin link {{$admin.diagnostics_probe.2}}'><a href="{{$admin.diagnostics_probe.0}}">{{$admin.diagnostics_probe.1}}</a></li>
	<li class='admin link {{$admin.diagnostics_webfinger.2}}'><a href="{{$admin.diagnostics_webfinger.0}}">{{$admin.diagnostics_webfinger.1}}</a></li>
</ul>
