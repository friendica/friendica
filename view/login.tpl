
<form action="$dest_url" method="post" >
	<input type="hidden" name="auth-params" value="login" />

	<div id="login_standard">
	{{ inc $field_input with $field=$lname }}{{ endinc }}
	{{ inc $field_password with $field=$lpassword }}{{ endinc }}
	</div>
	
	{{ if $openid }}
			<div id="login_openid">
			{{ inc $field_openid with $field=$lopenid }}{{ endinc }}
			</div>
	{{ endif }}

	{{ inc $field_checkbox with $field=$lremember }}{{ endinc }}

	<div id="login-extra-links">
		{{ if $register }}<a href="register" title="$register.title" id="register-link">$register.desc</a>{{ endif }}
        <a href="lostpass" title="$lostpass" id="lost-password-link" >$lostlink</a>
	</div>
	
	<div id="login-submit-wrapper" >
		<input type="submit" name="submit" id="login-submit-button" value="$login" />
	</div>
	
	{{ for $hiddens as $k=>$v }}
		<input type="hidden" name="$k" value="$v" />
	{{ endfor }}
	
	
</form>


<script type="text/javascript"> $(document).ready(function() { $("#id_$lname.0").focus();} );</script>
