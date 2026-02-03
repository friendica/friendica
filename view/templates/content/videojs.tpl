{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<link rel="stylesheet" href="view/js/videojs/video-js.min.css"
	type="text/css" media="screen" />
<script type="text/javascript" src="view/js/videojs/video.min.js"></script>
<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}" style="{{$video.style}}">
	<video id="player-{{$video.id}}" controls {{if $video.preview}}preload="auto" poster="{{$video.preview}}" {{else}}preload="metadata" {{/if}} width="{{$video.width}}" height="{{$video.height}}" title="{{$video.description}}" class="video-js vjs-normalise-time-controls">
		<source src="{{$video.src}}" type="{{$video.mime}}">
	</video>

	<script>
		var player = videojs('player-{{$video.id}}', {
			fluid: true,
			playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 2],
			controlBar: {
				children: [
					'playToggle',
					'currentTimeDisplay',
					'progressControl',
					'durationDisplay',
					'volumePanel',
					'playbackRateMenuButton',
					'chaptersButton',
					'DescriptionButton',
					'SubsCapsButton',
					'AudioTrackButton',
					'PictureInPictureToggle',
					'fullscreenToggle'
				],
			}
		});
	</script>
</div>
