
{{foreach $mails as $mail_item}}
	{{include file="mail_conv.tpl" mail=$mail_item}}
{{/foreach}}

{{if $canreply}}
{{include file="prv_message.tpl"}}
{{else}}
{{$unknown_text}}
{{/if}}
