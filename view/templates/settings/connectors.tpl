
<h1>{{$title}}</h1>

<div class="connector_statusmsg">{{$diasp_enabled}}</div>
<div class="connector_statusmsg">{{$ostat_enabled}}</div>

<form action="settings/connectors" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

<span id="settings_general_inflated" class="settings-block fakelink" style="display: block;" onclick="openClose('settings_general_expanded'); openClose('settings_general_inflated');">
	<h3 class="connector">{{$general_settings}}</h3>
</span>
<div id="settings_general_expanded" class="settings-block" style="display: none;">
	<span class="fakelink" onclick="openClose('settings_general_expanded'); openClose('settings_general_inflated');">
		<h3 class="connector">{{$general_settings}}</h3>
	</span>

	{{include file="field_checkbox.tpl" field=$disable_cw}}
	{{include file="field_checkbox.tpl" field=$no_intelligent_shortening}}
	{{include file="field_checkbox.tpl" field=$ostatus_autofriend}}
	{{$default_group}}
	{{include file="field_input.tpl" field=$legacy_contact}}

	<p><a href="{{$repair_ostatus_url}}">{{$repair_ostatus_text}}</a></p>

	<div class="settings-submit-wrapper" ><input type="submit" id="general-submit" name="general-submit" class="settings-submit" value="{{$submit}}" /></div>
</div>
<div class="clear"></div>

{{$settings_connectors}}

{{if $mail_disabled}}

{{else}}
	<span id="settings_mail_inflated" class="settings-block fakelink" style="display: block;" onclick="openClose('settings_mail_expanded'); openClose('settings_mail_inflated');">
		<img class="connector" src="images/mail.png" /><h3 class="settings-heading connector">{{$h_imap}}</h3>
	</span>
	<div id="settings_mail_expanded" class="settings-block" style="display: none;">
	<span class="fakelink" onclick="openClose('settings_mail_expanded'); openClose('settings_mail_inflated');">
		<img class="connector" src="images/mail.png" /><h3 class="settings-heading connector">{{$h_imap}}</h3>
	</span>
	<p>{{$imap_desc}}</p>
	{{include file="field_custom.tpl" field=$imap_lastcheck}}
	{{include file="field_input.tpl" field=$mail_server}}
	{{include file="field_input.tpl" field=$mail_port}}
	{{include file="field_select.tpl" field=$mail_ssl}}
	{{include file="field_input.tpl" field=$mail_user}}
	{{include file="field_password.tpl" field=$mail_pass}}
	{{include file="field_input.tpl" field=$mail_replyto}}
	{{include file="field_checkbox.tpl" field=$mail_pubmail}}
	{{include file="field_select.tpl" field=$mail_action}}
	{{include file="field_input.tpl" field=$mail_movetofolder}}

	<div class="settings-submit-wrapper" >
		<input type="submit" id="imap-submit" name="imap-submit" class="settings-submit" value="{{$submit|escape:'html'}}" />
	</div>
	</div>
{{/if}}

</form>

