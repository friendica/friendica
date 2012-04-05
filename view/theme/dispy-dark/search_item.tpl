<div class="wall-item-outside-wrapper$item.indent$item.previewing" id="wall-item-outside-wrapper-$item.id" >
	<div class="wall-item-content-wrapper$item.indent" id="wall-item-content-wrapper-$item.id" >
		<div class="wall-item-info" id="wall-item-info-$item.id">
			<div class="wall-item-photo-wrapper" id="wall-item-photo-wrapper-$item.id" 
				 onmouseover="if (typeof t$item.id != 'undefined') clearTimeout(t$item.id); openMenu('wall-item-photo-menu-button-$item.id')" 
				 onmouseout="t$item.id=setTimeout('closeMenu(\'wall-item-photo-menu-button-$item.id\'); closeMenu(\'wall-item-photo-menu-$item.id\');',200)">
				<a href="$item.profile_url" target="redir" title="$item.linktitle" class="wall-item-photo-link" id="wall-item-photo-link-$item.id">
				<img src="$item.thumb" class="wall-item-photo$item.sparkle" id="wall-item-photo-$item.id" style="height: 80px; width: 80px;" alt="$item.name" /></a>
				<span onclick="openClose('wall-item-photo-menu-$item.id');" class="fakelink wall-item-photo-menu-button" id="wall-item-photo-menu-button-$item.id">menu</span>
				<div class="wall-item-photo-menu" id="wall-item-photo-menu-$item.id">
					<ul>
						$item.item_photo_menu
					</ul>
				</div>
			</div>
			<div class="wall-item-photo-end"></div>	
			<div class="wall-item-wrapper" id="wall-item-wrapper-$item.id" >
				{{ if $item.lock }}<div class="wall-item-lock"><img src="images/lock_icon.gif" class="lockview" alt="$item.lock" onclick="lockview(event,$item.id);" /></div>
				{{ else }}<div class="wall-item-lock"></div>{{ endif }}	
				<div class="wall-item-location" id="wall-item-location-$item.id">$item.location</div>
			</div>
		</div>
		<div class="wall-item-author">
				<a href="$item.profile_url" target="redir" title="$item.linktitle" class="wall-item-name-link"><span class="wall-item-name$item.sparkle" id="wall-item-name-$item.id" >$item.name</span></a>
				<div class="wall-item-ago"  id="wall-item-ago-$item.id">$item.ago</div>
				
		</div>			
		<div class="wall-item-content" id="wall-item-content-$item.id" >
			<div class="wall-item-title" id="wall-item-title-$item.id">$item.title</div>
			<div class="wall-item-title-end"></div>
			<div class="wall-item-body" id="wall-item-body-$item.id" >$item.body</div>
		</div>
		<div class="wall-item-tools" id="wall-item-tools-$item.id">
			<div class="wall-item-delete-wrapper" id="wall-item-delete-wrapper-$item.id" >
				{{ if $item.drop.dropping }}<a href="item/drop/$item.id" onclick="return confirmDelete();" class="icon drophide" title="$item.drop.delete" onmouseover="imgbright(this);" onmouseout="imgdull(this);" ></a>{{ endif }}
			</div>
				{{ if $item.drop.dropping }}<input type="checkbox" onclick="checkboxhighlight(this);" title="$item.drop.select" class="item-select" name="itemselected[]" value="$item.id" />{{ endif }}
			<div class="wall-item-delete-end"></div>
		</div>
	</div>
	<div class="wall-item-wrapper-end"></div>


	<div class="wall-item-conv" id="wall-item-conv-$item.id" >
	{{ if $item.conv }}
			<a href='$item.conv.href' id='context-$item.id' title='$item.conv.title'>$item.conv.title</a>
	{{ endif }}
	</div>

<div class="wall-item-outside-wrapper-end$item.indent" ></div>

</div>


