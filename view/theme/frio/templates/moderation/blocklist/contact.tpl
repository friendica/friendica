{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script type="text/javascript" src="view/theme/frio/js/mod_admin.js?v={{$VERSION}}"></script>
<link rel="stylesheet" href="view/theme/frio/css/mod_admin.css?v={{$VERSION}}" type="text/css" media="screen"/>

<div id="admin-contactblock" class="adminpage generic-page-wrapper">
	<h1>{{$title}} - {{$page}}</h1>
	<p>{{$description nofilter}}</p>

	{{* We organize the settings in collapsable panel-groups *}}
	<div class="panel-group panel-group-settings" id="admin-settings" role="tablist" aria-multiselectable="true">
		{{* CSV Import/Export Section *}}
		<div class="panel">
			<div class="panel-heading section-subtitle-wrapper" role="tab" id="admin-settings-contactblock-import">
				<h4>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-contactblock-import-collapse" aria-expanded="false" aria-controls="admin-settings-contactblock-import-collapse">
						{{$importexport}}
					</button>
				</h4>
			</div>

			<div id="admin-settings-contactblock-import-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-contactblock-import">
				<div class="form-group">
					<a href="{{$baseurl}}/moderation/blocklist/contact/import" class="btn btn-primary">{{$import_csv}}</a>
				</div>
				<div class="form-group">
					<a href="{{$baseurl}}/moderation/blocklist/contact/export" class="btn btn-default" download>{{$export_csv}}</a>
				</div>
			</div>
		</div>

		{{* Search blocked contacts *}}
		<div class="panel">
			<div class="panel-heading section-subtitle-wrapper" role="tab" id="admin-settings-contactblock-search">
				<h4>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-contactblock-search-collapse" aria-expanded="false" aria-controls="admin-settings-contactblock-search-collapse">
						{{$search_label}}
					</button>
				</h4>
			</div>

			<div id="admin-settings-contactblock-search-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-contactblock-search">
				<form action="{{$baseurl}}/moderation/blocklist/contact" method="get">
					<div class="form-group">
						<label for="contactblock_search">{{$search_label}}</label>
						<input id="contactblock_search" class="form-control" type="text" name="search" value="{{$search}}">
					</div>
					<div class="form-group pull-right">
						<button type="submit" class="btn btn-primary">{{$search_submit}}</button>
						{{if $search}}<a href="{{$baseurl}}/moderation/blocklist/contact" class="btn btn-default">{{$search_reset}}</a>{{/if}}
					</div>
					<div class="clear"></div>
				</form>
			</div>
		</div>

		{{* The form for entering user profile which should be blocked *}}
		<div class="panel">
			<div class="panel-heading section-subtitle-wrapper" role="tab" id="admin-settings-contactblock-block">
				<h4>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-contactblock-block-collapse" aria-expanded="false" aria-controls="admin-settings-contactblock-block-collapse">
						{{$h_newblock}}
					</button>
				</h4>
			</div>

			<div id="admin-settings-contactblock-block-collapse" class="panel-body panel-collapse collapse" role="tabpanel" aria-labelledby="admin-settings-contactblock-block">
				<form action="{{$baseurl}}/moderation/blocklist/contact" method="post">
					<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
					<input type="hidden" name="search" value="{{$search}}">

					{{include file="field_input.tpl" field=$contacturl}}
					{{include file="field_checkbox.tpl" field=$contact_block_purge}}
					{{include file="field_textarea.tpl" field=$contact_block_reason}}

					<div class="admin-settings-submit-wrapper form-group pull-right">
						<button type="submit" class="btn btn-primary" name="page_contactblock_block" value="1">{{$submit}}</button>
					</div>
					<div class="clear"></div>
				</form>
			</div>
		</div>

		{{* The list of blocked user profiles with the possibility to unblock them *}}
		<div class="panel">
			<div class="panel-heading section-subtitle-wrapper" role="tab" id="admin-settings-contactblock-blocked">
				<h4>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#admin-settings" href="#admin-settings-contactblock-blocked-collapse" aria-expanded="{{if count($contacts) > 0}}true{{else}}false{{/if}}" aria-controls="admin-settings-contactblock-blocked-collapse">
						{{$h_contacts}} ({{$total_contacts}})
					</button>
				</h4>
			</div>

			<div id="admin-settings-contactblock-blocked-collapse" class="panel-body panel-collapse collapse {{if count($contacts) > 0 || $search}}in{{/if}}" role="tabpanel" aria-labelledby="admin-settings-contactblock-blocked">
				<form action="{{$baseurl}}/moderation/blocklist/contact" method="post">
					<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
					<input type="hidden" name="search" value="{{$search}}">

					{{if $contacts}}
					<table id="contactblock" class="table table-condensed table-striped">
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
									<td>
										<div class="checkbox">
											<input type="checkbox" class="contacts_ckbx" id="id_contact_{{$contact.id}}" name="contacts[]" value="{{$contact.id}}"/>
											<label for="id_contact_{{$contact.id}}"></label>
										</div>
									</td>
									<td><img class="icon" src="{{$contact.micro}}" alt="{{$contact.nickname}}" title="{{$contact.addr}}"></td>
									<td class="name">
										{{$contact.name}}<br>
										<a href="{{$contact.url}}" title="{{$contact.nickname}}">{{$contact.addr}}</a>
									</td>
									<td class="reason">{{if $contact.block_reason}}{{$contact.block_reason}}{{else}}N/A{{/if}}</td>
								</tr>
							{{/foreach}}
						</tbody>
						<tfoot>
							<tr>
								<td>
									{{* Checkbox to select all blocked contacts *}}
									<div class="checkbox">
										<input type="checkbox" id="contactblock-select" class="selecttoggle contacts_ckbx" data-select-class="contacts_ckbx" data-select-all="{{$select_all}}" data-select-none="{{$select_none}}" title="{{$select_all}}"/>
										<label for="contactblock-select"></label>
									</div>
								</td>
								<td colspan="3">
									{{$total_contacts}}
									<div class="admin-settings-submit-wrapper form-group pull-right">
										<button type="submit" class="btn btn-small btn-default pull-right" name="page_contactblock_unblock" value="1">{{$unblock}}</button>
									</div>
									<div class="clear"></div>
								</td>
							</tr>
						</tfoot>
					</table>

					{{$paginate nofilter}}

					{{else}}
					<p>{{if $search}}{{$no_data_filtered}}{{else}}{{$no_data}}{{/if}}</p>
					{{/if}}
				</form>
			</div>
		</div>
	</div>
</div>
