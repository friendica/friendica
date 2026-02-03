{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}" style="{{$video.style}}">
	<script src="view/js/hls/hls.min.js"></script>
	<video id="{{$video.id}}" controls {{if $video.preview}}preload="none" poster="{{$video.preview}}" {{else}}preload="metadata" {{/if}} width="{{$video.width}}" height="{{$video.height}}" title="{{$video.description}}" type="{{$video.mime}}">
		<a href="{{$video.src}}">{{$video.name}}</a>
	</video>
	<script>
		var video = document.getElementById('{{$video.id}}');
		var videoSrc = '{{$video.src}}';
		if (Hls.isSupported()) {
			var hls = new Hls();
			hls.loadSource(videoSrc);
			hls.attachMedia(video);
			hls.on(Hls.Events.ERROR, function (event, data) {
				var errorType = data.type;
				var errorDetails = data.details;
				var errorFatal = data.fatal;
				console.error('HLS.js error:', errorType, errorDetails, 'Fatal:', errorFatal);
				if (errorFatal) {
					document.getElementById('video-top-wrapper-{{$video.id}}').style.display = 'none';
				}
			});
		} else if (video.canPlayType('{{$video.mime}}')) {
			video.src = videoSrc;
		} else {
			document.getElementById('video-top-wrapper-{{$video.id}}').style.display = 'none';
		}
	</script>
</div>
