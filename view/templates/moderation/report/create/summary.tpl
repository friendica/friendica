{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$l10n.title}} - {{$l10n.page}}</h1>
	<p>{{$l10n.description}}</p>

	<div>
		{{$summary nofilter}}
	</div>

	<h2>{{$l10n.contact_action_title}}</h2>
	<p>{{$l10n.contact_action_desc}}</p>
	<form action="" method="post">
		<input type="hidden" name="cid" value="{{$cid}}">
		<input type="hidden" name="category" value="{{$category}}">
		<input type="hidden" name="rule-ids" value="{{$ruleIds}}">
		<input type="hidden" name="uri-ids" value="{{$uriIds}}">
		<input type="hidden" name="return" value="{{$return}}">

		{{include file="field_radio.tpl" field=$nothing}}
		{{include file="field_radio.tpl" field=$collapse}}
		{{include file="field_radio.tpl" field=$ignore}}
		{{include file="field_radio.tpl" field=$block}}

{{if $display_forward}}
        {{include file="field_checkbox.tpl" field=$forward}}
{{/if}}

		<p><button type="submit" name="report_create" class="btn btn-primary">{{$l10n.submit}}</button></p>
	</form>
</div>
