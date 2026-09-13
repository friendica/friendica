{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">
	<h1>{{$ptitle}}</h1>
	<form action="settings/display" id="settings-form" method="post" autocomplete="off">
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<div class="panel-group panel-group-settings" id="settings" role="tablist" aria-multiselectable="true">
			<details class="panel">
				<summary class="section-subtitle-wrapper panel-heading accordion-toggle" id="theme-settings-title"><h2>{{$themes_title}}</h2></summary>
				<div id="theme-settings-content">
					<div class="panel-body">
						{{include file="field_themeselect.tpl" field=$theme}}

						{{* Show the mobile theme selection only if mobile themes are available *}}
						{{if count($mobile_theme.4) > 1}}
						{{include file="field_themeselect.tpl" field=$mobile_theme}}
						{{/if}}

						<p class="alert alert-info" id="theme-changed">{{$theme_changed_text}}</p>

					{{if $theme_config}}
						<h3>{{$themes_settings_for}}</h3>
						{{$theme_config nofilter}}
					{{/if}}
					</div>

					<div class="panel-footer">
						{{*Technically the variable below could just be hardcoded, but it's relevant if at some point this template is copied to the main template dir so other themes starting using it *}}
						<button type="submit" name="{{$theme.2}}-settings-submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</details>

			<details class="panel"{{if !$theme && !$mobile_theme && !$theme_config}} open{{/if}}>
				<summary class="section-subtitle-wrapper panel-heading accordion-toggle"  id="content-settings-title"><h2>{{$d_cset}}</h2></summary>
				<div id="content-settings-content">
					<div class="panel-body">
						{{include file="field_input.tpl" field=$itemspage_network}}
						{{include file="field_input.tpl" field=$itemspage_mobile_network}}
						{{include file="field_checkbox.tpl" field=$enable_smile}}
						{{include file="field_checkbox.tpl" field=$enable_spa}}
						{{include file="field_checkbox.tpl" field=$update_content}}
						{{include file="field_checkbox.tpl" field=$infinite_scroll}}
						{{include file="field_checkbox.tpl" field=$enable_smart_threading}}
						{{include file="field_checkbox.tpl" field=$enable_dislike}}
						{{include file="field_checkbox.tpl" field=$display_resharer}}
						{{include file="field_checkbox.tpl" field=$stay_local}}
						{{include file="field_checkbox.tpl" field=$compact_timeline}}
						{{include file="field_checkbox.tpl" field=$show_page_drop}}
						{{include file="field_checkbox.tpl" field=$display_eventlist}}
						{{include file="field_select.tpl" field=$preview_mode}}
						{{include file="field_checkbox.tpl" field=$hide_empty_descriptions}}
						{{include file="field_checkbox.tpl" field=$hide_custom_emojis}}
						{{include file="field_select.tpl" field=$platform_icon_style}}
						{{include file="field_checkbox.tpl" field=$embed_remote_media}}
						{{include file="field_checkbox.tpl" field=$embed_media}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</details>

			<details class="panel"{{if !$theme && !$mobile_theme && !$theme_config}} open{{/if}}>
				<summary class="section-subtitle-wrapper panel-heading accordion-toggle"  id="timeline-settings-title"><h2>{{$timeline_title}}</h2></summary>
				<div id="timeline-settings-content">
						<p tabindex="0">{{$timeline_explanation}} {{$sortable}}</p>
						<h3 tabindex="0">{{$timeline_enable}}</h3>
					<div class="panel-body timelines-widget sortable">
						<input type="hidden" id="widget_timelineorder" name="widget_timelineorder" value=""/>
						{{foreach $timelines as $t}}
							{{include file="field_checkbox.tpl" field=$t.enable}}
						{{/foreach}}
						<div class="panel-footer">
							{{include file="field_checkbox.tpl" field=$reset_widget}}
							<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
						</div>
					</div>
						<h3 tabindex="0">{{$timeline_bookmark}}</h3>
						<input type="hidden" id="menu_timelineorder" name="menu_timelineorder" value=""/>
					<div class="panel-body timelines-menu sortable">
						{{foreach $timelines as $t}}
							{{include file="field_checkbox.tpl" field=$t.bookmark}}
						{{/foreach}}
					</div>
					<div class="panel-footer">
						{{include file="field_checkbox.tpl" field=$reset_menu}}
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</details>

			<details class="panel"{{if !$theme && !$mobile_theme && !$theme_config}} open{{/if}}>
				<summary class="section-subtitle-wrapper panel-heading accordion-toggle"  id="channel-settings-title"><h2>{{$channel_title}}</h2></summary>
				<div id="channel-settings-content">
					<div class="panel-body">
						{{include file="field_select.tpl" field=$channel_languages}}
					</div>
					{{if $has_timeline_channels}}
						<div class="panel-body">
							{{include file="field_select.tpl" field=$timeline_channels}}
						</div>
					{{/if}}
					{{if $has_filter_channels}}
						<div class="panel-body">
							{{include file="field_select.tpl" field=$filter_channels}}
						</div>
					{{/if}}
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</details>

			<details class="panel"{{if !$theme && !$mobile_theme && !$theme_config}} open{{/if}}>
				<summary class="section-subtitle-wrapper panel-heading accordion-toggle"  id="calendar-settings-title"><h2>{{$calendar_title}}</h2></summary>
				<div id="calendar-settings-content">
					<div class="panel-body">
						{{include file="field_select.tpl" field=$first_day_of_week}}
						{{include file="field_select.tpl" field=$calendar_default_view}}
					</div>
					<div class="panel-footer">
						<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
					</div>
				</div>
			</details>
		</div>
	</form>
</div>
