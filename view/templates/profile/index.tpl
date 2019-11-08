<div id="profile-page" class="generic-page-wrapper">
    {{include file="section_title.tpl"}}

    {{* The link to edit the profile*}}
{{if $is_owner}}
	<div id="profile-edit-links">
		<ul class="nav nav-pills preferences">
			<li class="pull-right">
				<a class="btn btn-link btn-sm" type="button" id="profile-edit-link" href="{{$edit_link.url}}" title="{{$edit_link.title}}">
					<i class="fa fa-pencil-square-o" aria-hidden="true"></i>&nbsp;{{$edit_link.label}}
				</a>
			</li>
		{{if count($view_as_contacts)}}
			<li class="pull-right">
				<form action="{{$query_string}}" method="get">
					<button type="submit" class="btn btn-sm">{{$view_as}}</button>
					<select name="viewas" class="input-sm">
						<option value="0">Yourself</option>
				{{foreach $view_as_contacts as $contact}}
						<option value="{{$contact.id}}"{{if $contact.id == $view_as_contact_id}} selected{{/if}}>{{$contact.name}}</option>
				{{/foreach}}
					</select>

				</form>
			</li>
		{{/if}}
		</ul>
		<div class="clear"></div>
	</div>
{{/if}}

    {{* Frio does split the profile information in "standard" and "advanced". This is the tab menu for switching between this modes *}}
    {{if count($custom_fields)}}
		<ul id="profile-menu" class="nav nav-tabs" role="tablist">
			<li role="presentation" class="active">
				<a href="{{$query_string}}#profile-content-standard" aria-controls="profile-content-standard" role="tab" data-toggle="tab">{{$basic}}</a>
			</li>
			<li role="presentation">
				<a href="{{$query_string}}#profile-content-advanced" aria-controls="profile-content-advanced" role="tab" data-toggle="tab">{{$advanced}}</a>
			</li>
		</ul>
    {{/if}}

	<div class="tab-content">
		<div role="tabpanel" class="tab-pane active" id="profile-content-standard">
			<dl id="{{$basic_fields.fullname.id}}" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$basic_fields.fullname.class|default:'aprofile'}}">
				<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.fullname.label}}</dt>
				<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.fullname.value}}</dd>
			</dl>

            {{if $basic_fields.membersince}}
				<dl id="aprofile-membersince" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$basic_fields.membersince.class|default:'aprofile'}}">
					<hr class="profile-separator">
					<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.membersince.label}}</dt>
					<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.membersince.value}}</dd>
				</dl>
            {{/if}}

            {{if $basic_fields.birthday}}
				<dl id="aprofile-birthday" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$basic_fields.birthday.class|default:'aprofile'}}">
					<hr class="profile-separator">
					<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.birthday.label}}</dt>
					<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.birthday.value}}</dd>
				</dl>
            {{/if}}

            {{if $basic_fields.age}}
				<dl id="aprofile-age" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$basic_fields.age.class|default:'aprofile'}}">
					<hr class="profile-separator">
					<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.age.label}}</dt>
					<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.age.value}}</dd>
				</dl>
            {{/if}}

            {{if $basic_fields.location}}
				<dl id="aprofile-location" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$basic_fields.location.class|default:'aprofile'}}">
					<hr class="profile-separator">
					<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.location.label}}</dt>
					<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.location.value}}</dd>
				</dl>
            {{/if}}

            {{if $basic_fields.homepage}}
				<dl id="aprofile-homepage" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$basic_fields.homepage.class|default:'aprofile'}}">
					<hr class="profile-separator">
					<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.homepage.label}}</dt>
					<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.homepage.value nofilter}}</dd>
				</dl>
            {{/if}}

            {{if $basic_fields.xmpp}}
				<dl id="aprofile-xmpp" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$basic_fields.xmpp.class|default:'aprofile'}}">
					<hr class="profile-separator">
					<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.xmpp.label}}</dt>
					<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$basic_fields.xmpp.value nofilter}}</dd>
				</dl>
            {{/if}}

            {{if $basic_fields.pub_keywords}}
				<dl id="aprofile-tags" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$basic_fields.pub_keywords.class|default:'aprofile'}}">
					<hr class="profile-separator">
					<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$basic_fields.pub_keywords.label}}</dt>
					<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">
                        {{foreach $basic_fields.pub_keywords.value as $tag}}
							<a href="{{$tag.url}}" class="tag label btn-info sm">{{$tag.label}} <i class="fa fa-bolt" aria-hidden="true"></i></a>
                        {{/foreach}}
					</dd>
				</dl>
            {{/if}}
		</div>

        {{if count($custom_fields)}}
			<div role="tabpanel" class="tab-pane advanced" id="profile-content-advanced">
                {{foreach $custom_fields as $custom_field}}
					<dl id="{{$custom_field.id}}" class="col-lg-12 col-md-12 col-sm-12 col-xs-12 {{$custom_field.class|default:'aprofile'}}">
						<hr class="profile-separator">
						<dt class="col-lg-4 col-md-4 col-sm-4 col-xs-12 profile-label-name text-muted">{{$custom_field.label}}</dt>
						<dd class="col-lg-8 col-md-8 col-sm-8 col-xs-12 profile-entry">{{$custom_field.value nofilter}}</dd>
					</dl>
                {{/foreach}}
			</div>
        {{/if}}
	</div>
</div>
