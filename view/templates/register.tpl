<h3>{{$regtitle}}</h3>

<form action="register" method="post" id="register-form">

	<input type="hidden" name="photo" value="{{$photo}}" />

	{{if $registertext != ""}}<div class="error-message">{{$registertext}} </div>{{/if}}


	<p id="register-realpeople">{{$realpeople}}</p>

	<p id="register-fill-desc">{{$fillwith}}</p>
	<p id="register-fill-ext">{{$fillext}}</p>

{{if $oidlabel}}
	<div id="register-openid-wrapper" >
    	<label for="register-openid" id="label-register-openid" >{{$oidlabel}}</label><input 	type="text" maxlength="60" size="32" name="openid_url" class="openid" id="register-openid" value="{{$openid|escape:'html'}}" >
	</div>
	<div id="register-openid-end" ></div>
{{/if}}

{{if $invitations}}

	<p id="register-invite-desc">{{$invite_desc}}</p>
	<div id="register-invite-wrapper" >
		<label for="register-invite" id="label-register-invite" >{{$invite_label}}</label>
		<input type="text" maxlength="60" size="32" name="invite_id" id="register-invite" value="{{$invite_id}}" >
	</div>
	<div id="register-name-end" ></div>

{{/if}}


	<div id="register-name-wrapper" >
		<label for="register-name" id="label-register-name" >{{$namelabel}}</label>
		<input type="text" maxlength="60" size="32" name="username" id="register-name" value="{{$username|escape:'html'}}" >
	</div>
	<div id="register-name-end" ></div>


	<div id="register-email-wrapper" >
		<label for="register-email" id="label-register-email" >{{$addrlabel}}</label>
		<input type="text" maxlength="60" size="32" name="email" id="register-email" value="{{$email|escape:'html'}}" >
	</div>
	<div id="register-email-end" ></div>

{{if $passwords}}
	{{include file="field_password.tpl" field=$password1}}
	{{include file="field_password.tpl" field=$password2}}
{{/if}}

	<p id="register-nickname-desc" >{{$nickdesc}}</p>

	<div id="register-nickname-wrapper" >
		<label for="register-nickname" id="label-register-nickname" >{{$nicklabel}}</label>
		<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" value="{{$nickname|escape:'html'}}" ><div id="register-sitename">@{{$sitename}}</div>
	</div>
	<div id="register-nickname-end" ></div>

{{if $permonly}}
    {{include file="field_textarea.tpl" field=$permonlybox}}
{{/if}}

	{{$publish}}

	<div id="register-submit-wrapper">
		<input type="submit" name="submit" id="register-submit-button" value="{{$regbutt|escape:'html'}}" />
	</div>
	<div id="register-submit-end" ></div>

<h3>{{$importh}}</h3>
	<div id ="import-profile">
		<a href="uimport">{{$importt}}</a>
	</div>
</form>

{{$license}}


