
<div id="invite-wrapper">

	<h3 class="heading">{{$title}}</h3>

	<form action="invite" method="post" id="invite-form" >
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

		<div id="invite-content-wrapper">
			{{include file="field_textarea.tpl" field=$recipients}}
			{{include file="field_textarea.tpl" field=$message}}

			<div id="invite-submit-wrapper" class="form-group pull-right">
				<button type="submit" name="submit" class="btn btn-primary">{{$submit|escape:'html'}}</button>
			</div>
			<div class="clear"></div>
		</div>
	</form>
</div>
