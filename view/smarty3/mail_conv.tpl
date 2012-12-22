<div class="mail-conv-outside-wrapper">
	<div class="mail-conv-sender" >
		<a href="{{$mail.from_url}}" class="mail-conv-sender-url" ><img class="mframe mail-conv-sender-photo{{$mail.sparkle}}" src="{{$mail.from_photo}}" heigth="80" width="80" alt="{{$mail.from_name}}" /></a>
	</div>
	<div class="mail-conv-detail" >
		<div class="mail-conv-sender-name" >{{$mail.from_name}}</div>
		<div class="mail-conv-date">{{$mail.date}}</div>
		<div class="mail-conv-subject">{{$mail.subject}}</div>
		<div class="mail-conv-body">{{$mail.body}}</div>
	<div class="mail-conv-delete-wrapper" id="mail-conv-delete-wrapper-{{$mail.id}}" ><a href="message/drop/{{$mail.id}}" class="icon drophide delete-icon mail-list-delete-icon" onclick="return confirmDelete();" title="{{$mail.delete}}" id="mail-conv-delete-icon-{{$mail.id}}" class="mail-conv-delete-icon" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a></div><div class="mail-conv-delete-end"></div>
	<div class="mail-conv-outside-wrapper-end"></div>
</div>
</div>
<hr class="mail-conv-break" />
