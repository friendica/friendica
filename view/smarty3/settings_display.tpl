<h1>{{$ptitle}}</h1>

<form action="settings/display" id="settings-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{include file="file:{{$field_themeselect}}" field=$theme}}
{{include file="file:{{$field_themeselect}}" field=$mobile_theme}}
{{include file="file:{{$field_input}}" field=$ajaxint}}
{{include file="file:{{$field_input}}" field=$itemspage_network}}
{{include file="file:{{$field_checkbox}}" field=$nosmile}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>

{{if $theme_config}}
<h2>Theme settings</h2>
{{$theme_config}}
{{/if}}

</form>
