
<h1>{{$ptitle}}</h1>

{{$nickname_block}}

<form action="settings" id="settings-form" method="post" autocomplete="off" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

<h3 class="settings-heading">{{$h_pass}}</h3>

{{include file="field_password.tpl" field=$password1}}
{{include file="field_password.tpl" field=$password2}}
{{include file="field_password.tpl" field=$password3}}

{{if $oid_enable}}
{{include file="field_input.tpl" field=$openid}}
{{/if}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>


<h3 class="settings-heading">{{$h_basic}}</h3>

{{include file="field_input.tpl" field=$username}}
{{include file="field_input.tpl" field=$email}}
{{include file="field_password.tpl" field=$password4}}
{{include file="field_custom.tpl" field=$timezone}}
{{include file="field_select.tpl" field=$language}}
{{include file="field_input.tpl" field=$defloc}}
{{include file="field_checkbox.tpl" field=$allowloc}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>


<h3 class="settings-heading">{{$h_prv}}</h3>


<input type="hidden" name="visibility" value="{{$visibility}}" />

{{include file="field_input.tpl" field=$maxreq}}

{{$profile_in_dir}}

{{$profile_in_net_dir}}

{{$hide_friends}}

{{$hide_wall}}

{{$blockwall}}

{{$blocktags}}

{{$suggestme}}

{{$unkmail}}


{{include file="field_input.tpl" field=$cntunkmail}}

{{include file="field_input.tpl" field=$expire.days}}


<div class="field input">
	<span class="field_help"><a href="#advanced-expire-popup" id="advanced-expire" class='popupbox' title="{{$expire.advanced}}">{{$expire.label}}</a></span>
	<div style="display: none;">
		<div id="advanced-expire-popup" style="width:auto;height:auto;overflow:auto;">
			<h3>{{$expire.advanced}}</h3>
			{{include file="field_yesno.tpl" field=$expire.items}}
			{{include file="field_yesno.tpl" field=$expire.notes}}
			{{include file="field_yesno.tpl" field=$expire.starred}}
			{{include file="field_yesno.tpl" field=$expire.network_only}}
		</div>
	</div>

</div>


<div id="settings-perms-wrapper" class="field">
<label for="settings-default-perms">{{$settings_perms}}</label><br/>
<div id="settings-default-perms" class="settings-default-perms" >
{{*<!--	<a href="#settings-jot-acl-wrapper" id="settings-default-perms-menu" class='popupbox'>{{$permissions}} {{$permdesc}}</a>
	<div id="settings-default-perms-menu-end"></div>

	<div id="settings-default-perms-select" style="display: none; margin-bottom: 20px" >

	<div style="display: none;">-->*}}
		<div id="settings-jot-acl-wrapper" style="width:auto;height:auto;overflow:auto;margin-bottom: 20px">
			{{*<!--{{$aclselect}}-->*}}
			{{include file="acl_html_selector.tpl"}}
		</div>
{{*<!--	</div>

	</div>-->*}}
</div>
</div>
<br/>
<div id="settings-default-perms-end"></div>

{{$group_select}}


<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>



<h3 class="settings-heading">{{$h_not}}</h3>
<div id="settings-notifications">

<div id="settings-activity-desc">{{$activity_options}}</div>

{{include file="field_checkbox.tpl" field=$post_newfriend}}
{{include file="field_checkbox.tpl" field=$post_joingroup}}
{{include file="field_checkbox.tpl" field=$post_profilechange}}


<div id="settings-notify-desc">{{$lbl_not}}</div>

<div class="group">
{{include file="field_intcheckbox.tpl" field=$notify1}}
{{include file="field_intcheckbox.tpl" field=$notify2}}
{{include file="field_intcheckbox.tpl" field=$notify3}}
{{include file="field_intcheckbox.tpl" field=$notify4}}
{{include file="field_intcheckbox.tpl" field=$notify5}}
{{include file="field_intcheckbox.tpl" field=$notify6}}
{{include file="field_intcheckbox.tpl" field=$notify7}}
{{include file="field_intcheckbox.tpl" field=$notify8}}
</div>

{{include file="field_checkbox.tpl" field=$email_textonly}}

</div>

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>


<h3 class="settings-heading">{{$h_advn}}</h3>
<div id="settings-pagetype-desc">{{$h_descadvn}}</div>

{{$pagetype}}

<div class="settings-submit-wrapper" >
<input type="submit" name="submit" class="settings-submit" value="{{$submit}}" />
</div>


