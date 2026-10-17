{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p><a href="{{$back_to_reports_url}}">{{$back_to_reports}}</a></p>

	<div class="panel panel-default">
		<div class="panel-heading"><strong>{{$report.category}}</strong> - {{$report.status_label}}</div>
		<div class="panel-body">
			<p><strong>{{$report.created}}</strong>{{if $report.edited}} · {{$report.edited}}{{/if}}</p>
			<p><strong>{{$report.comment}}</strong></p>
			<p>{{if $report.forward}}{{$forwarded}}{{else}}{{$not_forwarded}}{{/if}}</p>
			<p>{{if $report.assigned_uid}}{{$assigned_user_id}} {{$report.assigned_uid}}{{else}}{{$unassigned}}{{/if}}</p>
			{{if $report.reporter}}
			<p><strong>{{$reporter_label}}</strong> {{$report.reporter.name}} ({{$report.reporter.nick}})</p>
			{{/if}}
			{{if $report.target}}
			<p><strong>{{$target_label}}</strong> {{$report.target.name}} ({{$report.target.nick}})</p>
			{{/if}}
		</div>
	</div>

		{{if !$report.is_final}}
		<div class="panel panel-default">
			<div class="panel-heading"><strong>{{$category_and_rules}}</strong></div>
			<div class="panel-body">
				<form method="post">
					<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
					<input type="hidden" name="report_id" value="{{$report.id}}">
					<input type="hidden" name="report_action" value="save_metadata">
					<div class="form-group">
						<label for="category">{{$category_label}}</label>
						<select id="category" name="category" class="form-control">
							{{foreach $report.category_options as $option}}
							<option value="{{$option.value}}"{{if $option.selected}} selected{{/if}}>{{$option.label}}</option>
							{{/foreach}}
						</select>
					</div>
					{{if $report.rules_available}}
					<div class="form-group">
						<label>{{$node_rules}}</label>
						<div>
							{{foreach $report.rules_available as $rule}}
								<div class="checkbox">
									<label>
										<input type="checkbox" name="rule_ids[]" value="{{$rule.line_id}}"{{if $rule.selected}} checked{{/if}}>
										{{$rule.text}}
									</label>
								</div>
							{{/foreach}}
						</div>
					</div>
					{{/if}}
					<button type="submit" class="btn btn-primary">{{$save_metadata}}</button>
				</form>

				{{if $report.rules}}
				<hr>
				<ul>
					{{foreach $report.rules as $rule}}
					<li>{{$rule.text}}</li>
					{{/foreach}}
				</ul>
				{{/if}}
			</div>
		</div>
		{{else}}
		<div class="panel panel-default">
			<div class="panel-heading"><strong>{{$category_and_rules}}</strong></div>
			<div class="panel-body">
				<p>{{$report.category}}</p>
				{{if $report.rules}}
				<ul>
					{{foreach $report.rules as $rule}}
					<li>{{$rule.text}}</li>
					{{/foreach}}
				</ul>
				{{/if}}
			</div>
		</div>
		{{/if}}

	{{if $report.posts}}
	<div class="panel panel-default">
		<div class="panel-heading"><strong>{{$attached_posts}}</strong></div>
		<div class="panel-body">
			<ul>
				{{foreach $report.posts as $post}}
				<li>
					<strong>{{$uri_id_label}} {{$post.uri_id}}</strong>{{if $post.status}} · {{$status_label}} {{$post.status}}{{/if}}
					{{if $post.created}} · {{$post.created}}{{/if}}
					{{if $post.guid}} · {{$guid_label}} {{$post.guid}}{{/if}}
					{{if $post.plink}} · <a href="{{$post.plink}}" target="_blank" rel="noopener noreferrer">{{$post.plink}}</a>{{/if}}
					{{if $post.title}}<br>{{$post.title}}{{/if}}
					{{if !$report.is_final}}
					<form method="post" style="margin-top: 0.4em;">
						<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
						<input type="hidden" name="report_id" value="{{$report.id}}">
						<input type="hidden" name="uri_id" value="{{$post.uri_id}}">
						<button type="submit" name="report_action" value="delete_reported_post" class="btn btn-danger btn-xs">{{$delete_post}}</button>
					</form>
					{{/if}}
				</li>
				{{/foreach}}
			</ul>
		</div>
	</div>
	{{/if}}

	<div class="panel panel-default">
		<div class="panel-heading"><strong>{{$actions_heading}}</strong></div>
		<div class="panel-body">
			{{if !$report.is_final}}
			<form method="post" class="form-inline" style="margin-bottom: 1em;">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<input type="hidden" name="report_id" value="{{$report.id}}">
				<button type="submit" name="report_action" value="assign_self" class="btn btn-primary">{{$assign_self}}</button>
				<button type="submit" name="report_action" value="unassign" class="btn btn-default">{{$unassign}}</button>
				<button type="submit" name="report_action" value="resolve" class="btn btn-success">{{$resolve}}</button>
			</form>

			<form method="post" class="form-inline" style="margin-bottom: 1em;">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<input type="hidden" name="report_id" value="{{$report.id}}">
				<button type="submit" name="report_action" value="delete_reported_posts" class="btn btn-danger">{{$delete_posts}}</button>
				{{if $report.target}}
					{{if $report.target_is_local}}
						<button type="submit" name="report_action" value="block_target" class="btn btn-warning">{{$block_local}}</button>
						<button type="submit" name="report_action" value="silence_local_target" class="btn btn-default">{{$silence_local}}</button>
					{{else}}
						<button type="submit" name="report_action" value="block_target" class="btn btn-warning">{{$block_remote}}</button>
						<button type="submit" name="report_action" value="block_target_purge" class="btn btn-warning">{{$block_remote_purge}}</button>
					{{/if}}
				{{/if}}
			</form>

			{{if $report.target && $report.target_is_local}}
			<form method="post" style="margin-bottom: 1em;">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<input type="hidden" name="report_id" value="{{$report.id}}">
				<input type="hidden" name="report_action" value="warn_target">
				<div class="form-group">
					<label for="warning_message">{{$warning_message}}</label>
					<textarea id="warning_message" name="warning_message" class="form-control" rows="3"></textarea>
				</div>
				<button type="submit" class="btn btn-primary">{{$warn_target}}</button>
			</form>
			{{/if}}

			<form method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				<input type="hidden" name="report_id" value="{{$report.id}}">
				<input type="hidden" name="report_action" value="save_remarks">
				<div class="form-group">
					<label for="public_remarks">{{$public_remarks}}</label>
					<textarea id="public_remarks" name="public_remarks" class="form-control" rows="4">{{$report.public_remarks}}</textarea>
				</div>
				<div class="form-group">
					<label for="private_remarks">{{$private_remarks}}</label>
					<textarea id="private_remarks" name="private_remarks" class="form-control" rows="4">{{$report.private_remarks}}</textarea>
				</div>
				<button type="submit" class="btn btn-primary">{{$save_remarks}}</button>
			</form>
			{{else}}
			<p>{{$finalized_read_only}}</p>
			{{/if}}
		</div>
	</div>
</div>