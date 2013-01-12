
{{ for $mails as $mail }}
	{{ inc mail_conv.tpl }}{{endinc}}
{{ endfor }}

{{ if $canreply }}
{{ inc prv_message.tpl }}{{ endinc }}
{{ else }}
$unknown_text
{{endif }}
