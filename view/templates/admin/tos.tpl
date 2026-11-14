{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>
	<div class="settings-section">
  <p>{{$intro}}</p>
	<form action="{{$baseurl}}/admin/tos" method="post">
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		{{include file="field_checkbox.tpl" field=$displaytos}}
		{{include file="field_checkbox.tpl" field=$displayprivstatement}}
		{{include file="field_textarea.tpl" field=$tostext}}
		{{include file="field_textarea.tpl" field=$tosrules}}
		<div class="settings-submit-wrapper"><input type="submit" class="settings-submit btn btn-default" name="page_tos" value="{{$submit}}" /></div>
	</form>
	<h2>{{$preview}}</h2>
	{{for $i=1 to 3}}
	<p>{{$privtext[$i] nofilter}}</p>
	{{/for}}
	</div>
</div>

