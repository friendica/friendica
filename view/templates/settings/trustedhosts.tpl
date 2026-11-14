{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="settings-trustedhosts" class="generic-page-wrapper">
	<h1>{{$l10n.title}} ({{$count}})</h1>
	<div class="settings-section">
		<p>{{$l10n.desc}}</p>

		{{$paginate nofilter}}

		{{if $count == 0}}
			<em>{{$no_hosts}}</em>
		{{else}}
			<form action="" method="POST">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

				<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>

				<table class="table table-striped table-condensed table-bordered">
					<tr>
						<th>{{$l10n.host}}</th>
						<th>{{$l10n.delete}}</th>
					</tr>

		{{foreach $hosts as $index => $host}}
					<tr>
						<td>{{$host}}</td>
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
</div>
