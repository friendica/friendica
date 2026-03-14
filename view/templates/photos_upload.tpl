{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h3>{{$pagename}}</h3>

<div id="photos-usage-message">{{$usage}}</div>

<form action="profile/{{$nickname}}/photos" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form">
{{if $is_album_context}}
	<input type="hidden" name="album" value="{{$preselected_album}}" />
{{else}}
	<div id="photos-upload-new-wrapper">
		<div id="photos-upload-newalbum-div">
			<label id="photos-upload-newalbum-text" for="photos-upload-newalbum">{{$albumtext_label}}</label>
		</div>
		<input id="photos-upload-newalbum" type="text" name="newalbum" />
	</div>
	<div id="photos-upload-new-end"></div>
	<div id="photos-upload-exist-wrapper">
		<div id="photos-upload-existing-album-text">{{$albumtext_description}}</div>
		<select id="photos-upload-album-select" name="album" size="4">
		{{$albumselect nofilter}}
		</select>
	</div>
	<div id="photos-upload-exist-end"></div>
{{/if}}

	<div id="photos-upload-perms" class="photos-upload-perms">
		<a href="#photos-upload-permissions-wrapper" id="photos-upload-perms-menu" class="button popupbox" />
		<span id="jot-perms-icon" class="icon {{$lockstate}}"></span>{{$permissions}}
		</a>
	</div>
	<div id="photos-upload-perms-end"></div>

	<div style="display: none;">
		<div id="photos-upload-permissions-wrapper">
			{{$aclselect nofilter}}
		</div>
	</div>

	<div id="photos-upload-spacer"></div>

	{{$alt_uploader nofilter}}

	{{$default_upload_box nofilter}}
	{{$default_upload_submit nofilter}}

	<div class="photos-upload-end"></div>
</form>
