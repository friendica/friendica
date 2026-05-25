{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	{{include file="section_title.tpl" title=$title}}

	<p class="connector_statusmsg">{{$diasp_enabled}}</p>
	<p class="connector_statusmsg">{{$ostat_enabled}}</p>

	<div class="panel-group panel-group-settings" id="settings-connectors" role="tablist" aria-multiselectable="true">

		<form action="settings/connectors" method="post" autocomplete="off" class="panel">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

			<div class="section-subtitle-wrapper panel-heading" role="tab" id="content-settings-title">
				<h2>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings-connectors" href="#content-settings-content" aria-expanded="false" aria-controls="content-settings-content">
						{{$general_settings}}
					</button>
				</h2>
			</div>
			<div id="content-settings-content" class="panel-collapse collapse" role="tabpanel" aria-labelledby="content-settings-title">
				<div class="panel-body">
					{{include file="field_select.tpl" field=$accept_only_sharer}}

					{{include file="field_checkbox.tpl" field=$enable_cw}}

					{{include file="field_checkbox.tpl" field=$enable_smart_shortening}}

					{{include file="field_checkbox.tpl" field=$simple_shortening}}

					{{include file="field_checkbox.tpl" field=$attach_link_title}}

					{{include file="field_checkbox.tpl" field=$api_spoiler_title}}

					{{include file="field_checkbox.tpl" field=$api_auto_attach}}

					{{include file="field_select.tpl" field=$article_mode}}
				</div>
				<div class="panel-footer">
					<button type="submit" id="general-submit" name="general-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
				</div>
			</div>
		</form>

		<form action="settings/connectors" method="post" autocomplete="off" class="panel">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

			<div class="section-subtitle-wrapper panel-heading" role="tab" id="mail-settings-title">
				<h2>
					<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings-connectors" href="#mail-settings-content" aria-expanded="false" aria-controls="mail-settings-content">
						<img class="connector" src="images/mail.png" /> {{$h_mail}}
					</button>
				</h2>
			</div>
			<div id="mail-settings-content" class="panel-collapse collapse" role="tabpanel" aria-labelledby="mail-settings-title">
				<div class="panel-body">
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
				</div>
				<div class="panel-footer">
					<button type="submit" id="mail-submit" name="mail-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
			{{/if}}
				</div>
			</div>
		</form>

{{foreach $connector_settings_forms as $addon => $connector_settings_form}}
		<form action="settings/connectors/{{$addon}}" method="post" autocomplete="off" class="panel">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			{{$connector_settings_form nofilter}}
		</form>
{{/foreach}}
	</div>
</div>
