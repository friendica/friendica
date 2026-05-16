{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<h2>{{$title}}</h2>


{{if $editable == 1}}
<div id="circle-edit-wrapper">
	<form action="circle/{{$gid}}" id="circle-edit-form" method="post">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

		{{include file="field_input.tpl" field=$gname}}
		{{if $drop}}{{$drop nofilter}}{{/if}}
		<div id="circle-edit-submit-wrapper">
			<input type="submit" name="submit" value="{{$submit}}">
			<button type="button" onclick="window.location.href='circle/markread/{{$gid}}?t={{$form_security_token_markread}}';" title="{{$markread_label}}">
				<i class="fa fa-eye" aria-hidden="true"></i> {{$markread_label}}
			</button>
		</div>
		<div id="circle-edit-select-end"></div>
	</form>
</div>
{{/if}}


{{if $circle_editor}}
	<div id="circle-update-wrapper">
		{{include file="circle_editor.tpl"}}
	</div>
{{/if}}
{{if $desc}}<div class="clear" id="circle-edit-desc">{{$desc nofilter}}</div>{{/if}}
