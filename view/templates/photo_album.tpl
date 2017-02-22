<h3 id="photo-album-title">{{$album}}</h3>

{{if $edit}}
<div id="album-edit-link"><a href="{{$edit.1}}" title="{{$edit.0}}">{{$edit.0}}</a></div>
{{/if}}
<div class="photos-upload-link" ><a href="{{$order.1}}" title="{{$order.0}}">{{$order.0}}</a></div>
{{if $can_post}}
<div class="photos-upload-link" ><a href="{{$upload.1}}">{{$upload.0}}</a></div>
{{/if}}

{{foreach $photos as $photo}}
<div class="photo-album-image-wrapper" id="photo-album-image-wrapper-{{$photo.id}}">
	<a href="{{$photo.link}}" class="photo-album-photo-link" id="photo-album-photo-link-{{$photo.id}}" title="{{$photo.title}}">
		<img src="{{$photo.src}}" alt="{{if $photo.album.name}}{{$photo.album.name}}{{elseif $photo.desc}}{{$photo.desc}}{{elseif $photo.alt}}{{$photo.alt}}{{else}}{{$photo.unknown}}{{/if}}" title="{{$photo.title}}" class="photo-album-photo lframe resize{{$photo.twist}}" id="photo-album-photo-{{$photo.id}}" />
		<p class='caption'>{{$photo.desc}}</p>		
	</a>
</div>
<div class="photo-album-image-wrapper-end"></div>
{{/foreach}}

{{$paginate}}
