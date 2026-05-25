{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<div id="settings-form">
		{{* Import contacts CSV *}}
		<form action="settings/importcontacts" method="post" autocomplete="off" class="panel"
			enctype="multipart/form-data">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<div class="panel-body">
				{{include file="field_input.tpl" field=$legacy_contact}}
				<hr>
				<div id="importcontact-relocate-desc">{{$importcontact_text}}</div>
				<input type="hidden" name="MAX_FILE_SIZE" value="{{$importcontact_maxsize}}" />
				<input type="file" name="importcontact-filename" />
			</div>
			<div class="panel-footer">
				<button type="submit" name="importcontact-submit" class="btn btn-primary"
					value="{{$submit}}">{{$submit}}</button>
			</div>
	</div>
	</form>
</div>
</div>