{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}" style="{{$video.style}}">
	<video id="{{$video.id}}" controls {{if $video.preview}}preload="none" poster="{{$video.preview}}" {{else}}preload="metadata" {{/if}} width="{{$video.width}}" height="{{$video.height}}" title="{{$video.description}}" type="{{$video.mime}}">
		<a href="{{$video.src}}">{{$video.name}}</a>
	</video>
	<script>
		(function () {
			var video = document.getElementById('{{$video.id}}');
			var videoSrc = '{{$video.src}}';

			function hide() {
				document.getElementById('video-top-wrapper-{{$video.id}}').style.display = 'none';
			}

			function play() {
				if (Hls.isSupported()) {
					var hls = new Hls();
					hls.loadSource(videoSrc);
					hls.attachMedia(video);
					hls.on(Hls.Events.ERROR, function (event, data) {
						// hls.js recovers from non-fatal errors itself (buffer holes, stalls).
						if (data.fatal) {
							console.warn('HLS.js fatal error:', data.type, data.details);
							hide();
						}
					});
				} else if (video.canPlayType('{{$video.mime}}')) {
					video.src = videoSrc;
				} else {
					hide();
				}
			}

			// The library is loaded on demand and shared between all players on the page
			if (window.Hls) {
				play();
			} else if (window.hlsLoading) {
				window.hlsLoading.push(play);
			} else {
				window.hlsLoading = [play];
				var script = document.createElement('script');
				script.src = 'view/js/hls/hls.min.js';
				script.onload = function () {
					window.hlsLoading.forEach(function (cb) { cb(); });
					window.hlsLoading = null;
				};
				script.onerror = hide;
				document.head.appendChild(script);
			}
		})();
	</script>
</div>
