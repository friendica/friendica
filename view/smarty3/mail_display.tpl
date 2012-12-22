
{{foreach $mails as $mail_item}}
	{{include file="file:{{$mail_conv}}" mail=$mail_item}}
{{/foreach}}

{{if $canreply}}
{{include file="file:{{$prv_message}}" reply=$reply_info}}
{{else}}
{{$unknown_text}}
{{/if}}
