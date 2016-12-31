

<form action="photos/{{$nickname}}/{{$resource_id}}" method="post" id="photo_edit_form" >

	<input type="hidden" name="item_id" value="{{$item_id}}" />
	<input type="hidden" name="origaname" value="{{$album.2}}" />

	{{include file="field_input.tpl" field=$album}}
	{{include file="field_input.tpl" field=$caption}}
	{{include file="field_input.tpl" field=$tags}}

	{{include file="field_radio.tpl" field=$rotate_none}}
	{{include file="field_radio.tpl" field=$rotate_cw}}
	{{include file="field_radio.tpl" field=$rotate_ccw}}

	<div id="photo-edit-perms" class="photo-edit-perms" >
		<a href="#photo-edit-perms-select" id="photo-edit-perms-menu" class="button popupbox" title="{{$permissions}}"/>
			<span id="jot-perms-icon" class="icon {{$lockstate}}" ></span>{{$permissions}}
		</a>
		<div id="photo-edit-perms-menu-end"></div>

		<div style="display: none;">
			<div id="photo-edit-perms-select" >
				{{$aclselect}}
			</div>
		</div>
	</div>
	<div id="photo-edit-perms-end"></div>

	<input id="photo-edit-submit-button" type="submit" name="submit" value="{{$submit|escape:'html'}}" />
	<input id="photo-edit-delete-button" type="submit" name="delete" value="{{$delete|escape:'html'}}" onclick="return confirmDelete()"; />

	<div id="photo-edit-end"></div>
</form>


