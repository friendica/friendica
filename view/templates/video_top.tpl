
<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}">
	{{* v4.0.0 of VideoJS requires that there be a "data-setup" tag in the
	    <video> element for it to process the tag *}}
	{{* set preloading to none to lessen the load on the server *}}
	<video id="video-{{$video.id}}" class="video-js vjs-default-skin"
	  controls preload="none" data-setup="" width="400" height="264">
	 <source src="{{$video.src}}" type="{{$video.mime}}" />
	</video>

	{{*<div class="video-top-album-name"><a href="{{$video.album.link}}" class="video-top-album-link" title="{{$video.album.alt}}" >{{$video.album.name}}</a></div>*}}
</div>

