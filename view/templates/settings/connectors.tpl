{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<h1>{{$title}}</h1>

<div class="connector_statusmsg">{{$diasp_enabled}}</div>
<div class="connector_statusmsg">{{$ostat_enabled}}</div>

<form action="settings/connectors" method="post" autocomplete="off">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
	<details class="settings-section">
		<summary class="settings-heading"><h2>{{$general_settings}}</h2></summary>

		{{include file="field_select.tpl" field=$accept_only_sharer}}
		{{include file="field_checkbox.tpl" field=$enable_cw}}
		{{include file="field_checkbox.tpl" field=$enable_smart_shortening}}
		{{include file="field_checkbox.tpl" field=$simple_shortening}}
		{{include file="field_checkbox.tpl" field=$attach_link_title}}
		{{include file="field_checkbox.tpl" field=$api_spoiler_title}}
		{{include file="field_checkbox.tpl" field=$api_auto_attach}}
		{{include file="field_select.tpl" field=$article_mode}}
		{{include file="field_input.tpl" field=$minimum_posting_interval}}

		<div class="settings-submit-wrapper">
			<input type="submit" id="general-submit" name="general-submit" class="settings-submit btn btn-default" value="{{$submit}}"/>
		</div>
	</details>
</form>
<div class="clear"></div>

<form action="settings/connectors" method="post" autocomplete="off">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
	<details class="settings-section">
		<summary class="settings-heading"><h2><i class="ri ri-mail-line"></i> {{$h_mail}}</h2></summary>

    	{{if $mail_disabled}}
			<p>{{$mail_disabled}}</p>
		{{else}}
			<p>{{$mail_desc nofilter}}</p>
			{{include file="field_custom.tpl" field=$mail_lastcheck}}
			{{include file="field_input.tpl" field=$mail_server}}
			{{include file="field_input.tpl" field=$mail_port}}
			{{include file="field_select.tpl" field=$mail_ssl}}
			{{include file="field_input.tpl" field=$mail_user}}
			{{include file="field_password.tpl" field=$mail_pass}}
			{{include file="field_input.tpl" field=$mail_replyto}}
			{{include file="field_checkbox.tpl" field=$mail_pubmail}}
			{{include file="field_select.tpl" field=$mail_action}}
			{{include file="field_input.tpl" field=$mail_movetofolder}}

			<div class="settings-submit-wrapper">
				<input type="submit" id="mail-submit" name="mail-submit" class="settings-submit btn btn-default" value="{{$submit}}"/>
			</div>
		{{/if}}
	</details>
</form>

{{foreach $connector_settings_forms as $addon => $connector_settings_form}}
<form action="settings/connectors/{{$addon}}" method="post" autocomplete="off">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
	<details class="settings-section">
    	{{$connector_settings_form nofilter}}
	</details>
	<div class="clear"></div>
</form>
{{/foreach}}
