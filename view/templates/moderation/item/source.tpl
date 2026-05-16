{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="source" class="generic-page-wrapper">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<form action="moderation/item/source" method="get" class="panel panel-default">
		<div class="panel-body">
			<div class="form-group">
				{{include file="field_input.tpl" field=$guid_field}}
			</div>
			<p><button type="submit" class="btn btn-primary">{{$l10n.submit}}</button></p>
		</div>
	</form>

{{if $guid}}
	<div class="itemsource-results">
	{{if $item_id}}
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$l10n.itemidlbl}}</h3>
			</div>
			<div class="panel-body">
				{{$item_id}}
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$l10n.itemurilbl}}</h3>
			</div>
			<div class="panel-body">
				{{$item_uri}}
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$l10n.termslbl}}</h3>
			</div>
			<div class="panel-body">
				<table class="table table-condensed table-striped">
					<tr>
						<th>{{$l10n.typelbl}}</th>
						<th>{{$l10n.termlbl}}</th>
						<th>{{$l10n.urllbl}}</th>
					</tr>
		{{foreach $terms as $term}}
					<tr>
						<td>
			{{if $term.type == 1}}
							{{$l10n.taglbl}}
			{{/if}}
			{{if $term.type == 2}}
							{{$l10n.mentionlbl}}
			{{/if}}
			{{if $term.type == 8}}
							{{$l10n.implicitlbl}}
			{{/if}}
						</td>
						<td>
							{{$term.name}}
						</td>
						<td>
							{{$term.url}}
						</td>
					</tr>
		{{/foreach}}
				</table>
			</div>
		</div>
		{{if $source}}
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{{$sourcelbl}}</h3>
			</div>
			<pre><code class="language-php">{{$source}}</code></pre>
		</div>
		{{else}}
		<div class="panel panel-warning">
			<div class="panel-heading">
				<h3 class="panel-title">{{$l10n.error}}</h3>
			</div>
			<div class="panel-body">
				<p>{{$l10n.nosource}}</p>
			{{if $l10n.noconfig}}
				<p>{{$l10n.noconfig nofilter}}</p>
			{{/if}}
			</div>
		</div>
        {{/if}}
	{{else}}
		<div class="panel panel-danger">
			<div class="panel-heading">
				<h3 class="panel-title">{{$l10n.error}}</h3>
			</div>
			<div class="panel-body">
				{{$l10n.notfound}}
			</div>
		</div>
	{{/if}}
	</div>
{{/if}}
</div>
