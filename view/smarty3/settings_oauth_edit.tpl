<h1>{{$title}}</h1>

<form method="POST">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{include file="file:{{$field_input}}" field=$name}}
{{include file="file:{{$field_input}}" field=$key}}
{{include file="file:{{$field_input}}" field=$secret}}
{{include file="file:{{$field_input}}" field=$redirect}}
{{include file="file:{{$field_input}}" field=$icon}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
<input type="submit" name="cancel" class="settings-submit" value="{{$cancel}}" />
</div>

</form>
