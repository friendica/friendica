<div class="wall-item-like-buttons" id="wall-item-like-buttons-$id">
	<a href="#" class="tool like" title="$likethis" onclick="dolike($id,'like'); return false"></a>
	{{ if $nolike }}
	<a href="#" class="tool dislike" title="$nolike" onclick="dolike($id,'dislike'); return false"></a>
	{{ endif }}
	<img id="like-rotator-$id" class="like-rotator" src="images/rotator.gif" alt="$wait" title="$wait" style="display: none;" />
</div>
