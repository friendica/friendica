{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div id="delegation" class="generic-page-wrapper">
	<h2>{{$l10n.header}}</h2>

{{if !$is_child_user}}
	<h3>{{$l10n.account_header}}</h3>
	<div id="add-account-desc" class="add-account-desc"><p>{{$l10n.account_desc}}</p></div>
	<p><a href="register">{{$l10n.add_account}}</a></p>
{{/if}}

{{if $parent_user}}
	<h3>{{$l10n.parent_header}}</h3>
	<div id="delegate-parent-desc" class="delegate-parent-desc"><p>{{$l10n.parent_desc}}</p></div>
	<div id="delegate-parent" class="delegate-parent">
		<form action="settings/delegation" method="post">
			<input type="hidden" name='form_security_token' value="{{$form_security_token}}">
            {{include file="field_select.tpl" field=$parent_user}}
            {{include file="field_password.tpl" field=$parent_password}}
			<div class="submit">
				<button type="submit" class="btn btn-primary" name="delegate" value="{{$l10n.submit}}">{{$l10n.submit}}</button>
			</div>
		</form>
	</div>
{{/if}}

	<h3>{{$l10n.delegates_header}}</h3>

	<div id="delegate-desc" class="delegate-desc"><p>{{$l10n.desc}}</p></div>

	<h4>{{$l10n.head_delegates}}</h4>
{{if $delegates}}
    {{foreach $delegates as $delegate}}
	<div class="contact-block-div">
		<a class="contact-block-link" href="settings/delegation/remove/{{$delegate.uid}}">
			<img class="contact-block-img" src="photo/thumb/{{$delegate.uid}}" title="{{$delegate.username}} ({{$delegate.nickname}})">
		</a>
	</div>
    {{/foreach}}
	<div class="clear"></div>
{{else}}
	<p>{{$l10n.none}}</p>
{{/if}}

	<h4>{{$l10n.head_potentials}}</h4>
{{if $potentials}}
    {{foreach $potentials as $potential}}
	<div class="contact-block-div">
		<a class="contact-block-link" href="settings/delegation/add/{{$potential.uid}}">
			<img class="contact-block-img" src="photo/thumb/{{$potential.uid}}" title="{{$potential.username}} ({{$potential.nickname}})">
		</a>
	</div>
    {{/foreach}}
	<div class="clear"></div>
{{else}}
	<p>{{$l10n.none}}</p>
{{/if}}
</div>
