
<div id='adminpage-summery' class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>

	{{if $showwarning}}
	<div id="admin-warning-message-wrapper" class="alert alert-warning">
		{{foreach $warningtext as $wt}}
		<p>{{$wt}}</p>
		{{/foreach}}
	</div>
	{{/if}}

	<div id="admin-summary-wrapper">
		{{* The work queues short statistic. *}}
		<div id="admin-summary-queues" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$queues.label}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry"><a href="{{$baseurl}}/admin/queue">{{$queues.queue}}</a> - {{$queues.workerq}}</div>
		</div>

		{{* Number of pending registrations. *}}
		<div id="admin-summary-pending" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$pending.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">{{$pending.1}}</div>
		</div>

		{{* Number of registered users *}}
		<div id="admin-summary-users" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$users.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">{{$users.1}}</div>
		</div>

		{{* Account types of registered users. *}}
		{{foreach $accounts as $p}}
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$p.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">{{if $p.1}}{{$p.1}}{{else}}0{{/if}}</div>
		</div>
		{{/foreach}}

		{{* List enabled addons. *}}
		<div id="admin-summary-addons" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$addons.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">
				{{foreach $addons.1 as $p}}
				<a href="/admin/addons/{{$p}}/">{{$p}}</a><br>
				{{/foreach}}
			</div>
		</div>

		{{* The Friendica version. *}}
		<div id="admin-summary-version" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 admin-summary">
			<hr class="admin-summary-separator">
			<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12 admin-summary-label-name text-muted">{{$version.0}}</div>
			<div class="col-lg-8 col-md-8 col-sm-8 col-xs-12 admin-summary-entry">{{$platform}} '{{$codename}}' {{$version.1}} - {{$build}}</div>
		</div>
	</div>

	<div class="clear"></div>

</div>
