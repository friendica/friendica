{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<h1>{{$title}}</h1>

<form action="settings/features" method="post" autocomplete="off">
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

	{{foreach $features as $key => $f}}
		<h2 class="settings-heading"><a href="javascript:;">{{$f.0}}</a></h2>
		<div class="settings-content-block {{if $key == $network_mode}}network sortable{{/if}}">
			{{if $key == $network_mode}}
			<input type="hidden" id="feature_widgetorder" name="feature_widgetorder" value=""/>
			<p tabindex="0">{{$sortable}}</p>
			{{/if}}
			{{foreach $f.1 as $fcat}}
				{{include file="field_checkbox.tpl" field=$fcat}}
			{{/foreach}}
			<div class="settings-submit-wrapper">
				{{if $key == $network_mode}}
					{{include file="field_checkbox.tpl" field=$reset}}
				{{/if}}
				<input type="submit" name="submit" class="settings-features-submit" value="{{$submit}}"/>
			</div>
		</div>
	{{/foreach}}
</form>
