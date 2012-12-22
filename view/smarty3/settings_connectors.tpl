<h1>{{$title}}</h1>

<div class="connector_statusmsg">{{$diasp_enabled}}</div>
<div class="connector_statusmsg">{{$ostat_enabled}}</div>

<form action="settings/connectors" method="post" autocomplete="off">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{$settings_connectors}}

{{if $mail_disabled}}

{{else}}
	<div class="settings-block">
	<h3 class="settings-heading">{{$h_imap}}</h3>
	<p>{{$imap_desc}}</p>
	{{include file="file:{{$field_custom}}" field=$imap_lastcheck}}
	{{include file="file:{{$field_input}}" field=$mail_server}}
	{{include file="file:{{$field_input}}" field=$mail_port}}
	{{include file="file:{{$field_select}}" field=$mail_ssl}}
	{{include file="file:{{$field_input}}" field=$mail_user}}
	{{include file="file:{{$field_password}}" field=$mail_pass}}
	{{include file="file:{{$field_input}}" field=$mail_replyto}}
	{{include file="file:{{$field_checkbox}}" field=$mail_pubmail}}
	{{include file="file:{{$field_select}}" field=$mail_action}}
	{{include file="file:{{$field_input}}" field=$mail_movetofolder}}

	<div class="settings-submit-wrapper" >
		<input type="submit" id="imap-submit" name="imap-submit" class="settings-submit" value="{{$submit}}" />
	</div>
	</div>
{{/if}}

</form>

