{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

{{* Display system messages *}}
{{if $notices}}
	{{foreach $notices as $notice}}
		<div class="alert alert-warning" role="alert">{{$notice}}</div>
	{{/foreach}}
{{/if}}

<form id="login-form" action="{{$dest_url}}" role="form" method="post">
<div id="login-group" role="group" aria-labelledby="login-head">
	<input type="hidden" name="auth-params" value="login" />

	<h3 id="login-head" class="sr-only">{{$login}}</h3>

	<div id="login_standard">
	{{include file="field_input.tpl" field=$lname}}
	{{include file="field_password.tpl" field=$lpassword}}
	<div id="login-lost-password-link">
		<a href="lostpass" title="{{$lostpass}}" id="lost-password-link">{{$lostlink}}</a>
	</div>
	</div>

	{{if $openid}}
		<div id="login_openid">
		{{include file="field_openid.tpl" field=$lopenid}}
		</div>
	{{/if}}

	<div id="login-submit-wrapper">
		<input type="submit" name="submit" id="login-submit-button" value="{{$login}}" />
	</div>

	{{include file="field_checkbox.tpl" field=$lremember}}

	{{foreach $hiddens as $k=>$v}}
		<input type="hidden" name="{{$k}}" value="{{$v}}" />
	{{/foreach}}

</div>
</form>

{{if $register}}
<div id="login-extra-links">
	<h3 id="login-head" class="sr-only">{{$register.title}}</h3>
	<a href="{{$register.url}}" title="{{$register.title}}" id="register-link">{{$register.desc}}</a>
</div>
{{/if}}

<script type="text/javascript"> $(document).ready(function() { $("#id_{{$lname.0}}").focus();} );</script>
