
<link rel="stylesheet" href="{{$baseurl}}/view/theme/frio/frameworks/jRange/jquery.range.css" type="text/css" media="screen" />
<script src="{{$baseurl}}/view/theme/quattro/jquery.tools.min.js"></script>
<script src="{{$baseurl}}/view/theme/frio/frameworks/jRange/jquery.range.js"></script>
<script type="text/javascript" src="{{$baseurl}}/js/ajaxupload.js" ></script>

{{include file="field_select.tpl" field=$schema}}

{{if $nav_bg}}{{include file="field_colorinput.tpl" field=$nav_bg}}{{/if}}
{{if $nav_icon_color}}{{include file="field_colorinput.tpl" field=$nav_icon_color}}{{/if}}
{{if $link_color}}{{include file="field_colorinput.tpl" field=$link_color}}{{/if}}

{{if $bgcolor}}{{include file="field_colorinput.tpl" field=$bgcolor}}{{/if}}

{{* The slider for the content opacity - We use no template for this since it is only used at this page *}}
{{if $contentbg_transp}}
<div class="form-group field input color">
	<label for="id_{{$contentbg_transp.0}}" id="label_{{$contentbg_transp.0}}">{{$contentbg_transp.1}}</label>
	<input type="hidden" class="form-control color slider-input" name="{{$contentbg_transp.0}}" id="{{$contentbg_transp.0}}" type="text" value="{{$contentbg_transp.2}}" aria-describedby="{{$contentbg_transp.0}}_tip">
	<span id="{{$contentbg_transp.0}}_tip" class="help-block" role="tooltip">{{$contentbg_transp.3}}</span>
	<div id="end_{{$contentbg_transp.0}}" class="field_end"></div>
</div>
{{/if}}

{{if $background_image}}{{include file="field_fileinput.tpl" field=$background_image}}{{/if}}

<div id="frio_bg_image_options" style="display: none;">
{{foreach $bg_image_options as $options}}
	{{include file="field_radio.tpl" field=$options}}
{{/foreach}}
</div>

<script>
	$(function(){
		$("#frio_nav_bg, #frio_nav_icon_color, #frio_background_color, #frio_link_color").colorpicker({format: 'hex', align: 'left'});

		// show image options when user user starts to type the address of the image
		$("#id_frio_background_image").keyup(function(){
			var elText = $(this).val();
			if(elText.length !== 0) {
				$("#frio_bg_image_options").show();
			} else {
				$("#frio_bg_image_options").hide();
			}
		});

		// show the image options is there is allready an image
		if($("#id_frio_background_image").val().length != 0) {
				$("#frio_bg_image_options").show();
		}

		$('.slider-input').jRange({
			from: 0,
			to: 100,
			step: 1,
			scale: [0,10,20,30,40,50,60,70,80,90,100],
			format: '%s',
			width: '100%',
			showLabels: true,
			theme: 'theme-frio',
		});

	});
</script>

<div class="settings-submit-wrapper form-group pull-right">
	<button type="submit" value="{{$submit}}" class="settings-submit btn btn-primary" name="frio-settings-submit">{{$submit}}</button>
</div>
<div class="clearfix"></div>

<script>
    $(".inputRange").rangeinput();
</script>
