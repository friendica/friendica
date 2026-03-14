{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	{{* include the title template for the settings title *}}
	{{include file="section_title.tpl" title=$title}}

	<div class="panel-group panel-group-settings" id="settings-addons" role="tablist" aria-multiselectable="true">
{{foreach $addon_settings_forms as $addon => $addon_settings_form}}
		<form action="settings/addons/{{$addon}}" method="post" autocomplete="off" class="panel">
			<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
			{{$addon_settings_form nofilter}}
		</form>
{{foreachelse}}
		{{$no_addon_settings_configured}}
{{/foreach}}
	</div>

</div>
