{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}">
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
		} else if (video.canPlayType('application/vnd.apple.mpegurl')) {
			video.src = videoSrc;
		}
	</script>
</div>