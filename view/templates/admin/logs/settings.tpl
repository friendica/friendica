{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id='adminpage'>
    <h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/logs" method="post">
	    <input type='hidden' name='form_security_token' value="{{$form_security_token}}">

	    {{include file="field_checkbox.tpl" field=$debugging}}
	    {{include file="field_input.tpl" field=$logfile}}
	    {{include file="field_select.tpl" field=$loglevel}}

			<div class="submit"><button type"submit" class="btn btn-primary" name="page_logs" value="{{$submit}}">{{$submit}}</button></div>

	</form>

	<h2>{{$phpheader}}</h2>
	<div>
		<p>{{$phplogenabled}}<p>
		<p>{{$phphint}}</p>
		<pre>{{$phplogcode}}</pre>
	</div>

</div>
