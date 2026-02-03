{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="photo-top-image-wrapper" id="photo-top-image-wrapper-{{$photo.id}}">
	<a href="{{$photo.link}}" id="photo-top-photo-link-{{$photo.id}}" title="{{$photo.title}}{{if $photo.desc}}: {{$photo.desc}}{{/if}}">
		<img src="{{$photo.src}}" alt="{{if $photo.desc}}{{$photo.desc}}{{elseif $photo.alt}}{{$photo.alt}}{{else}}{{$photo.unknown}}{{/if}}" id="photo-top-photo-{{$photo.id}}" {{if $photo.desc}}class="has-alt-description"{{else}}class="empty-description"{{/if}} />
	</a>
	{{if $photo.album.name}}
	<div class="jg-caption"><a href="{{$photo.album.link}}" class="photo-top-album-link" title="{{$photo.album.alt}}">{{$photo.album.name}}</a></div>
	{{/if}}
</div>
