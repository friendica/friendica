{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<h1>{{$title}}</h1>
<div class="settings-section">
	<div id="settings-form">
		<form action="settings/importcontacts" method="post" autocomplete="off" enctype="multipart/form-data">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			<input type="hidden" name="MAX_FILE_SIZE" value="{{$importcontact_maxsize}}" />
			{{include file="field_input.tpl" field=$legacy_contact}}
			<hr>
			<p id="settings-pagetype-desc">{{$importcontact_text}}</p>
			<p><input type="file" name="importcontact-filename" /></p>

			<div class="settings-submit-wrapper">
				<input type="submit" name="importcontact-submit" class="importcontact-submit settings-submit btn btn-default"
					value="{{$importcontact_button}}" />
			</div>
		</form>
</div>
</div>
