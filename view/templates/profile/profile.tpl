{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{if $view_as_contact_alert}}
<div class="alert alert-info" role="alert">
	{{$view_as_contact_alert nofilter}}
</div>
{{/if}}
<div id="profile-page" class="generic-page-wrapper">
    {{include file="section_title.tpl"}}

    {{* The link to edit the profile*}}
{{if $is_owner}}
	<div id="profile-edit-links">
		<ul class="nav nav-pills preferences">
			<li>
				<a class="btn btn-primary" type="button" id="profile-viewas-link" href="{{$viewas_link.url}}">
					<i class="fa fa-eye" aria-hidden="true"></i>&nbsp;{{$viewas_link.label}}
				</a>
			</li>
			<li>
				<a class="btn btn-primary" type="button" id="profile-edit-link" href="{{$edit_link.url}}">
					<i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;{{$edit_link.label}}
				</a>
			</li>
		</ul>
	</div>
{{/if}}
	<dl id="{{$basic_fields.fullname.id}}" class="row {{$basic_fields.fullname.class|default:'aprofile'}}">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.fullname.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.fullname.value}}</dd>
	</dl>

{{if $basic_fields.membersince}}
	<dl id="aprofile-membersince" class="row {{$basic_fields.membersince.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.membersince.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.membersince.value}}</dd>
	</dl>
{{/if}}

{{if $basic_fields.birthday}}
	<dl id="aprofile-birthday" class="row {{$basic_fields.birthday.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.birthday.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.birthday.value}}</dd>
	</dl>
{{/if}}

{{if $basic_fields.age}}
	<dl id="aprofile-age" class="row {{$basic_fields.age.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.age.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.age.value}}</dd>
	</dl>
{{/if}}

{{if $basic_fields.location}}
	<dl id="aprofile-location" class="row {{$basic_fields.location.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.location.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.location.value}}</dd>
	</dl>
{{/if}}

{{if $basic_fields.homepage}}
	<dl id="aprofile-homepage" class="row {{$basic_fields.homepage.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.homepage.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.homepage.value nofilter}}{{if $profile.homepage_verified}} <span title="{{$homepage_verified}}">✔</span>{{/if}}</dd>
	</dl>
{{/if}}

{{if $basic_fields.xmpp}}
	<dl id="aprofile-xmpp" class="row {{$basic_fields.xmpp.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.xmpp.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.xmpp.value nofilter}}</dd>
	</dl>
{{/if}}

{{if $basic_fields.matrix}}
	<dl id="aprofile-matrix" class="row {{$basic_fields.matrix.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.matrix.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.matrix.value nofilter}}</dd>
	</dl>
{{/if}}

{{if $basic_fields.pub_keywords}}
	<dl id="aprofile-tags" class="row {{$basic_fields.pub_keywords.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.pub_keywords.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">
            {{foreach $basic_fields.pub_keywords.value as $tag}}
				<a href="{{$tag.url}}" class="tag label btn-info sm">{{$tag.label}} <i class="fa fa-bolt" aria-hidden="true"></i></a>
            {{/foreach}}
		</dd>
	</dl>
{{/if}}

{{foreach $custom_fields as $custom_field}}
	<dl id="{{$custom_field.id}}" class="row {{$custom_field.class|default:'aprofile'}}">
		<hr class="profile-separator">
		<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$custom_field.label}}</dt>
		<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$custom_field.value nofilter}}</dd>
	</dl>
{{/foreach}}
</div>
{{if $is_owner}}
<form action="{{$query_string}}" method="get" id="viewas" class="panel panel-default form-inline">
	<fieldset class="panel-body">
		<label for="viewas-select">{{$view_as}}</label>
		<select name="viewas" id="viewas-select" class="form-control">
			<option value="0">{{$yourself}}</option>
			{{foreach $view_as_contacts as $contact}}
				<option value="{{$contact.id}}"{{if $contact.id == $view_as_contact_id}} selected{{/if}}>{{$contact.name}}</option>
			{{/foreach}}
		</select>
		<button type="submit" class="btn btn-primary">{{$submit}}</button>
	</fieldset>
</form>
{{/if}}
