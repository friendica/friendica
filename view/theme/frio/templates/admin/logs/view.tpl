{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<h2>{{$logname}}</h2>
	{{if $error }}
		<div id="admin-error-message-wrapper" class="alert alert-warning">
			<p>{{$error nofilter}}</p>
		</div>
	{{else}}
		<form method="get" class="row">
			<div class="col-xs-8">
				<div class="form-group form-group-search">
					<input accesskey="s" id="nav-search-input-field" class="form-control form-search"
						type="text" name="q" data-toggle="tooltip" title="{{$l10n.Search_in_logs}}"
						placeholder="{{$l10n.Search}}" value="{{$q}}">
					<button class="btn btn-default btn-sm form-button-search"
						type="submit">{{$l10n.Search}}</button>
				</div>
			</div>
			<div class="xol-xs-4">
				<a href="{{$baseurl}}/admin/logs/view" class="btn btn-default">{{$l10n.Show_all}}</a>
			</div>
		</form>

		<table class="table table-hover table-logs">
			<thead>
				<tr>
					<th>{{$l10n.Date}}</th>
					<th class="dropdown">
						<a class="dropdown-toggle text-nowrap" type="button" id="level" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							{{$l10n.Level}} {{if $filters.level}}({{$filters.level}}){{/if}}<span class="caret"></span>
						</a>
						<ul class="dropdown-menu" aria-labelledby="level">
							{{foreach $filtersvalues.level as $v }}
								<li {{if $filters.level == $v}}class="active"{{/if}}>
									<a href="{{$baseurl}}/admin/logs/view?level={{$v}}" data-filter="level" data-filter-value="{{$v}}">
										{{if $v == ""}}{{$l10n.ALL}}{{/if}}{{$v}}
									</a>
								</li>
							{{/foreach}}
						</ul>
					</th>
					<th class="dropdown">
						<a class="dropdown-toggle text-nowrap" type="button" id="context" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							{{$l10n.Context}} {{if $filters.context}}({{$filters.context}}){{/if}}<span class="caret"></span>
						</a>
						<ul class="dropdown-menu" aria-labelledby="context">
							{{foreach $filtersvalues.context as $v }}
								<li {{if $filters.context == $v}}class="active"{{/if}}>
									<a href="{{$baseurl}}/admin/logs/view?context={{$v}}" data-filter="context" data-filter-value="{{$v}}">
										{{if $v == ""}}{{$l10n.ALL}}{{/if}}{{$v}}
									</a>
								</li>
							{{/foreach}}
						</ul>
					</th>
					<th>{{$l10n.Message}}</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $data as $row}}
				<tr id="ev-{{$row->id}}" class="log-event"
					role="button" tabIndex="0"
					aria-label="{{$l10n.View_details}}" aria-haspopup="true" aria-expanded="false"
					data-data="{{$row->data}}" data-source="{{$row->source}}">
					<td>{{$row->date}}</td>
					{{assign var="class" value="text-info"}}
					{{if $row->level == "EMERGENCY" || $row->level == "ALERT" || $row->level == "CRITICAL" || $row->level == "ERROR"}}
						{{assign var="class" value="text-danger"}}
					{{elseif $row->level == "WARNING"}}
						{{assign var="class" value="text-warning"}}
					{{elseif $row->level == "NOTICE" || $row->level == "INFO"}}
						{{assign var="class" value="text-info"}}
					{{else}}
						{{assign var="class" value="text-muted"}}
					{{/if}}
					<td class="{{$class}}">{{$row->level}}</td>
					<td>{{$row->context}}</td>
					<td class="log-message">{{$row->message}}</td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
	{{/if}}
</div>

<div id="logdetail" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg" style="width:90%" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">{{$l10n.Event_details}}</h4>
			</div>
			<div class="modal-body">
				<table class="table main-data">
					<thead>
						<tr>
							<th>{{$l10n.Date}}</th>
							<th>{{$l10n.Level}}</th>
							<th>{{$l10n.Context}}</th>
							<th>{{$l10n.Message}}</th>
						</tr>
					</thead>
					<tbody><tr></tr></tbody>
				</table>
				<table class="table source-data">
					<thead>
						<tr>
							<th>{{$l10n.File}}</th>
							<th>{{$l10n.Line}}</th>
							<th>{{$l10n.Function}}</th>
							<th>{{$l10n.UID}}</th>
							<th>{{$l10n.Process_ID}}</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td data-value="file"></td>
							<td data-value="line"></td>
							<td data-value="function" style="width:70%"></td>
							<td data-value="uid"></td>
							<td data-value="process_id"></td>
						</tr>
					</tbody>
				</table>

				<h3 class="event-data-header">{{$l10n.Data}}</h3>
				<div class="event-source">
				</div>
				<div class="event-data">
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-previous>&lt;</button>
				<button type="button" class="btn btn-default" data-next>&gt;</button>
				<button type="button" class="btn btn-primary" data-dismiss="modal">{{$l10n.Close}}</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
