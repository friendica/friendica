
{{ for $mails as $mail }}
	{{ inc mail_conv.tpl }}{{endinc}}
{{ endfor }}

{{ inc prv_message.tpl }}{{ endinc }}
