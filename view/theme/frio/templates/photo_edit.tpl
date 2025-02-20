{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<form action="photos/{{$nickname}}/image/{{$resource_id}}/edit" method="post" id="photo_edit_form">

	<input type="hidden" name="item_id" value="{{$item_id}}" />
	<input type="hidden" name="origaname" value="{{$album.2}}" />

	{{include file="field_input.tpl" field=$album}}
	{{include file="field_input.tpl" field=$caption}}
	{{include file="field_input.tpl" field=$tags}}

	{{include file="field_radio.tpl" field=$rotate_none}}
	{{include file="field_radio.tpl" field=$rotate_cw}}
	{{include file="field_radio.tpl" field=$rotate_ccw}}

	<div id="photo-edit-perms">
		<button class="btn btn-default btn-sm" data-toggle="modal" data-target="#photo-edit-permission-acl" onclick="return false;">
			<i id="jot-perms-icon" class="fa {{$lockstate}}"></i> {{$permissions}}
		</button>
	</div>

	<input id="photo-edit-submit-button" type="submit" name="submit" value="{{$submit}}" />

	{{* The modal for advanced-expire (photo permissions) *}}
	<div id="photo-edit-permission-acl" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button id="modal-close" type="button" class="close" data-dismiss="modal" aria-hidden="true">
						&times;
					</button>
					<h4 class="modal-title">{{$permissions}}</h4>
				</div>
				<div id="photos-edit-permissions-wrapper" class="modal-body">
					{{$aclselect nofilter}}
				</div>
			</div>
		</div>
	</div>
</form>
