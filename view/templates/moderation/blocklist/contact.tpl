{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script>
	function selectall(cls) {
		$('.' + cls).prop('checked', true);
		return false;
	}
	function selectnone(cls) {
		$('.' + cls).prop('checked', false);
		return false;
	}
</script>
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$description nofilter}}</p>

	<h3>{{$h_newblock}}</h3>
	<p>
		<a href="{{$baseurl}}/moderation/blocklist/contact/import" class="btn btn-primary">{{$import_csv}}</a>
		<a href="{{$baseurl}}/moderation/blocklist/contact/export" class="btn btn-default">{{$export_csv}}</a>
	</p>

	<form action="{{$baseurl}}/moderation/blocklist/contact" method="post">
        <input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<h3>{{$h_contacts}}</h3>
	{{if $contacts}}
		<table id="contactblock">
			<thead>
				<tr>
					<th></th>
						{{foreach $th_contacts as $th}}
					<th>
						{{$th}}
					</th>
					{{/foreach}}
				</tr>
			</thead>
			<tbody>
				{{foreach $contacts as $contact}}
				<tr>
					<td class="checkbox"><input type="checkbox" class="contacts_ckbx" id="id_contact_{{$contact.id}}" name="contacts[]" value="{{$contact.id}}"/></td>
					<td><img class="icon" src="{{$contact.micro}}" alt="{{$contact.nickname}}" title="{{$contact.nickname}}"></td>
					<td class="name">
						{{$contact.name}}<br>
						<a href="{{$contact.url}}" title="{{$contact.nickname}}">{{$contact.addr}}</a>
					</td>
					<td class="reason">{{if $contact.block_reason}}{{$contact.block_reason}}{{else}}N/A{{/if}}</td>
				</tr>
				{{/foreach}}
			</tbody>
		</table>
		<p><a href="#" onclick="return selectall('contacts_ckbx');">{{$select_all}}</a> | <a href="#" onclick="return selectnone('contacts_ckbx');">{{$select_none}}</a></p>
		{{$paginate nofilter}}
		<div class="submit"><input type="submit" name="page_contactblock_unblock" value="{{$unblock}}" /></div>
	{{else}}
		<p>{{$no_data}}</p>
	{{/if}}
	</form>

	<h3>{{$h_newblock}}</h3>
	<form action="{{$baseurl}}/moderation/blocklist/contact" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<table id="contactblock">
			<tbody>
				<tr>
					<td>{{include file="field_input.tpl" field=$contacturl}}</td>
					<td>{{include file="field_checkbox.tpl" field=$contact_block_purge}}</td>
					<td>{{include file="field_textarea.tpl" field=$contact_block_reason}}</td>
				</tr>
			</tbody>
		</table>
		<div class="submit"><input type="submit" name="page_contactblock_block" value="{{$submit}}" /></div>
	</form>
</div>
