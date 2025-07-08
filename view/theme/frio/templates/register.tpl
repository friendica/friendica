{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="generic-page-wrapper">

	{{* Display system messages *}}
	{{if $notices}}
		{{foreach $notices as $notice}}
			<div class="alert alert-warning" role="alert">{{$notice}}</div>
		{{/foreach}}
	{{/if}}

	<form action="register" method="post" id="register-form">

		<input type="hidden" name="photo" value="{{$photo}}" />
		<input type="hidden" name="form_security_token" value="{{$form_security_token}}">

		<h2 class="heading">{{$regtitle}}</h2>

		{{if $registertext != ""}}<div class="error-message">{{$registertext nofilter}}</div>{{/if}}

		{{if $explicit_content}} <p id="register-explicit-content">{{$explicit_content_note}}</p> {{/if}}

		{{if $oidlabel}}
		<h3>{{$openid_title}}</h3>

		<div id="register-openid-wrapper" class="form-group">
			<span class="help-block" id="openid_url_tip">{{$fillwith}}&nbsp;{{$fillext}}</span>
			<input type="text" placeholder="{{$oidlabel}}"maxlength="60" name="openid_url" class="openid form-control" id="register-openid" value="{{$openid}}">
		</div>
		<div id="register-openid-end"></div>
		{{/if}}

		<h3>Normal registration</h3>

		{{if !$additional}}
			<div id="register-email-wrapper" class="form-group">
				<input type="email" placeholder="{{$addrlabel}}" maxlength="60" name="field1" id="register-email" class="form-control" value="{{$email}}" required>
				<small class="help-block">{{$addrdesc}}</small>
			</div>
			<div id="register-email-end"></div>
		{{/if}}

		{{if $invitations}}
		<div id="register-invite-wrapper" class="form-group">
			<label for="register-invite" id="label-register-invite">{{$invite_label}}</label>
			<input type="text" maxlength="60" name="invite_id" id="register-invite" class="form-control" value="{{$invite_id}}">
			<span class="help-block" id="invite_id_tip">{{$invite_desc nofilter}}</span>
		</div>
		<div id="register-name-end"></div>
		{{/if}}

		<div id="register-name-wrapper" class="form-group">
			<input type="text" placeholder="{{$namelabel}}"maxlength="60" name="username" id="register-name" class="form-control" value="{{$username}}" required>
			<small class="help-block" id="name_tip">{{$namedesc nofilter}}</small>
		</div>
		<div id="register-name-end"></div>


		{{if $ask_password}}
		{{include file="field_password.tpl" field=$password1}}
		{{include file="field_password.tpl" field=$password2}}
		{{/if}}

		<div id="register-nickname-wrapper" class="form-group">
			<input type="text" placeholder="{{$nicklabel}}" maxlength="60"  name="nickname" id="register-nickname" class="form-control" value="{{$nickname}}" required>
			<small class="help-block" id="nickname_tip">
				{{$nickdesc nofilter}}<br/>
				{{$nickdesc2 nofilter}}
			</small>
		</div>
		<div id="register-nickname-end"></div>

		{{if $additional}}
			<div id="register-type-wrapper" class="form-group">
				{{include file="field_select.tpl" field=$acct_type}}
			</div>
			<div id="register-type-end"></div>
			{{assign var="label" value="true"}}
			{{include file="field_password.tpl" field=$parent_password label=false}}
		{{/if}}

		<input type="input" id=tarpit" name="email" style="display: none;" placeholder="Don't enter anything here"/>

		{{if $permonly}}
		{{include file="field_textarea.tpl" field=$permonlybox}}
		{{/if}}

		<hr>

		{{if $showtoslink}}
		<p><a href="{{$baseurl}}/tos">{{$tostext}}</a></p>
		{{/if}}
		{{if $showprivstatement}}
		<h3>{{$privstatement.0}}</h3>
		{{for $i=1 to 3}}
		<p>{{$privstatement[$i] nofilter}}</p>
		{{/for}}
		{{/if}}

		{{$publish nofilter}}

		<div id="register-submit-wrapper">
			<button type="submit" name="submit" id="register-submit-button" class="btn btn-primary" value="{{$regbutt}}">{{$regbutt}}</button>
		</div>
		<div id="register-submit-end" class="clear"></div>

		{{if !$additional}}
		  <hr>
			<h3>{{$importh}}</h3>
			<p>{{$importdesc}}</p>
			<div id ="import-profile">
				<a class="btn btn-secondary" href="user/import">{{$importt}}</a>
			</div>
		{{/if}}
	</form>
</div>
