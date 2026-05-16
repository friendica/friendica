{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="settings-server" class="generic-page-wrapper">
	<h1>{{$l10n.title}} ({{$count}})</h1>

	<p>{{$l10n.desc1 nofilter}}</p>
	<p>{{$l10n.desc2}}</p>

	{{$paginate nofilter}}

	{{if $count == 0}}
		<em>{{$no_servers}}</em>
	{{else}}
		<form action="" method="POST">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

			<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>

			<table class="table table-striped table-condensed table-bordered">
				<tr>
					<th>{{$l10n.siteName}}</th>
					<th><span title="{{$l10n.ignored_title}}">{{$l10n.ignored}} <i class="fa fa-question-circle icon-question-sign"></i></span></th>
					<th>
						<span title="{{$l10n.delete_title}}">
							<i class="fa fa-trash icon-trash" aria-hidden="true" title="{{$l10n.delete}}"></i>
							<span class="sr-only">{{$l10n.delete}}</span>
							<i class="fa fa-question-circle icon-question-sign"></i>
						</span>
					</th>
				</tr>

	{{foreach $servers as $index => $server}}
				<tr>
					<td>
						<a href="{{$server->gserver->url}}">{{($server->gserver->siteName) ? $server->gserver->siteName : $server->gserver->url}} <i class="fa fa-external-link"></i></a>
					</td>
					<td>
										{{include file="field_checkbox.tpl" field=$ignoredCheckboxes[$index]}}
					</td>
					<td>
										{{include file="field_checkbox.tpl" field=$deleteCheckboxes[$index]}}
					</td>
				</tr>
	{{/foreach}}

			</table>
			<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>
		</form>

		{{$paginate nofilter}}
	{{/if}}
</div>
