<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}">
	{{* v4.0.0 of VideoJS requires that there be a "data-setup" tag in the
	    <video> element for it to process the tag *}}
	{{* set preloading to none to lessen the load on the server *}}
	<video id="video-{{$video.id}}" class="video-js vjs-default-skin"
		controls preload="none" data-setup='{}' width="100%">
		<source src="{{$video.src}}" type="{{$video.mime}}" />
	</video>

	{{if $delete_url }}
	<form method="post" action="{{$delete_url}}">
		<input type="submit" name="delete" value="X" class="video-delete"></input>
		<input type="hidden" name="id" value="{{$video.id}}"></input>
	</form>
	{{/if}}
</div>
