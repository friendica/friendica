<div id="mail-display-subject">
	<span class="{{if $thread_seen}}seen{{else}}unseen{{/if}}">{{$thread_subject}}</span>
	<a href="message/dropconv/{{$thread_id}}" onclick="return confirmDelete();"  title="{{$delete}}" class="mail-delete icon s22 delete"></a>
</div>

{{foreach $mails as $mail_item}}
	<div id="tread-wrapper-{{$mail_item.id}}" class="tread-wrapper">
		{{include file="file:{{$mail_conv}}" mail=$mail_item}}
	</div>
{{/foreach}}

{{include file="file:{{$prv_message}}" reply=$reply_info}}
