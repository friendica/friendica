<div class="wall-item-like-buttons" id="wall-item-like-buttons-$id">
	<a href="like/$id?verb=like&return=$return_path#$item.id" class="icon like" title="$likethis" ></a>
	{{ if $nolike }}
	<a href="like/$id?verb=dislike&return=$return_path#$item.id" class="icon dislike" title="$nolike" ></a>
	{{ endif }}
	<img id="like-rotator-$id" class="like-rotator" src="images/rotator.gif" alt="$wait" title="$wait" style="display: none;" />
</div>
