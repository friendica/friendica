
<form action="{{$dest_url}}" method="post" >
	<input type="hidden" name="auth-params" value="login" />

	<div id="login_standard">
	{{include file="file:{{$field_input}}" field=$lname}}
	{{include file="file:{{$field_password}}" field=$lpassword}}
	</div>
	
	{{if $openid}}
			<div id="login_openid">
			{{include file="file:{{$field_openid}}" field=$lopenid}}
			</div>
	{{/if}}

	{{include file="file:{{$field_checkbox}}" field=$lremember}}

	<div id="login-extra-links">
		{{if $register}}<a href="register" title="{{$register.title}}" id="register-link">{{$register.desc}}</a>{{/if}}
        <a href="lostpass" title="{{$lostpass}}" id="lost-password-link" >{{$lostlink}}</a>
	</div>
	
	<div id="login-submit-wrapper" >
		<input type="submit" name="submit" id="login-submit-button" value="{{$login}}" />
	</div>
	
	{{foreach $hiddens as $k=>$v}}
		<input type="hidden" name="{{$k}}" value="{{$v}}" />
	{{/foreach}}
	
	
</form>


<script type="text/javascript"> $(document).ready(function() { $("#id_{{$lname.0}}").focus();} );</script>
