{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<p><a href="{{$baseurl}}/moderation/blocklist/contact">{{$l10n.return_list}}</a></p>
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>

	{{if $notices}}
		{{foreach $notices as $notice}}
			<div class="alert alert-info" role="alert">{{$notice}}</div>
		{{/foreach}}
	{{/if}}
{{if !$contactlist}}
	{{$l10n.download nofilter}}

	<form action="{{$baseurl}}/moderation/blocklist/contact/import" method="post" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{include file="field_input.tpl" field=$listfile}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_contactblock_upload" value="{{$l10n.upload}}">{{$l10n.upload}}</button>
		</div>
	</form>
{{else}}
	<h2>{{$l10n.contacts}}</h2>
	<form action="{{$baseurl}}/moderation/blocklist/contact/import" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<input type="hidden" name="contactlist" value="{{$contactlist|json_encode}}">
		<table class="table table-condensed table-striped table-bordered">
			<thead>
				<tr>
					<th>{{$l10n.contact_url}}</th>
					<th>{{$l10n.block_reason}}</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2">{{$l10n.contact_count}}</td>
				</tr>
			</tfoot>
			<tbody>
				{{foreach $contactlist as $contact}}
				<tr>
					<td>{{$contact.url}}</td>
					<td>{{$contact.reason}}</td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>

		{{include file="field_checkbox.tpl" field=$purge}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_contactblock_import" value="{{$l10n.import}}">{{$l10n.import}}</button>
		</div>
	</form>
{{/if}}
</div>
