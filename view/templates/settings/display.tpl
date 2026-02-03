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
	{{include file="field_checkbox.tpl" field=$embed_remote_media}}
	{{include file="field_checkbox.tpl" field=$embed_media}}
	<h2 tabindex="0">{{$timeline_title}}</h2>
	<p tabindex="0">{{$timeline_explanation}} {{$sortable}}</p>
	<h3 tabindex="0">{{$timeline_enable}}</h3>
	<div class="select timelines-widget sortable">
		<input type="hidden" id="widget_timelineorder" name="widget_timelineorder" value=""/>
		{{foreach $timelines as $t}}
			{{include file="field_checkbox.tpl" field=$t.enable}}
		{{/foreach}}
		<div class="settings-submit-wrapper">
			{{include file="field_checkbox.tpl" field=$reset_widget}}
		</div>
	</div>
	<h3 tabindex="0">{{$timeline_bookmark}}</h3>
	<div class="select timelines-menu sortable">
		<input type="hidden" id="menu_timelineorder" name="menu_timelineorder" value=""/>
		{{foreach $timelines as $t}}
			{{include file="field_checkbox.tpl" field=$t.bookmark}}
		{{/foreach}}
		<div class="settings-submit-wrapper">
			{{include file="field_checkbox.tpl" field=$reset_menu}}
		</div>
	</div>

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
