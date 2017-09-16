<script>
	// update pending count //
	$(function(){
		$("nav").bind('nav-update', function(e,data){
			var elm = $('#pending-update');
			var register = $(data).find('register').text();
			if (register=="0") { register = ""; }
			elm.html(register);
		});
	});
</script>

<div class="widget">
	<h3><a href="{{$admurl}}">{{$admtxt}}</a></h3>

	<ul role="menu">
		{{foreach $subpages as $name => $item}}
		<li role="menuitem" class="{{$item.2}}">
			<a href="{{$item.0}}" {{if $item.accesskey}}accesskey="{{$item.accesskey}}"{{/if}}>
				{{$item.1}}
				{{if $name == "users"}}
				 <span id="pending-update" class="badge pull-right"></span>
				{{/if}}
			</a>
		</li>
		{{/foreach}}
	</ul>

	{{if $admin.update}}
	<ul role="menu">
		<li role="menuitem" class="{{$admin.update.2}}">
			<a href="{{$admin.update.0}}" {{if $admin.update.accesskey}}accesskey="{{$admin.update.accesskey}}"{{/if}}>
				{{$admin.update.1}}
			</a>
		</li>
	</ul>
	{{/if}}
</div>

{{if $admin.plugins_admin}}
<div class="widget">
	<h3>{{$plugadmtxt}}</h3>
	<ul role="menu">
		{{foreach $admin.plugins_admin as $name => $item}}
		<li role="menuitem" class="{{$item.2}}">
			<a href="{{$item.0}}" {{if $item.accesskey}}accesskey="{{$item.accesskey}}"{{/if}}>
				{{$item.1}}
			</a>
		</li>
		{{/foreach}}
	</ul>
</div>
{{/if}}

<div class="widget">
	<h3>{{$logtxt}}</h3>
	<ul role="menu">
		<li role="menuitem" class="{{$admin.logs.2}}">
			<a href="{{$admin.logs.0}}" {{if $admin.logs.accesskey}}accesskey="{{$admin.logs.accesskey}}"{{/if}}>
				{{$admin.logs.1}}
			</a>
		</li>
		<li role="menuitem" class="{{$admin.viewlogs.2}}">
			<a href="{{$admin.viewlogs.0}}" {{if $admin.viewlogs.accesskey}}accesskey="{{$admin.viewlogs.accesskey}}"{{/if}}>
				{{$admin.viewlogs.1}}
			</a>
		</li>
	</ul>
</div>

<div class="widget">
	<h3>{{$diagnosticstxt}}</h3>
	<ul role="menu">
		<li role="menuitem" class="{{$admin.diagnostics_probe.2}}">
			<a href="{{$admin.diagnostics_probe.0}}" {{if $admin.diagnostics_probe.accesskey}}accesskey="{{$admin.diagnostics_probe.accesskey}}"{{/if}}>
				{{$admin.diagnostics_probe.1}}
			</a>
		</li>
		<li role="menuitem" class="{{$admin.diagnostics_webfinger.2}}">
			<a href="{{$admin.diagnostics_webfinger.0}}" {{if $admin.viewlogs.accesskey}}accesskey="{{$admin.diagnostics_webfinger.accesskey}}"{{/if}}>
				{{$admin.diagnostics_webfinger.1}}
			</a>
		</li>
	</ul>
</div>
