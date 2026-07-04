{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script type="text/javascript" src="view/theme/frio/js/mod_admin.js?v={{$VERSION}}"></script>
<link rel="stylesheet" href="view/theme/frio/css/mod_admin.css?v={{$VERSION}}" type="text/css" media="screen"/>

<div id="admin-serverblocklist" class="adminpage generic-page-wrapper">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<p>{{$l10n.intro}}</p>
	<p>{{$l10n.public nofilter}}</p>

	<div class="panel-group panel-group-settings" id="admin-settings" role="tablist" aria-multiselectable="true">
		<div class="panel">
			<div class="panel-heading section-subtitle-wrapper" role="tab" id="admin-settings-serverblocklist-import">
				<h4>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-serverblocklist-import-collapse" aria-expanded="false" aria-controls="admin-settings-serverblocklist-import-collapse">
						{{$l10n.importtitle}}
					</button>
				</h4>
			</div>

			<div id="admin-settings-serverblocklist-import-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-serverblocklist-import">
				<div class="panel panel-body">
					{{$l10n.download nofilter}}
				</div>

				<form action="{{$baseurl}}/moderation/blocklist/server/import" method="post" enctype="multipart/form-data">
					<input type="hidden" name="form_security_token" value="{{$form_security_token_import}}">
					{{include file="field_input.tpl" field=$listfile}}
					<div class="form-group pull-right">
						<button type="submit" class="btn btn-primary" name="page_blocklist_upload">{{$l10n.importsubmit}}</button>
					</div>
					<div class="clear"></div>
				</form>
			</div>
		</div>

		<div class="panel">
			<div class="panel-heading section-subtitle-wrapper" role="tab" id="admin-settings-serverblocklist-add">
				<h4>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-serverblocklist-add-collapse" aria-expanded="false" aria-controls="admin-settings-serverblocklist-add-collapse">
						{{$l10n.addtitle}}
					</button>
				</h4>
			</div>

			<div id="admin-settings-serverblocklist-add-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-serverblocklist-add">
				<div class="panel panel-body">
					{{$l10n.syntax nofilter}}
				</div>

				<form action="{{$baseurl}}/moderation/blocklist/server/add" method="get">
					{{include file="field_input.tpl" field=$newdomain}}
					<div class="form-group pull-right">
						<button type="submit" class="btn btn-primary">{{$l10n.addsubmit}}</button>
					</div>
					<div class="clear"></div>
				</form>
			</div>
		</div>

		<div class="panel">
			<div class="panel-heading section-subtitle-wrapper" role="tab" id="admin-settings-serverblocklist-search">
				<h4>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-serverblocklist-search-collapse" aria-expanded="false" aria-controls="admin-settings-serverblocklist-search-collapse">
						{{$l10n.search_label}}
					</button>
				</h4>
			</div>

			<div id="admin-settings-serverblocklist-search-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-serverblocklist-search">
				<form action="{{$baseurl}}/moderation/blocklist/server" method="get">
					<div class="form-group">
						<label for="serverblocklist_search">{{$l10n.search_label}}</label>
						<input id="serverblocklist_search" class="form-control" type="text" name="search" value="{{$search}}">
					</div>
					<div class="form-group pull-right">
						<button type="submit" class="btn btn-primary">{{$l10n.search_submit}}</button>
						{{if $search}}<a href="{{$baseurl}}/moderation/blocklist/server" class="btn btn-default">{{$l10n.search_reset}}</a>{{/if}}
					</div>
					<div class="clear"></div>
				</form>
			</div>
		</div>

		<div class="panel">
			<div class="panel-heading section-subtitle-wrapper" role="tab" id="admin-settings-serverblocklist-current">
				<h4>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-serverblocklist-current-collapse" aria-expanded="{{if $entries || $search}}true{{else}}false{{/if}}" aria-controls="admin-settings-serverblocklist-current-collapse">
						{{$l10n.currenttitle}}
					</button>
				</h4>
			</div>

			<div id="admin-settings-serverblocklist-current-collapse" class="panel-body panel-collapse collapse {{if $entries || $search}}in{{/if}}" role="tabpanel" aria-labelledby="admin-settings-serverblocklist-current">
				{{if $entries}}
				<form action="{{$baseurl}}/moderation/blocklist/server" method="post">
					<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
					<input type="hidden" name="search" value="{{$search}}">

					<table class="table table-condensed table-striped table-bordered">
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
								<td>
									<div class="checkbox">
										<input type="hidden" name="{{$e.delete.0}}" value="0">
										<input type="checkbox" name="{{$e.delete.0}}" id="id_{{$e.delete.0}}" value="1" {{if $e.delete.2}}checked{{/if}} {{$e.delete.4 nofilter}}>
										<label for="id_{{$e.delete.0}}"></label>
									</div>
								</td>
								<td>{{include file="field_input.tpl" field=$e.domain label=false}}</td>
								<td>{{include file="field_input.tpl" field=$e.reason label=false}}</td>
							</tr>
						{{/foreach}}
						</tbody>
					</table>

					<div class="form-group pull-right">
						<button type="submit" class="btn btn-primary" name="page_blocklist_edit" value="{{$l10n.savechanges}}">{{$l10n.savechanges}}</button>
					</div>
					<div class="clear"></div>
				</form>
				{{else}}
				<p>{{if $search}}{{$l10n.no_entries_filtered}}{{else}}{{$l10n.no_entries}}{{/if}}</p>
				{{/if}}
			</div>
		</div>
	</div>
</div>