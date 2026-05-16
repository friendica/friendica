{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{* Template for single photo view *}}

{{* "live-photos" is needed for js autoupdate *}}
<div id="live-photos"></div>

<div id="photo-view-{{$id}}" class="generic-page-wrapper">
	<div class="pull-left" id="photo-edit-link-wrap">
		<a class="page-action faded-icon" id="photo-album-link" href="{{$album.0}}">
			<i class="fa fa-folder-open"></i>
			{{$album.1}}
		</a>
	</div>
	<div class="pull-right" id="photo-edit-link-wrap">
{{if $tools}}
	{{if $tools.view}}
		<a id="photo-edit-link" class="btn btn-primary photo-back-link" href="{{$tools.view.0}}">
			<i class="page-action fa fa-mail-reply"></i>
			 {{$back_text}}
		</a>
	{{/if}}
	{{if $tools.edit}}
		<a id="photo-edit-link" class="btn btn-primary" href="{{$tools.edit.0}}">
			 <i class="page-action fa fa-pencil"></i>
			 {{$edit_text}}
		</a>
	{{/if}}
	{{if $tools.delete}}
		<button id="photo-delete-link" class="btn btn-primary" type="button" data-modal-url="{{$tools.delete.0}}">
			<i class="page-action fa fa-trash"></i>
			{{$delete_text}}
		</button>
	{{/if}}
	{{if $tools.profile}}
		<a id="photo-toprofile-link" class="btn btn-primary" href="{{$tools.profile.0}}">
			<i class="page-action fa fa-user"></i>
			{{$use_as_profile_picture_text}}
		</a>
	{{/if}}
	{{if $tools.lock}}
		<a id="photo-lock-link" onclick="lockview(event, 'photo', {{$id}});" title="{{$tools.lock}}">
			<i class="page-action fa fa-lg fa-lock faded-icon"></i>
		</a>
	{{/if}}
{{/if}}
	</div>
	<div class="clear"></div>

	<div id="photo-view-wrapper">
		<div id="photo-photo" class="center-block">
			{{* The photo *}}
			<div class="photo-container">
				<a href="{{$photo.href}}" title="{{$photo.title}}"><img src="{{$photo.src}}" alt="{{$photo.filename}}"/></a>
			</div>

			{{* Overlay buttons for previous and next photo *}}
			{{if $prevlink}}
			<a class="photo-prev-link" href="{{$prevlink.0}}"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
			{{/if}}
			{{if $nextlink}}
			<a class="photo-next-link" href="{{$nextlink.0}}"><i class="fa fa-angle-right" aria-hidden="true"></i></a>
			{{/if}}
		</div>

		<div id="photo-photo-end"></div>
		{{* The photo description *}}
		<div id="photo-caption">{{$desc}}</div>

		{{* The part for editing the photo - only available for the edit subpage *}}
		{{if $edit}}{{$edit nofilter}}{{/if}}

		<hr>
	</div>
</div>
