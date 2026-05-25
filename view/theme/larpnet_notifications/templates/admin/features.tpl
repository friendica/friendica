{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$title}}</h1>
	<form action="{{$baseurl}}/admin/features" method="post" autocomplete="off">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">
		{{* We organize the settings in collapsable panel-groups *}}
		<div class="panel-group panel-group-settings" id="settings" role="tablist" aria-multiselectable="true">
			{{foreach $features as $g => $f}}
				<div class="panel">
					<div class="section-subtitle-wrapper panel-heading" role="tab" id="{{$g}}-settings-title">
						<h2>
							<button class="btn-link accordion-toggle collapsed" data-toggle="collapse" data-parent="#settings" href="#{{$g}}-settings-content" aria-expanded="true" aria-controls="{{$g}}-settings-content">
								{{$f.0}}
							</button>
						</h2>
					</div>
					<div id="{{$g}}-settings-content" class="panel-collapse collapse" role="tabpanel" aria-labelledby="{{$g}}-settings-title">
						<div class="panel-body">
							{{foreach $f.1 as $fcat}}
								<div class="settings-block">
									{{include file="field_select.tpl" field=$fcat}}
								</div>
							{{/foreach}}
						</div>
						<div class="panel-footer">
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
						</div>
					</div>
				</div>
			{{/foreach}}
		</div>

	</form>
</div>
