{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<script src="{{$baseurl}}/view/theme/frio/js/jquery.tools.min.js?v={{$VERSION}}"></script>
<script type="text/javascript" src="{{$baseurl}}/view/js/ajaxupload.js?v={{$VERSION}}"></script>

<div class="form-group field select">
	<label for="id_{{$scheme.0}}">{{$scheme.1}}</label>
	<select name="{{$scheme.0}}" id="id_{{$scheme.0}}" class="form-control">
	{{foreach $scheme.3 as $value => $label}}
		<option value="{{$value}}" {{if $value == $scheme.2}}selected="selected"{{/if}}>{{$label}}</option>
	{{/foreach}}
	</select>
</div>

{{if $scheme_accent}}
<div class="form-group">
	<p><label>{{$scheme_accent.1}}</label></p>
	<div id="frio-accents">
		<div>
			<input id="blue" type="radio" name="{{$scheme_accent.0}}" value="{{$smarty.const.FRIO_SCHEME_ACCENT_BLUE}}" {{if $scheme_accent.2 == $smarty.const.FRIO_SCHEME_ACCENT_BLUE}} checked{{/if}}>
			<label for="blue" class="frio-accent" style="background-color: {{$smarty.const.FRIO_SCHEME_ACCENT_BLUE}}"></label>
		</div>
		<div>
			<input id="red" type="radio" name="{{$scheme_accent.0}}" value="{{$smarty.const.FRIO_SCHEME_ACCENT_RED}}" {{if $scheme_accent.2 == $smarty.const.FRIO_SCHEME_ACCENT_RED}} checked{{/if}}>
			<label for="red" class="frio-accent" style="background-color: {{$smarty.const.FRIO_SCHEME_ACCENT_RED}}"></label>
		</div>
		<div>
			<input id="purple" type="radio" name="{{$scheme_accent.0}}" value="{{$smarty.const.FRIO_SCHEME_ACCENT_PURPLE}}" {{if $scheme_accent.2 == $smarty.const.FRIO_SCHEME_ACCENT_PURPLE}} checked{{/if}}>
			<label for="purple" class="frio-accent" style="background-color: {{$smarty.const.FRIO_SCHEME_ACCENT_PURPLE}}"></label>
		</div>
		<div>
			<input id="green" type="radio" name="{{$scheme_accent.0}}" value="{{$smarty.const.FRIO_SCHEME_ACCENT_GREEN}}" {{if $scheme_accent.2 == $smarty.const.FRIO_SCHEME_ACCENT_GREEN}} checked{{/if}}>
			<label for="green" class="frio-accent" style="background-color: {{$smarty.const.FRIO_SCHEME_ACCENT_GREEN}}"></label>
		</div>
		<div>
			<input id="pink" type="radio" name="{{$scheme_accent.0}}" value="{{$smarty.const.FRIO_SCHEME_ACCENT_PINK}}" {{if $scheme_accent.2 == $smarty.const.FRIO_SCHEME_ACCENT_PINK}} checked{{/if}}>
			<label for="pink" class="frio-accent" style="background-color: {{$smarty.const.FRIO_SCHEME_ACCENT_PINK}}"></label>
		</div>
	</div>
</div>
{{/if}}

{{if $share_string}}{{include file="field_input.tpl" field=$share_string}}{{/if}}
{{if $nav_bg}}{{include file="field_colorinput.tpl" field=$nav_bg}}{{/if}}
{{if $nav_icon_color}}{{include file="field_colorinput.tpl" field=$nav_icon_color}}{{/if}}
{{if $link_color}}{{include file="field_colorinput.tpl" field=$link_color}}{{/if}}

{{if $background_color}}{{include file="field_colorinput.tpl" field=$background_color}}{{/if}}

{{if $contentbg_transp}}{{include file="field/range_percent.tpl" field=$contentbg_transp}}{{/if}}

{{if $background_image}}{{include file="field_fileinput.tpl" field=$background_image}}{{/if}}

<div id="frio_bg_image_options" style="display: none;">
	<label>{{$bg_image_options_title}}:</label>
{{foreach $bg_image_options as $options}}
	{{include file="field_radio.tpl" field=$options}}
{{/foreach}}
</div>

{{if $login_bg_image}}{{include file="field_fileinput.tpl" field=$login_bg_image}}{{/if}}
{{if $login_bg_color}}{{include file="field_colorinput.tpl" field=$login_bg_color}}{{/if}}

<script type="text/javascript">
	$(document).ready(function() {

		function GenerateShareString() {
			var theme = {};
			// Parse initial share_string
			if ($("#id_frio_nav_bg").length) {
				theme.nav_bg = $("#id_frio_nav_bg").val();
			}

			if ($("#id_frio_nav_icon_color").length) {
				theme.nav_icon_color = $("#id_frio_nav_icon_color").val();
			}

			if ($("#id_frio_link_color").length) {
				theme.link_color = $("#id_frio_link_color").val();
			}

			if ($("#id_frio_background_color").length) {
				theme.background_color = $("#id_frio_background_color").val();
			}

			if ($("#id_frio_background_image").length) {
				theme.background_image = $("#id_frio_background_image").val();

				if (theme.background_image.length > 0) {
					if ($("#id_frio_bg_image_option_stretch").is(":checked") == true) {
						theme.background_image_option = "stretch";
					}
					if ($("#id_frio_bg_image_option_cover").is(":checked") == true) {
						theme.background_image_option = "cover";
					}
					if ($("#id_frio_bg_image_option_contain").is(":checked") == true) {
						theme.background_image_option = "contain";
					}
					if ($("#id_frio_bg_image_option_repeat").is(":checked") == true) {
						theme.background_image_option = "repeat";
					}
				 }
			}

			if ($("#frio_contentbg_transp").length) {
				theme.contentbg_transp = $("#frio_contentbg_transp").val();
			}

			if ($("#id_frio_login_bg_image").length) {
				theme.login_bg_image = $("#id_frio_login_bg_image").val();
			}

			if ($("#id_frio_login_bg_color").length) {
				theme.login_bg_color = $("#id_frio_login_bg_color").val();
			}
			if (!($("#id_frio_share_string").is(":focus"))){
				var share_string = JSON.stringify(theme);
				$("#id_frio_share_string").val(share_string);
			}
		}

		// interval because jquery.val does not trigger events
		window.setInterval(GenerateShareString, 500);
		GenerateShareString();

		// Take advantage of the effects of previous comment
		$(document).on("input", "#id_frio_share_string", function() {
			theme = JSON.parse($("#id_frio_share_string").val());

			if ($("#id_frio_nav_bg").length) {
				$("#id_frio_nav_bg").val(theme.nav_bg);
			}

			if ($("#id_frio_nav_icon_color").length) {
				$("#id_frio_nav_icon_color").val(theme.nav_icon_color);
			}

			if ($("#id_frio_link_color").length) {
				 $("#id_frio_link_color").val(theme.link_color);
			}

			if ($("#id_frio_background_color").length) {
				$("#id_frio_background_color").val(theme.background_color);
			}

			if ($("#id_frio_background_image").length) {
				$("#id_frio_background_image").val(theme.background_image);
				var elText = theme.background_image;
				if(elText.length !== 0) {
					$("#frio_bg_image_options").show();
				} else {
					$("#frio_bg_image_options").hide();
				}

				switch (theme.background_image_option) {
					case 'stretch':
						$("#id_frio_bg_image_option_stretch").prop("checked", true);
						break;
					case 'cover':
						$("#id_frio_bg_image_option_cover").prop("checked", true);
						break;
					case 'contain':
						$("#id_frio_bg_image_option_contain").prop("checked", true);
						break;
					case 'repeat':
						$("#id_frio_bg_image_option_repeat").prop("checked", true);
						break;
				}
			}

			if ($("#frio_contentbg_transp").length) {
				$("#frio_contentbg_transp").val(theme.contentbg_transp);
			}

			if ($("#id_frio_login_bg_image").length) {
				$("#id_frio_login_bg_image").val(theme.login_bg_image);
			}

			if ($("#id_frio_login_bg_color").length) {
				$("#id_frio_login_bg_color").val(theme.login_bg_color);
			}
		});
		// Create colorpickers
		$("#frio_nav_bg, #frio_nav_icon_color, #frio_background_color, #frio_link_color, #frio_login_bg_color").colorpicker({format: 'hex', align: 'left'});

		if ($("#id_frio_background_image").length) {
			// show image options when user starts to type the address of the image
			$("#id_frio_background_image").keyup(function () {
				const elText = $(this).val();
				if (elText.length !== 0) {
					$("#frio_bg_image_options").show();
				} else {
					$("#frio_bg_image_options").hide();
				}
			});

			// show the image options if there is already an image
			if ($("#id_frio_background_image").val().length != 0) {
				$("#frio_bg_image_options").show();
			}
		}
	});
</script>

{{include file="field_checkbox.tpl" field=$always_open_compose}}

{{if $admin_theme_settings}}
<div class="settings-submit-wrapper pull-right">
	<button type="submit" value="{{$submit}}" class="settings-submit btn btn-primary" name="frio-settings-submit">{{$submit}}</button>
</div>
{{/if}}
<div class="clearfix"></div>
