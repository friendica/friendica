<img style="float:left; margin:5px 20px 0 0" src="/images/friendica-128.png" title="friendica">

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

	<div id="login-submit-wrapper" >
		<input type="submit" name="submit" id="login-submit-button" value="{{$login}}" />
	</div>

	<br /><br />
	<div class="login-extra-links">
		{{if $register}}	
			<a href="register" title="{{$register.title}}" id="register-link">{{$register.desc}}</a><br />
			<a href="lostpass" title="{{$lostpass}}" id="lost-password-link" >{{$lostlink}}</a>
		{{/if}}
	</div>
	
	{{foreach $hiddens as $k=>$v}}
		<input type="hidden" name="{{$k}}" value="{{$v}}" />
	{{/foreach}}
	
	
</form>
</div>

<script type="text/javascript">window.loginName = "{{$lname.0}}";</script>
