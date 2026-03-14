{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

	<p>{{$logname}}</p>
	{{if $error }}
		<div id="admin-error-message-wrapper" class="alert alert-warning">
			<p>{{$error nofilter}}</p>
		</div>
	{{else}}
		<form>
			<p>
				<input type="search" name="q" value="{{$q}}" placeholder="{{$l10n.Search}}"></input>
				<input type="submit" value="{{$l10n.Search}}">
				<a href="{{$baseurl}}/admin/logs/view">{{$l10n.Show_all}}</a>
			</p>


			<table>
				<thead>
					<tr>
						<th>{{$l10n.Date}}</th>
						<th>
							<select name="level" onchange="this.form.submit()">
								{{foreach $filtersvalues.level as $v }}
								<option {{if $filters.level == $v}}selected{{/if}} value="{{$v}}">
									{{if $v == ""}}Level{{/if}}
									{{$v}}
								</option>
								{{/foreach}}
							</select>
						</th>
						<th>
							<select name="context" onchange="this.form.submit()">
								{{foreach $filtersvalues.context as $v }}
								<option {{if $filters.context == $v}}selected{{/if}} value="{{$v}}">
									{{if $v == ""}}Context{{/if}}
									{{$v}}
								</option>
								{{/foreach}}
							</select>
						</th>
						<th>{{$l10n.Message}}</th>
					</tr>
				</thead>
				<tbody>
					{{foreach $data as $row}}
						<tr id="ev-{{$row->id}}" class="log-event"
						 role="button" tabIndex="0"
						 aria-label="{{$l10n.View_details}}" aria-haspopup="true" aria-expanded="false"
						 style="cursor:pointer;"
						 title="{{$l10n.Click_to_view_details}}">
							<td>{{$row->date}}</td>
							<td class="loglevel-{{$row->level|lower}}">{{$row->level}}</td>
							<td>{{$row->context}}</td>
							<td>{{$row->message}}</td>
						</tr>
						<tr class="hidden" data-id="ev-{{$row->id}}"><th colspan="4">{{$l10n.Event_details}}</th></tr>
						{{foreach $row->getData() as $k=>$v}}
							<tr class="hidden" data-id="ev-{{$row->id}}">
								<th>{{$k}}</th>
								<td colspan="3">
									<pre>{{$v nofilter}}</pre>
								</td>
							</tr>
						{{/foreach}}
						<tr class="hidden" data-id="ev-{{$row->id}}"><th colspan="4">{{$l10n.Source}}</th></tr>
						{{foreach $row->getSource() as $k=>$v}}
							<tr class="hidden" data-id="ev-{{$row->id}}">
								<th>{{$k}}</th>
								<td colspan="3">{{$v}}</td>
							</tr>
						{{/foreach}}
					{{/foreach}}
				</tbody>
			</table>
		</form>
	{{/if}}
</div>
