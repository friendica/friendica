{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="photo-top-image-wrapper" id="photo-top-image-wrapper-{{$photo.id}}">
	<a href="{{$photo.link}}" id="photo-top-photo-link-{{$photo.id}}" title="{{$photo.title}}">
		<img src="{{$photo.src}}" alt="{{if $photo.album.name}}{{$photo.album.name}}{{elseif $photo.desc}}{{$photo.desc}}{{elseif $photo.alt}}{{$photo.alt}}{{else}}{{$photo.unknown}}{{/if}}" title="{{$photo.title}}" id="photo-top-photo-{{$photo.id}}" />
	</a>
	<div class="jg-caption"><a href="{{$photo.album.link}}" class="photo-top-album-link" title="{{$photo.album.alt}}">{{$photo.album.name}}</a></div>
</div>
