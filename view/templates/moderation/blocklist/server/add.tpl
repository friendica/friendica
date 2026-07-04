{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<p><a href="{{$baseurl}}/moderation/blocklist/server">{{$l10n.return_list}}</a></p>
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	{{$l10n.syntax nofilter}}

	<form action="{{$baseurl}}/moderation/blocklist/server/add" method="get">
		{{include file="field_input.tpl" field=$newdomain}}
		<div class="submit">
			<button type="submit" class="btn btn-primary">{{$l10n.submit}}</button>
		</div>
	</form>
{{if $pattern}}
	<h2>{{$l10n.matching_servers}}</h2>
	<form action="{{$baseurl}}/moderation/blocklist/server/add" method="post">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
        <input type="hidden" name="pattern" value="{{$pattern}}">
		<table class="table table-condensed table-striped table-bordered">
			<thead>
				<tr>
					<th></th>
					<th>{{$l10n.server_name}}</th>
					<th>{{$l10n.server_domain}}</th>
					<th>{{$l10n.known_contacts}}</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">{{$l10n.server_count}}</td>
				</tr>
			</tfoot>
			<tbody>
            {{foreach $gservers as $gserver}}
				<tr>
					<td class="text-center">
						<span class="network-label icon">
							<img class="network-svg" src="{{$gserver.network_svg}}" alt="{{$gserver.network_name}}" title="{{$gserver.network_name}}" loading="lazy"/>
						</span>
					</td>
					<th>{{$gserver.site_name|default:$gserver.domain}}</th>
					<td>
						<a href="{{$gserver.url}}" target="_blank" rel="noreferrer noopener">{{$gserver.domain}} <i class="ri ri-external-link-line"></i></a>
					</td>
					<td class="text-right">{{$gserver.contacts}} <i class="ri ri-user-line"></i></td>
				</tr>
            {{/foreach}}
			</tbody>
		</table>

		{{include file="field_checkbox.tpl" field=$newpurge}}
		{{include file="field_input.tpl" field=$newreason}}
		<div class="submit">
			<button type="submit" class="btn btn-primary" name="page_blocklist_add" value="{{$l10n.add_pattern}}">{{$l10n.add_pattern}}</button>
		</div>
	</form>
{{/if}}
</div>
