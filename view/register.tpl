<h3>$regtitle</h3>

<form action="register" method="post" id="register-form">

	<input type="hidden" name="photo" value="$photo" />

	$registertext

	<p id="register-realpeople">$realpeople</p>

	<p id="register-fill-desc">$fillwith</p>
	<p id="register-fill-ext">$fillext</p>

	<div id="register-openid-wrapper" >
			$oidhtml
	</div>
	<div id="register-openid-end" ></div>

{{ if $invitations }}

	<p id="register-invite-desc">$invite_desc</p>
	<div id="register-invite-wrapper" >
		<label for="register-invite" id="label-register-invite" >$invite_label</label>
		<input type="text" maxlength="60" size="32" name="invite_id" id="register-invite" value="$invite_id" >
	</div>
	<div id="register-name-end" ></div>

{{ endif }}


	<div id="register-name-wrapper" >
		<label for="register-name" id="label-register-name" >$namelabel</label>
		<input type="text" maxlength="60" size="32" name="username" id="register-name" value="$username" >
	</div>
	<div id="register-name-end" ></div>


	<div id="register-email-wrapper" >
		<label for="register-email" id="label-register-email" >$addrlabel</label>
		<input type="text" maxlength="60" size="32" name="email" id="register-email" value="$email" >
	</div>
	<div id="register-email-end" ></div>

	<p id="register-nickname-desc" >$nickdesc</p>

	<div id="register-nickname-wrapper" >
		<label for="register-nickname" id="label-register-nickname" >$nicklabel</label>
		<input type="text" maxlength="60" size="32" name="nickname" id="register-nickname" value="$nickname" ><div id="register-sitename">@$sitename</div>
	</div>
	<div id="register-nickname-end" ></div>

	$publish

	<div id="register-submit-wrapper">
		<input type="submit" name="submit" id="register-submit-button" value="$regbutt" />
	</div>
	<div id="register-submit-end" ></div>
</form>

$license


