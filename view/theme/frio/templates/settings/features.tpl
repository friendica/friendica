{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<form action="settings/features" method="post" autocomplete="off">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{* We organize the settings in collapsable panel-groups *}}
		<div class="panel-group panel-group-settings" id="settings">
			{{foreach $features as $key => $f}}
			<details class="panel">
				<summary id="{{$key}}-settings-title" class="section-subtitle-wrapper panel-heading accordion-toggle"><h2>{{$f.0}}</h2></summary>
				<div id="{{$key}}-settings-content">
					<div class="panel-body {{if $key == $network_mode}}network sortable{{/if}}">
						{{if $key == $network_mode}}
						<input type="hidden" id="feature_widgetorder" name="feature_widgetorder" value=""/>
						<p tabindex="0">{{$sortable}}</p>
						{{/if}}
						{{foreach $f.1 as $fcat}}
							{{include file="field_checkbox.tpl" field=$fcat}}
						{{/foreach}}
						<div class="clear"></div>
					</div>
					<div class="panel-footer">
						{{if $key == $network_mode}}
							{{include file="field_checkbox.tpl" field=$reset}}
						{{/if}}
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</details>
			{{/foreach}}
		</div>

	</form>
</div>
