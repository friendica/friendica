<h1>$ptitle</h1>

<form action="settings/display" id="settings-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='$form_security_token'>

{{inc field_themeselect.tpl with $field=$theme }}{{endinc}}
{{inc field_themeselect.tpl with $field=$mobile_theme }}{{endinc}}
{{inc field_input.tpl with $field=$ajaxint }}{{endinc}}
{{inc field_input.tpl with $field=$itemspage_network }}{{endinc}}
{{inc field_checkbox.tpl with $field=$nosmile}}{{endinc}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="$submit" />
</div>

{{ if $theme_config }}
<h2>Theme settings</h2>
$theme_config
{{ endif }}

</form>
