{{ if $item.thread_level!=1 }}<div class="children">{{ endif }}

<div class="wall-item-container item-tag $item.indent">
	<div class="wall-item-item">
		<div class="wall-item-info">
			<div class="contact-photo-wrapper">
				<a href="$item.profile_url" target="redir" title="$item.linktitle" class="contact-photo-link" id="wall-item-photo-link-$item.id">
					<img src="$item.thumb" class="contact-photo$item.sparkle" id="wall-item-photo-$item.id" alt="$item.name" />
				</a>
				<ul class="contact-menu menu-popup" id="wall-item-photo-menu-$item.id">
				$item.item_photo_menu
				</ul>
				
			</div>
			<div class="wall-item-location">$item.location</div>	
		</div>
		<div class="wall-item-content">
			$item.ago $item.body 
		</div>
	</div>
</div>

{{ if $item.thread_level!=1 }}</div>{{ endif }}

{{ if $item.flatten }}
<div class="wall-item-comment-wrapper" >$item.comment</div>
{{ endif }}
