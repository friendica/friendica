{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<h1>{{$ptitle}}</h1>

<form action="settings/display" id="settings-form" method="post" autocomplete="off">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	{{include file="field_themeselect.tpl" field=$theme}}
	{{include file="field_input.tpl" field=$itemspage_network}}

	{{* Show the mobile theme selection only if mobile themes are available *}}
	{{if count($mobile_theme.4) > 1}}
		{{include file="field_themeselect.tpl" field=$mobile_theme}}
	{{/if}}

	{{include file="field_input.tpl" field=$itemspage_mobile_network}}
	{{include file="field_checkbox.tpl" field=$enable_smile}}
	{{include file="field_checkbox.tpl" field=$update_content}}
	{{include file="field_checkbox.tpl" field=$infinite_scroll}}
	{{include file="field_checkbox.tpl" field=$enable_smart_threading}}
	{{include file="field_checkbox.tpl" field=$enable_dislike}}
	{{include file="field_checkbox.tpl" field=$display_resharer}}
	{{include file="field_checkbox.tpl" field=$stay_local}}
	{{include file="field_checkbox.tpl" field=$show_page_drop}}
	{{include file="field_checkbox.tpl" field=$display_eventlist}}
	{{include file="field_select.tpl" field=$preview_mode}}
	{{include file="field_checkbox.tpl" field=$hide_empty_descriptions}}
	{{include file="field_checkbox.tpl" field=$hide_custom_emojis}}
	{{include file="field_select.tpl" field=$platform_icon_style}}
	<h2>{{$timeline_title}}</h2>
	{{$timeline_explanation}}
	<table class="table table-condensed table-striped table-bordered">
	<thead>
	<tr>
		<th>{{$timeline_label}}</th>
		<th>{{$timeline_descriptiom}}</th>
		<th>{{$timeline_enable}}</th>
		<th>{{$timeline_bookmark}}</th>
	</tr>
	</thead>
	<tbody>
	{{foreach $timelines as $t}}
		<tr>
			<td>{{$t.label}}</td>
			<td>{{$t.description}}</td>
			<td>{{include file="field_checkbox.tpl" field=$t.enable}}</td>
			<td>{{include file="field_checkbox.tpl" field=$t.bookmark}}</td>
		</tr>
	{{/foreach}}
	</tbody>
	</table>

	<h2>{{$channel_title}}</h2>
	{{include file="field_select.tpl" field=$channel_languages}}

	<h2>{{$calendar_title}}</h2>
	{{include file="field_select.tpl" field=$first_day_of_week}}
	{{include file="field_select.tpl" field=$calendar_default_view}}

	<div class="settings-submit-wrapper">
		<input type="submit" name="submit" class="settings-submit" value="{{$submit}}"/>
	</div>

	{{if $theme_config}}
		<h2>{{$stitle}}</h2>
		{{$theme_config nofilter}}
	{{/if}}

</form>
