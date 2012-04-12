<h1>$title</h1>

<div class="connector_statusmsg">$diasp_enabled</div>
<div class="connector_statusmsg">$ostat_enabled</div>

<form action="settings/connectors" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='$form_security_token'>

$settings_connectors

{{ if $mail_disabled }}

{{ else }}
	<div class="settings-block">
	<h3 class="settings-heading">$h_imap</h3>
	<p>$imap_desc</p>
	{{inc field_custom.tpl with $field=$imap_lastcheck }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_server }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_port }}{{endinc}}
	{{inc field_select.tpl with $field=$mail_ssl }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_user }}{{endinc}}
	{{inc field_password.tpl with $field=$mail_pass }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_replyto }}{{endinc}}
	{{inc field_checkbox.tpl with $field=$mail_pubmail }}{{endinc}}
	{{inc field_select.tpl with $field=$mail_action }}{{endinc}}
	{{inc field_input.tpl with $field=$mail_movetofolder }}{{endinc}}

	<div class="settings-submit-wrapper" >
		<input type="submit" id="imap-submit" name="imap-submit" class="settings-submit" value="$submit" />
	</div>
	</div>
{{ endif }}

</form>

