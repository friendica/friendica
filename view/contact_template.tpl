
<div class="contact-wrapper" id="contact-entry-wrapper-$id" >
	<div class="contact-photo-wrapper" >
		<div class="contact-photo mframe" id="contact-entry-photo-$id"
		onmouseover="if (typeof t$id != 'undefined') clearTimeout(t$id); openMenu('contact-photo-menu-button-$id')" onmouseout="t$id=setTimeout('closeMenu(\'contact-photo-menu-button-$id\'); closeMenu(\'contact-photo-menu-$id\');',200)" >

			<a href="$url" title="$img_hover" /><img src="$thumb" $sparkle alt="$name" /></a>

			<a href="#" rel="#contact-photo-menu-$id" class="contact-photo-menu-button icon s16 menu" id="contact-photo-menu-button-$id">menu</a>
			<ul class="contact-photo-menu menu-popup" id="contact-photo-menu-$id">
				$contact_photo_menu
			</ul>

		</div>
			
	</div>
	<div class="contact-name" id="contact-entry-name-$id" >$name</div>


</div>

