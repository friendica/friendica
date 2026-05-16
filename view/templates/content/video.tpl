{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="video-top-wrapper lframe" id="video-top-wrapper-{{$video.id}}" style="{{$video.style}}">
	{{* set preloading to none to lessen the load on the server *}}
	<video src="{{$video.src}}" controls {{if $video.preview}}preload="none" poster="{{$video.preview}}" {{else}}preload="metadata" {{/if}}width="{{$video.width}}" height="{{$video.height}}" title="{{$video.description}}" type="{{$video.mime}}">
		<a href="{{$video.src}}">{{$video.name}}</a>
	</video>

{{if $delete_url }}
	<form method="post" action="{{$delete_url}}">
		<input type="submit" name="delete" value="X" class="video-delete"></input>
		<input type="hidden" name="id" value="{{$video.id}}"></input>
	</form>
{{/if}}
</div>
