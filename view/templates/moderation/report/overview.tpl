{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$description nofilter}}</p>
	<p>
		{{if $filter_status == 'open'}}
			<strong>{{$open_reports}}</strong>
		{{else}}
			<a href="moderation/reports?status=open&category={{$category_filter_value}}&assigned={{$filter_assigned}}">{{$open_reports}}</a>
		{{/if}} |
		{{if $filter_status == 'closed'}}
			<strong>{{$closed_reports}}</strong>
		{{else}}
			<a href="moderation/reports?status=closed&category={{$category_filter_value}}&assigned={{$filter_assigned}}">{{$closed_reports}}</a>
		{{/if}} |
		{{if $filter_status == 'all'}}
			<strong>{{$all_reports}}</strong>
		{{else}}
			<a href="moderation/reports?status=all&category={{$category_filter_value}}&assigned={{$filter_assigned}}">{{$all_reports}}</a>
		{{/if}}
	</p>
	<p>
		{{foreach $category_filters as $category_filter}}
			{{if $category_filter.selected}}
				<strong>{{$category_filter.label}}</strong>
			{{else}}
				<a href="moderation/reports?status={{$filter_status}}&category={{$category_filter.value}}&assigned={{$filter_assigned}}">{{$category_filter.label}}</a>
			{{/if}}{{if !$category_filter.last}} | {{/if}}
		{{/foreach}}
	</p>
	<p>
		{{foreach $assigned_filters as $assigned_filter}}
			{{if $assigned_filter.selected}}
				<strong>{{$assigned_filter.label}}</strong>
			{{else}}
				<a href="moderation/reports?status={{$filter_status}}&category={{$category_filter_value}}&assigned={{$assigned_filter.value}}">{{$assigned_filter.label}}</a>
			{{/if}}{{if !$assigned_filter.last}} | {{/if}}
		{{/foreach}}
	</p>

	<h3>{{$h_reports}}</h3>
	{{if $reports}}
		<form method="post">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table class="table table-condensed table-striped table-bordered">
			<thead>
				<tr>
					<th>
					<input type="checkbox" id="select-all-reports" title="{{$select_all}}">
					</th>
					{{foreach $th_reports as $th}}
					<th>
					{{$th}}
					</th>
					{{/foreach}}
				</tr>
			</thead>
			<tbody>
				{{foreach $reports as $report}}
				<tr>
					<td>
						<input type="checkbox" name="report_ids[]" value="{{$report.id}}" class="report-checkbox">
					</td>
					<td>
							<a href="{{$report.detail_url}}">{{$report.created}}</a>
					</td>
					<td><img class="icon" src="{{$report.micro}}" alt="{{$report.nickname}}" title="{{$report.nickname}}"></td>
					<td class="name">
							{{$report.name}}<br>
						<a href="contact/{{$report.cid}}" title="{{$report.nickname}}">{{if $report.nick}}{{$report.nick}}{{else}}{{$report.name}}{{/if}}</a><br>
						<a href="{{$report.url}}" title="{{$report.nickname}}">{{if $report.addr}}{{$report.addr}}{{else}}{{$report.url}}{{/if}}</a>
					</td>
					<td class="comment">{{if $report.comment}}{{$report.comment}}{{else}}N/A{{/if}}</td>
					<td class="category">{{if $report.category}}{{$report.category}}{{else}}N/A{{/if}}</td>
					<td class="status">{{$report.status_label}}</td>
				</tr>
				{{if $report.posts}}
				<tr>
					<td colspan="7">
					<table class="table table-condensed table-striped table-bordered">
					{{foreach $report.posts as $post}}
						<tr>
						<td>
							<a href="display/{{$post.guid}}">{{$post.created}}</a><br>
						</td>
						<td>
							{{$post.body}}
						</td>
						</tr>
					{{/foreach}}
					</table>
					</td>
				</tr>
				{{/if}}
				{{/foreach}}
			</tbody>
		</table>
		<button type="submit" name="close_reports" value="1" class="btn btn-primary">{{$close_reports}}</button>
		</form>
		{{$paginate nofilter}}
	{{else}}
		<p>{{$no_data}}</p>
	{{/if}}
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var selectAllCheckbox = document.getElementById('select-all-reports');
	var reportCheckboxes = document.querySelectorAll('.report-checkbox');

	if (selectAllCheckbox) {
		selectAllCheckbox.addEventListener('change', function() {
			reportCheckboxes.forEach(function(checkbox) {
				checkbox.checked = selectAllCheckbox.checked;
			});
		});

		reportCheckboxes.forEach(function(checkbox) {
			checkbox.addEventListener('change', function() {
				var allChecked = Array.from(reportCheckboxes).every(function(cb) {
					return cb.checked;
				});
				selectAllCheckbox.checked = allChecked;
			});
		});
	}
});
</script>
