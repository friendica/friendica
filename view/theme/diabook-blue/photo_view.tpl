<div id="live-display"></div>
<h3><a href="$album.0">$album.1</a></h3>

<div id="photo-edit-link-wrap">
{{ if $tools }}
<a id="photo-edit-link" href="$tools.edit.0">$tools.edit.1</a>
-
<a id="photo-toprofile-link" href="$tools.profile.0">$tools.profile.1</a>
{{ endif }}
{{ if $lock }} - <img src="images/lock_icon.gif" class="lockview" alt="$lock" onclick="lockview(event,'photo$id');" /> {{ endif }}
</div>

<div id="photo-photo">
	{{ if $prevlink }}<div id="photo-prev-link"><a href="$prevlink.0">$prevlink.1</a></div>{{ endif }}
	<a href="$photo.href" class="fancy-photo" title="$photo.title"><img src="$photo.src" /></a>
	{{ if $nextlink }}<div id="photo-next-link"><a href="$nextlink.0">$nextlink.1</a></div>{{ endif }}
</div>

<div id="photo-photo-end"></div>
<div id="photo-caption" >$desc</div>
{{ if $tags }}
<div id="in-this-photo-text">$tags.0</div>
<div id="in-this-photo">$tags.1</div>
{{ endif }}
{{ if $tags.2 }}<div id="tag-remove"><a href="$tags.2">$tags.3</a></div>{{ endif }}

{{ if $edit }}$edit{{ endif }}