
<form action="invite" method="post" id="invite-form" >

	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

	<div id="invite-wrapper">

		<h3>{{$title}}</h3>

		{{include file="field_textarea.tpl" field=$recipients}}
		{{include file="field_textarea.tpl" field=$message}}

		<div id="invite-submit-wrapper">
			<input type="submit" name="submit" value="{{$submit|escape:'html'}}" />
		</div>

	</div>
</form>
