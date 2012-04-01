<div class="wall-item-container $item.indent">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="contact-photo-wrapper"
				<a href="$mail.profile_url" target="redir" title="$mail.from_name" class="contact-photo-link" id="wall-item-photo-link-$mail.id">
					<img src="$mail.from_photo" class="contact-photo$mail.sparkle" id="wall-item-photo-$mail.id" alt="$mail.from_name" />
				</a>
			</div>
		</div>
		<div class="wall-item-content">
			$mail.body
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="wall-item-links">
		</div>
		<div class="wall-item-tags">
		</div>
	</div>
	<div class="wall-item-bottom">
		<div class="">
		</div>
		<div class="wall-item-actions">
			<div class="wall-item-actions-author">
				<a href="$mail.from_url" target="redir" class="wall-item-name-link"><span class="wall-item-name$mail.sparkle">$mail.from_name</span></a> <span class="wall-item-ago">$mail.date</span>
			</div>
			
			<div class="wall-item-actions-social">
			</div>
			
			<div class="wall-item-actions-tools">
				<a href="message/drop/$mail.id" onclick="return confirmDelete();" class="icon delete s16" title="$mail.delete">$mail.delete</a>
			</div>
			
		</div>
	</div>
	<div class="wall-item-bottom">
	</div>
</div>


{#


<div class="mail-conv-outside-wrapper">
	<div class="mail-conv-sender" >
		<a href="$mail.from_url" class="mail-conv-sender-url" ><img class="mframe mail-conv-sender-photo$mail.sparkle" src="$mail.from_photo" heigth="80" width="80" alt="$mail.from_name" /></a>
	</div>
	<div class="mail-conv-detail" >
		<div class="mail-conv-sender-name" >$mail.from_name</div>
		<div class="mail-conv-date">$mail.date</div>
		<div class="mail-conv-subject">$mail.subject</div>
		<div class="mail-conv-body">$mail.body</div>
	<div class="mail-conv-delete-wrapper" id="mail-conv-delete-wrapper-$mail.id" ><a href="message/drop/$mail.id" class="icon drophide delete-icon mail-list-delete-icon" onclick="return confirmDelete();" title="$mail.delete" id="mail-conv-delete-icon-$mail.id" class="mail-conv-delete-icon" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a></div><div class="mail-conv-delete-end"></div>
	<div class="mail-conv-outside-wrapper-end"></div>
</div>
</div>
<hr class="mail-conv-break" />

#}
