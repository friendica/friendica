
<h1>{{$ptitle}}</h1>

<form action="settings/display" id="settings-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{include file="field_themeselect.tpl" field=$theme}}
{{include file="field_input.tpl" field=$itemspage_network}}
{{include file="field_themeselect.tpl" field=$mobile_theme}}
{{include file="field_input.tpl" field=$itemspage_mobile_network}}
{{include file="field_input.tpl" field=$ajaxint}}
{{include file="field_checkbox.tpl" field=$no_auto_update}}
{{include file="field_checkbox.tpl" field=$nosmile}}
{{include file="field_checkbox.tpl" field=$noinfo}}
{{include file="field_checkbox.tpl" field=$infinite_scroll}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit|escape:'html'}}" />
</div>

{{if $theme_config}}
<h2>{{$stitle}}</h2>
{{$theme_config}}
{{/if}}

</form>
