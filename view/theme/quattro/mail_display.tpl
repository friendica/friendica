<div id="mail-display-subject">
	<span class="{{if $thread_seen}}seen{{else}}unseen{{endif}}">$thread_subject</span>
	<a href="message/dropconv/$thread_id" onclick="return confirmDelete();"  title="$delete" class="mail-delete icon s22 delete"></a>
</div>

{{ for $mails as $mail_item }}
	<div id="tread-wrapper-$mail_item.id" class="tread-wrapper">
		{{ inc $mail_conv with $mail=$mail_item }}{{endinc}}
	</div>
{{ endfor }}

{{ inc $prv_message with $reply=$reply_info }}{{ endinc }}
