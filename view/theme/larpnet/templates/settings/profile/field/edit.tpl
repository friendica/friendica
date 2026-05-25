{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<fieldset data-id="{{$profile_field.id}}">
	<legend>&#8801; {{$profile_field.legend}}</legend>

	<input type="hidden" name="profile_field_order[]" value="{{$profile_field.id}}">

	{{include file="field_input.tpl" field=$profile_field.fields.label}}

	{{include file="field_textarea.tpl" field=$profile_field.fields.value}}

	{{* Block for setting default permissions *}}
	<p>
		<a id="settings-default-perms-menu" class="settings-default-perms" data-toggle="modal" data-target="#profile-field-acl-{{$profile_field.id}}">{{$profile_field.permissions}} {{$profile_field.permdesc}}</a>
	</p>

	{{* We include the aclModal directly into the template since we cant use frio's default modal *}}
	<div id="profile-field-acl-{{$profile_field.id}}" class="modal fade">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">{{$profile_field.permissions}}</h4>
				</div>
				<div class="modal-body">
					{{$profile_field.fields.acl nofilter}}
				</div>
			</div>
		</div>
	</div>
</fieldset>
