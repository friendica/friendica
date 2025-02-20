{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<div class="wall-item-outside-wrapper{{$indent}} media" id="wall-item-outside-wrapper-{{$id}}">

	<ul class="nav nav-pills preferences">
		{{* The time of the comment *}}
		<li><span><small><time class="dt-published">{{$ago}}</time></small></span></li>

		{{* Dropdown menu with actions (e.g. delete comment) *}}
		{{if $drop.dropping }}
		<li class="dropdown">
			<a class="dropdown-toggle" data-toggle="dropdown" id="dropdownMenuTools-{{$id}}" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-angle-down" aria-hidden="true"></i></a>

			<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dropdownMenuTools-{{$id}}">
				<li role="menuitem">
					<a onclick="dropItem('item/drop/{{$id}}', '#wall-item-outside-wrapper-{{$id}}'); return false;" class="delete" title="{{$drop.delete}}"><i class="fa fa-trash" aria-hidden="true"></i>&nbsp;{{$drop.delete}}</a>
				</li>
			</ul>
		</li>
		{{/if}}
	</ul>

	{{* avatar picture *}}
	<div class="contact-photo-wrapper mframe p-author h-card pull-left">
		<a class="userinfo click-card u-url" id="wall-item-photo-menu-{{$id}}" href="{{$profile_url}}">
			<div class="contact-photo-image-wrapper">
				<img src="{{$thumb}}" class="contact-photo-xs media-object p-name u-photo" id="wall-item-photo-{{$id}}" alt="{{$name}}" />
			</div>
		</a>
	</div>

	<div class="media-body">

		{{* the header with the comment author name *}}
		<div class="contact-info-comment">
			<h5 class="media-heading">
				<a href="{{$profile_url}}" title="View {{$name}}'s profile" class="wall-item-name-link userinfo hover-card"><span class="btn-link">{{$name}}</span></a>
			</h5>
		</div>

		{{* comment content *}}
		<article class="wall-item-content" id="wall-item-content-{{$id}}">
			{{if $title}}
			<div class="wall-item-title" id="wall-item-title-{{$id}}">{{$title}}</div>
			{{/if}}

			<div class="wall-item-body {{if !$title}}p-name{{/if}}" id="wall-item-body-{{$id}}" dir="auto">{{$body}}</div>
		</article>

		<div class="wall-item-wrapper-end clear"></div>
		<div class="wall-item-comment-separator"></div>

		{{* comment text field *}}
		{{if $comment}}
		<div class="wall-item-comment-wrapper" id="item-comments-{{$item.id}}">
			{{$comment}}
		</div>
		{{/if}}
	</div>

	<div class="wall-item-outside-wrapper-end{{$indent}} clear"></div>
</div>
