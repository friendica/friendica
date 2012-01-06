<h3>$title</h3>
{{ if $can_post }}
<a id="photo-top-upload-link" href="$upload.1">$upload.0</a>
{{ endif }}

<div class="photos">
{{ for $photos as $photo }}
	{{ inc photo_top.tpl }}{{ endinc }}
{{ endfor }}
</div>
