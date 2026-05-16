{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$description nofilter}}</p>

	<h3>{{$h_reports}}</h3>
	{{if $reports}}
		<form method="post">
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
						{{$report.created}}
					</td>
					<td><img class="icon" src="{{$report.micro}}" alt="{{$report.nickname}}" title="{{$report.nickname}}"></td>
					<td class="name">
						<a href="contact/{{$report.cid}}" title="{{$report.nickname}}">{{$report.name}}</><br>
						<a href="{{$report.url}}" title="{{$report.nickname}}">{{if $report.addr}}{{$report.addr}}{{else}}{{$report.url}}{{/if}}</a>
					</td>
					<td class="comment">{{if $report.comment}}{{$report.comment}}{{else}}N/A{{/if}}</td>
					<td class="category">{{if $report.category}}{{$report.category}}{{else}}N/A{{/if}}</td>
				</tr>
				{{if $report.posts}}
				<tr>
					<td colspan="6">
					<table class="table table-condensed table-striped table-bordered">
					{{foreach $report.posts as $post}}
						<tr>
						<td>
							<a href="display/{{$post.guid}}">{{$post.created}}</><br>
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
