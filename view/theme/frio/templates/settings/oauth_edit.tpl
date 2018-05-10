
<h2 class="heading">{{$title}}</h2>

<form method="POST">
	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	{{include file="field_input.tpl" field=$name}}
	{{include file="field_input.tpl" field=$key}}
	{{include file="field_input.tpl" field=$secret}}
	{{include file="field_input.tpl" field=$redirect}}
	{{include file="field_input.tpl" field=$icon}}

	<div class="form-group pull-right settings-submit-wrapper" >
		<button type="submit" name="submit" class="btn btn-primary" value="{{$submit|escape:'html'}}">{{$submit}}</button>
	</div>
	<div class="clear"></div>

</form>
