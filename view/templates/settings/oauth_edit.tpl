
<h1>{{$title}}</h1>

<form method="POST">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{include file="field_input.tpl" field=$name}}
{{include file="field_input.tpl" field=$key}}
{{include file="field_input.tpl" field=$secret}}
{{include file="field_input.tpl" field=$redirect}}
{{include file="field_input.tpl" field=$icon}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit|escape:'html'}}" />
<!-- <input type="submit" name="cancel" class="settings-submit" value="{{$cancel}}" /> -->
</div>

</form>
