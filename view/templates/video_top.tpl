
<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}">
	{{*<!--<a href="{{$photo.link}}" class="photo-top-photo-link" id="photo-top-photo-link-{{$photo.id}}" title="{{$photo.title}}">
		<img src="{{$photo.src}}" alt="{{$photo.alt}}" title="{{$photo.title}}" class="photo-top-photo{{$photo.twist}}" id="photo-top-photo-{{$photo.id}}" />
	</a>-->*}}

	{{*<video id="video-{{$video.id}}" class="video-js vjs-default-skin"
	  controls preload="auto" width="480" height="320"
	  poster="http://video-js.zencoder.com/oceans-clip.png">*}}
	{{* v3.2.0 of VideoJS requires that there be a "data-setup" tag in the
	    <video> element for it to process the tag *}}
	{{* set preloading to false to lessen the load on the server *}}
	<video id="video-{{$video.id}}" class="video-js vjs-default-skin"
	  controls preload="false" data-setup="" width="400" height="264">
	 <source src="{{$video.src}}" type="{{$video.mime}}" />
	 {{*<source src="http://video-js.zencoder.com/oceans-clip.webm" type='video/webm' />
	 <source src="http://video-js.zencoder.com/oceans-clip.ogv" type='video/ogg' />*}}
	</video>

	{{*<div class="video-top-album-name"><a href="{{$video.album.link}}" class="video-top-album-link" title="{{$video.album.alt}}" >{{$video.album.name}}</a></div>*}}
</div>

