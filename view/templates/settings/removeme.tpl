{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
    {{include file="section_title.tpl" title=$l10n.title}}
	<div class="settings-section">
		<div id="remove-account-wrapper">
			<div id="remove-account-desc">{{$l10n.desc nofilter}}</div>

			{{$hovercard nofilter}}

			<form action="settings/removeme" autocomplete="off" method="post">
            	{{include file="field_password.tpl" field=$password}}

				<div class="form-group pull-right settings-submit-wrapper">
					<button type="submit" name="submit" class="settings-submit btn btn-primary" value="{{$l10n.title}}"><i class="ri ri-delete-bin-line ri-fw"></i>&nbsp;{{$l10n.title}}</button>
				</div>
				<div class="clear"></div>
			</form>
		</div>
	</div>
</div>
