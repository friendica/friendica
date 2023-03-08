<h1>{{$title}}</h1>

<div class="connector_statusmsg">{{$diasp_enabled}}</div>
<div class="connector_statusmsg">{{$ostat_enabled}}</div>

<form action="settings/connectors" method="post" autocomplete="off">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
	<h2 class="settings-heading">
		<a onclick="openClose('settings_general_expanded'); openClose('settings_general_inflated');">{{$general_settings}}</a>
	</h2>

	<div id="settings_general_expanded" class="settings-block" style="display: none;">

		{{include file="field_select.tpl" field=$accept_only_sharer}}
		{{include file="field_checkbox.tpl" field=$enable_cw}}
		{{include file="field_checkbox.tpl" field=$enable_smart_shortening}}
		{{include file="field_checkbox.tpl" field=$simple_shortening}}
		{{include file="field_checkbox.tpl" field=$attach_link_title}}
		{{include file="field_checkbox.tpl" field=$api_spoiler_title}}
		{{include file="field_input.tpl" field=$legacy_contact}}

		<p><a href="{{$repair_ostatus_url}}">{{$repair_ostatus_text}}</a></p>

		<div class="settings-submit-wrapper">
			<input type="submit" id="general-submit" name="general-submit" class="settings-submit" value="{{$submit}}"/>
		</div>
	</div>
</form>
<div class="clear"></div>

<form action="settings/connectors" method="post" autocomplete="off">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
	<span id="settings_mail_inflated" class="settings-block fakelink" style="display: block;"
	      onclick="openClose('settings_mail_expanded'); openClose('settings_mail_inflated');">
		<img class="connector" src="images/mail.png"/><h3 class="settings-heading connector">{{$h_mail}}</h3>
	</span>
	<div id="settings_mail_expanded" class="settings-block" style="display: none;">
		<span class="fakelink" onclick="openClose('settings_mail_expanded'); openClose('settings_mail_inflated');">
			<img class="connector" src="images/mail.png"/><h3 class="settings-heading connector">{{$h_mail}}</h3>
		</span>
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
			<input type="submit" id="mail-submit" name="mail-submit" class="settings-submit" value="{{$submit}}"/>
		</div>
	{{/if}}
	</div>
</form>

{{foreach $connector_settings_forms as $addon => $connector_settings_form}}
<form action="settings/connectors/{{$addon}}" method="post" autocomplete="off">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
    {{$connector_settings_form nofilter}}
	<div class="clear"></div>
</form>
{{/foreach}}
