<div class='register-form'>
<h2>{{$regtitle}}</h2>
<br /><br />

<form action="register" method="post" id="register-form">

	<input type="hidden" name="photo" value="{{$photo}}" />

	{{$registertext}}

	<p id="register-realpeople">{{$realpeople}}</p>

	<br />
{{if $oidlabel}}
	<div id="register-openid-wrapper" >
    	<label for="register-openid" id="label-register-openid" >{{$oidlabel}}</label><input 	type="text" maxlength="60" size="32" name="openid_url" class="openid" id="register-openid" value="{{$openid}}" >
	</div>
	<div id="register-openid-end" ></div>
{{/if}}

	<div class="register-explain-wrapper">
	<p id="register-fill-desc">{{$fillwith}} {{$fillext}}</p>
	</div>

	<br /><br />

{{if $invitations}}

	<p id="register-invite-desc">{{$invite_desc}}</p>
	<div id="register-invite-wrapper" >
		<label for="register-invite" id="label-register-invite" >{{$invite_label}}</label>
		<input type="text" maxlength="60" size="32" name="invite_id" id="register-invite" value="{{$invite_id}}" >
	</div>
	<div id="register-name-end" ></div>

{{/if}}


	<div id="register-name-wrapper" class="field input" >
		<label for="register-name" id="label-register-name" >{{$namelabel}}</label>
		<input type="text" maxlength="60" size="32" name="username" id="register-name" value="{{$username}}" >
	</div>
	<div id="register-name-end" ></div>


	<div id="register-email-wrapper"  class="field input" >
		<label for="register-email" id="label-register-email" >{{$addrlabel}}</label>
		<input type="text" maxlength="60" size="32" name="email" id="register-email" value="{{$email}}" >
	</div>
	<div id="register-email-end" ></div>
	<br /><br />

	<div id="register-nickname-wrapper" class="field input" >
		<label for="register-nickname" id="label-register-nickname" >{{$nicklabel}}</label>
		<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" value="{{$nickname}}" >
	</div>
	<div id="register-nickname-end" ></div>

	<div class="register-explain-wrapper">
	<p id="register-nickname-desc" >{{$nickdesc}}</p>
	</div>

	{{$publish}}

	<br />
<!--	<br><br>
	<div class="agreement">
	By clicking '{{$regbutt}}' you are agreeing to the latest <a href="tos.html" title="{{$tostitle}}" id="terms-of-service-link" >{{$toslink}}</a> and <a href="privacy.html" title="{{$privacytitle}}" id="privacy-link" >{{$privacylink}}</a>
	</div>-->
	<br><br>

	<div id="register-submit-wrapper">
		<input type="submit" name="submit" id="register-submit-button" value="{{$regbutt}}" />
	</div>
	<div id="register-submit-end" ></div>
</form>

{{$license}}

</div>
