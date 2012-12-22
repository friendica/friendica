<h3>$title</h3>
{{ if $can_post }}
<a id="photo-top-upload-link" href="$upload.1">$upload.0</a>
{{ endif }}

<div class="photos">
{{ for $photos as $ph }}
	{{ inc $photo_top with $photo=$ph }}{{ endinc }}
{{ endfor }}
</div>
<div class="photos-end"></div>
