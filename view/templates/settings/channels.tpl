{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$l10n.title}}</h1>
	<p>{{$l10n.intro}}</p>
	<details class="settings-section">
		<summary class="settings-heading"><h2>{{$l10n.addtitle}}</h2></summary>
		<form action="{{$baseurl}}/settings/channels" method="post">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			{{include file="field_input.tpl" field=$label}}
			{{include file="field_input.tpl" field=$description}}
			{{include file="field_input.tpl" field=$access_key}}
			{{include file="field_select.tpl" field=$circle}}
			{{include file="field_textarea.tpl" field=$include_tags}}
			{{include file="field_textarea.tpl" field=$exclude_tags}}
			{{include file="field_textarea.tpl" field=$text_search}}
			{{include file="field_input.tpl" field=$min_size}}
			{{include file="field_input.tpl" field=$max_size}}
			{{include file="field_checkbox.tpl" field=$image}}
			{{include file="field_checkbox.tpl" field=$video}}
			{{include file="field_checkbox.tpl" field=$audio}}
			{{include file="field_select.tpl" field=$languages}}
			<div class="settings-submit-wrapper">
				<button type="submit" class="settings-submit btn btn-primary" name="add_channel" value="{{$l10n.addsubmit}}">{{$l10n.addsubmit}}</button>
			</div>
		</form>
	</details>

	{{if $entries}}
	<details class="settings-section">
		<summary class="settings-heading"><h2>{{$l10n.currenttitle}}</h2></summary>
		<form action="{{$baseurl}}/settings/channels" method="post">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			{{foreach $entries as $e}}
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
				<hr>
			{{/foreach}}
			<div class="settings-submit-wrapper">
				<button type="submit" class="settings-submit btn btn-primary" name="edit_channel" value="{{$l10n.savechanges}}">{{$l10n.savechanges}}</button>
			</div>
			{{/if}}
		</form>
	</details>
</div>
