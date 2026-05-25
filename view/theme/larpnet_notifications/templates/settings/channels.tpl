{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$l10n.title}}</h1>
	<p>{{$l10n.intro}}</p>
	<div class="panel-group panel-group-settings" id="settings-channels" role="tablist" aria-multiselectable="true">
		<form class="panel" action="{{$baseurl}}/settings/channels" method="post">
			<div class="section-subtitle-wrapper panel-heading" role="tab" id="add-settings-title">
				<h2>
					<button class="btn-link accordion-toggle{{if !$open}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings-channels" href="#add-settings-content" aria-expanded="false" aria-controls="add-settings-content">
						{{$l10n.addtitle}}
					</button>
				</h2>
			</div>
			<div id="add-settings-content" class="panel-collapse collapse{{if $open}} in{{/if}}" role="tabpanel" aria-labelledby="add-settings-title">
				<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
				{{include file="field_input.tpl" field=$label}}
				{{include file="field_input.tpl" field=$description}}
				{{include file="field_input.tpl" field=$access_key}}
				{{include file="field_select.tpl" field=$circle}}
				{{include file="field_textarea.tpl" field=$include_tags}}
				{{include file="field_textarea.tpl" field=$exclude_tags}}
				{{include file="field_input.tpl" field=$min_size}}
				{{include file="field_input.tpl" field=$max_size}}
				{{include file="field_textarea.tpl" field=$text_search}}
				{{include file="field_checkbox.tpl" field=$image}}
				{{include file="field_checkbox.tpl" field=$video}}
				{{include file="field_checkbox.tpl" field=$audio}}
				{{include file="field_select.tpl" field=$languages}}
				<div class="submit">
					<button type="submit" class="btn btn-primary" name="add_channel" value="{{$l10n.addsubmit}}">{{$l10n.addsubmit}}</button>
				</div>
			</div>
		</form>

		{{if $entries}}
			{{foreach $entries as $e}}
				<form class="panel" action="{{$baseurl}}/settings/channels" method="post">
					<div class="section-subtitle-wrapper panel-heading" role="tab" id="{{$e.id}}-settings-title">
						<h2>
							<button class="btn-link accordion-toggle{{if !$e.open}} collapsed{{/if}}" data-toggle="collapse" data-parent="#settings-channels" href="#{{$e.id}}-settings-content" aria-expanded="false" aria-controls="{{$e.id}}-settings-content">
								{{$e.label.2}}
							</button>
						</h2>
					</div>
					<div id="{{$e.id}}-settings-content" class="panel-collapse collapse{{if $e.open}} in{{/if}}" role="tabpanel" aria-labelledby="{{$e.id}}-settings-title">
						<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
						<input type="hidden" name="id" value="{{$e.id}}">
						{{include file="field_input.tpl" field=$e.label}}
						{{include file="field_input.tpl" field=$e.description}}
						{{include file="field_input.tpl" field=$e.access_key}}
						{{include file="field_select.tpl" field=$e.circle}}
						{{include file="field_textarea.tpl" field=$e.include_tags}}
						{{include file="field_textarea.tpl" field=$e.exclude_tags}}
						{{include file="field_input.tpl" field=$e.min_size}}
						{{include file="field_input.tpl" field=$e.max_size}}
						{{include file="field_textarea.tpl" field=$e.text_search}}
						{{include file="field_checkbox.tpl" field=$e.image}}
						{{include file="field_checkbox.tpl" field=$e.video}}
						{{include file="field_checkbox.tpl" field=$e.audio}}
						{{include file="field_select.tpl" field=$e.languages}}
						{{if $e.publish}}
							{{include file="field_checkbox.tpl" field=$e.publish}}
						{{/if}}
						{{include file="field_checkbox.tpl" field=$e.delete}}
						<div class="submit">
							<button type="submit" class="btn btn-primary" name="edit_channel" value="{{$l10n.savechanges}}">{{$l10n.savechanges}}</button>
						</div>
					</div>
				</form>
			{{/foreach}}
		{{/if}}
	</div>
</div>
