{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<h3>{{$regtitle}}</h3>

<h4 style="margin-top: 25px">Normal registration</h4>

<form action="register" method="post" id="register-form">

	<input type="hidden" name="photo" value="{{$photo}}" />
	<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

	{{if $registertext != ""}}<div class="error-message">{{$registertext nofilter}} </div>{{/if}}

	{{if $explicit_content}} <p id="register-explicit-content">{{$explicit_content_note}}</p> {{/if}}

{{if $invitations}}
	<p id="register-invite-desc">{{$invite_desc nofilter}}</p>
	<div id="register-invite-wrapper">
		<label for="register-invite" id="label-register-invite">{{$invite_label}}</label>
		<input type="text" maxlength="60" size="32" name="invite_id" id="register-invite" value="{{$invite_id}}">
	</div>
	<div id="register-name-end"></div>
{{/if}}

	<div id="register-name-wrapper">
		<label for="register-name" id="label-register-name">{{$namelabel}}</label>
		<input type="text" maxlength="60" size="32" name="username" id="register-name" value="{{$username}}" required>
	</div>
	<div id="register-name-end"></div>


	{{if !$additional}}
		<div id="register-email-wrapper">
			<label for="register-email" id="label-register-email">{{$addrlabel}}</label>
			<input type="text" maxlength="60" size="32" name="field1" id="register-email" value="{{$email}}" required>
		</div>
		<div id="register-email-end"></div>
	{{/if}}

{{if $ask_password}}
	{{include file="field_password.tpl" field=$password1}}
	{{include file="field_password.tpl" field=$password2}}
{{/if}}

	<p id="register-nickname-desc">{{$nickdesc nofilter}}</p>

	<div id="register-nickname-wrapper">
		<label for="register-nickname" id="label-register-nickname">{{$nicklabel}}</label>
		<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" value="{{$nickname}}" required><div id="register-sitename">@{{$sitename}}</div>
	</div>
	<div id="register-nickname-end"></div>

	<input type="input" id=tarpit" name="email" style="display: none;" placeholder="Don't enter anything here"/>

	{{if $additional}}
		{{include file="field_password.tpl" field=$parent_password}}
	{{/if}}

{{if $permonly}}
	{{include file="field_textarea.tpl" field=$permonlybox}}
{{/if}}

{{if $oidlabel}}

	<h4 style="margin-top: 45px">{{$openid_title}}</h4>

	<p id="register-fill-desc">{{$fillwith}}</p>
	<p id="register-fill-ext">{{$fillext}}</p>
	<div id="register-openid-wrapper">
	<label for="register-openid" id="label-register-openid">{{$oidlabel}}</label><input type="text" maxlength="60" size="32" name="openid_url" class="openid" id="register-openid" value="{{$openid}}">
	</div>
	<div style="margin-top: 20px;" id="register-openid-end"></div>
{{/if}}

{{if $showtoslink}}
	<p><a href="{{$baseurl}}/tos">{{$tostext}}</a></p>
{{/if}}
{{if $showprivstatement}}
	<h4>{{$privstatement.0}}</h4>
	{{for $i=1 to 3}}
	<p>{{$privstatement[$i] nofilter}}</p>
	{{/for}}
{{/if}}

<hr>

	{{$publish nofilter}}

	<div id="register-submit-wrapper">
		<input type="submit" name="submit" id="register-submit-button" value="{{$regbutt}}" />
	</div>
	<div id="register-submit-end"></div>

	{{if !$additional}}
		<hr>
		<h4>{{$importh}}</h4>
		<p>{{$importdesc}}</p>
		<div id ="import-profile">
			<a class="btn btn-secondary" href="user/import">{{$importt}}</a>
		</div>
	{{/if}}
</form>
