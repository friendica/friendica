{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h2>{{$fsuggest_title}}</h2>
	<form id="fsuggest-form" action="fsuggest/{{$contact_id}}" method="post">
		{{include file="field_select.tpl" field=$fsuggest_select}}
		<div id="fsuggest-submit-wrapper">
			<button class="btn btn-primary" id="fsuggest-submit" type="submit" name="submit" value="{{$submit}}">{{$submit}}</button>
		</div>
	</form>
</div>
