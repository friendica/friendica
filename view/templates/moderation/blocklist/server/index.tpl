{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script>
	function confirm_delete(uname){
		return confirm("{{$l10n.confirm_delete}}".format(uname));
	}
</script>
<div id="adminpage">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<p>{{$l10n.intro}}</p>
	<p>{{$l10n.public nofilter}}</p>

	<h2>{{$l10n.importtitle}}</h2>
    {{$l10n.download nofilter}}

	<form action="{{$baseurl}}/moderation/blocklist/server/import" method="post" enctype="multipart/form-data">
		<input type="hidden" name="form_security_token" value="{{$form_security_token_import}}">
        {{include file="field_input.tpl" field=$listfile}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_upload">{{$l10n.importsubmit}}</button>
		</div>
	</form>

	<h2>{{$l10n.addtitle}}</h2>
    {{$l10n.syntax nofilter}}
	<form action="{{$baseurl}}/moderation/blocklist/server/add" method="get">
		{{include file="field_input.tpl" field=$newdomain}}
		<div class="submit">
			<button type="submit" class="btn btn-primary">{{$l10n.addsubmit}}</button>
		</div>
	</form>

	<h2>{{$l10n.currenttitle}}</h2>
	<form action="{{$baseurl}}/moderation/blocklist/server" method="get" class="form-inline" style="margin-bottom: 1em;">
		<div class="form-group">
			<label for="serverblocklist_search">{{$l10n.search_label}}</label>
			<input id="serverblocklist_search" class="form-control" type="text" name="search" value="{{$search}}">
		</div>
		<button type="submit" class="btn btn-primary">{{$l10n.search_submit}}</button>
		{{if $search}}<a href="{{$baseurl}}/moderation/blocklist/server" class="btn btn-default">{{$l10n.search_reset}}</a>{{/if}}
	</form>

	{{if $entries}}
	<form action="{{$baseurl}}/moderation/blocklist/server" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		<input type="hidden" name="search" value="{{$search}}">
		<table id="serverblocklist">
			<thead>
				<tr>
					<th></th>
					<th>{{$l10n.thurl}}</th>
					<th>{{$l10n.threason}}</th>
				</tr>
			</thead>
			<tbody>
			{{foreach $entries as $e}}
				<tr>
					<td class="checkbox">
						<input type="hidden" name="{{$e.delete.0}}" value="0">
						<input type="checkbox" name="{{$e.delete.0}}" id="id_{{$e.delete.0}}" value="1" {{if $e.delete.2}}checked{{/if}} {{$e.delete.4 nofilter}}>
					</td>
					<td>{{include file="field_input.tpl" field=$e.domain label=false}}</td>
					<td>{{include file="field_input.tpl" field=$e.reason label=false}}</td>
				</tr>
			{{/foreach}}
			</tbody>
		</table>
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_edit" value="{{$l10n.savechanges}}">{{$l10n.savechanges}}</button>
		</div>
	</form>
	{{else}}
	<p>{{if $search}}{{$l10n.no_entries_filtered}}{{else}}{{$l10n.no_entries}}{{/if}}</p>
	{{/if}}
</div>
