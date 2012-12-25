
<div class="login-form">
<form action="{{$dest_url}}" method="post" >
	<input type="hidden" name="auth-params" value="login" />

	<div id="login_standard">
	{{include file="field_input.tpl" field=$lname}}
	{{include file="field_password.tpl" field=$lpassword}}
	</div>
	
	{{if $openid}}
			<div id="login_openid">
			{{include file="field_openid.tpl" field=$lopenid}}
			</div>
	{{/if}}

	<br />
	<div id='login-footer'>
<!--	<div class="login-extra-links">
	By signing in you agree to the latest <a href="tos.html" title="{{$tostitle}}" id="terms-of-service-link" >{{$toslink}}</a> and <a href="privacy.html" title="{{$privacytitle}}" id="privacy-link" >{{$privacylink}}</a>
	</div>-->

	<br />
	{{include file="field_checkbox.tpl" field=$lremember}}

	<div id="login-submit-wrapper" >
		<input type="submit" name="submit" id="login-submit-button" value="{{$login}}" />
	</div>

	<br /><br />
	<div class="login-extra-links">
		{{if $register}}<a href="register" title="{{$register.title}}" id="register-link">{{$register.desc}}</a>{{/if}}
        <a href="lostpass" title="{{$lostpass}}" id="lost-password-link" >{{$lostlink}}</a>
	</div>
	</div>
	
	{{foreach $hiddens as $k=>$v}}
		<input type="hidden" name="{{$k}}" value="{{$v}}" />
	{{/foreach}}
	
	
</form>
</div>

<script type="text/javascript">window.loginName = "{{$lname.0}}";</script>
