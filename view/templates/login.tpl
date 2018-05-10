

<form id="login-form" action="{{$dest_url}}" role="form" method="post" >
<div id="login-group" role="group" aria-labelledby="login-head">
	<input type="hidden" name="auth-params" value="login" />

	<h3 id="login-head" class="sr-only">{{$login}}</h3>

	<div id="login_standard">
	{{include file="field_input.tpl" field=$lname}}
	{{include file="field_password.tpl" field=$lpassword}}
	<div id="login-lost-password-link">
		<a href="lostpass" title="{{$lostpass|escape:'html'}}" id="lost-password-link" >{{$lostlink}}</a>
	</div>
	</div>
	
	{{if $openid}}
		<div id="login_openid">
		{{include file="field_openid.tpl" field=$lopenid}}
		</div>
	{{/if}}

	<div id="login-submit-wrapper" >
		<input type="submit" name="submit" id="login-submit-button" value="{{$login|escape:'html'}}" />
	</div>

	{{include file="field_checkbox.tpl" field=$lremember}}
	
	{{foreach $hiddens as $k=>$v}}
		<input type="hidden" name="{{$k}}" value="{{$v|escape:'html'}}" />
	{{/foreach}}
	
</div>
</form>

{{if $register}}
<div id="login-extra-links">
	<h3 id="login-head" class="sr-only">{{$register.title|escape:'html'}}</h3>
	<a href="register" title="{{$register.title|escape:'html'}}" id="register-link">{{$register.desc}}</a>
</div>
{{/if}}

<script type="text/javascript"> $(document).ready(function() { $("#id_{{$lname.0}}").focus();} );</script>
