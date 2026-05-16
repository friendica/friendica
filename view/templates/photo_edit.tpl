{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<form action="photos/{{$nickname}}/image/{{$resource_id}}/edit" method="post" id="photo_edit_form">

	<input type="hidden" name="origaname" value="{{$album.2}}" />

	{{include file="field_input.tpl" field=$album}}
	{{include file="field_input.tpl" field=$caption}}

	{{include file="field_radio.tpl" field=$rotate_none}}
	{{include file="field_radio.tpl" field=$rotate_cw}}
	{{include file="field_radio.tpl" field=$rotate_ccw}}

	<div id="photo-edit-perms" class="photo-edit-perms">
		<a href="#photo-edit-perms-select" id="photo-edit-perms-menu" class="button popupbox" title="{{$permissions}}">
			<span id="jot-perms-icon" class="icon {{$lockstate}}"></span>{{$permissions}}
		</a>
		<div id="photo-edit-perms-menu-end"></div>

		<div style="display: none;">
			<div id="photo-edit-perms-select">
				{{$aclselect nofilter}}
			</div>
		</div>
	</div>
	<div id="photo-edit-perms-end"></div>

	<input id="photo-edit-submit-button" type="submit" name="submit" value="{{$submit}}" />

	<div id="photo-edit-end"></div>
</form>
