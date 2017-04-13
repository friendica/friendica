
<div id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>
{{if $showwarning}}
	<div id="admin-warning-message-wrapper">
		{{foreach $warningtext as $wt}}
		<p class="warning-message">{{$wt}}</p>
		{{/foreach}}
	</div>
{{/if}}

	<dl>
		<dt>{{$queues.label}}</dt>
		<dd><a href="{{$baseurl}}/admin/queue">{{$queues.queue}}</a> - {{$queues.workerq}}</dd>
	</dl>
	<dl>
		<dt>{{$pending.0}}</dt>
		<dd>{{$pending.1}}</dt>
	</dl>

	<dl>
		<dt>{{$users.0}}</dt>
		<dd>{{$users.1}}</dd>
	</dl>
	{{foreach $accounts as $p}}
		<dl>
			<dt>{{$p.0}}</dt>
			<dd>{{if $p.1}}{{$p.1}}{{else}}0{{/if}}</dd>
		</dl>
	{{/foreach}}


	<dl>
		<dt>{{$plugins.0}}</dt>
		
		{{foreach $plugins.1 as $p}}
			<dd><a href="/admin/plugins/{{$p}}/">{{$p}}</a></dd>
		{{/foreach}}
		
	</dl>

	<dl>
		<dt>{{$version.0}}</dt>
		<dd> {{$platform}} '{{$codename}}' {{$version.1}} - {{$build}}</dt>
	</dl>


</div>
