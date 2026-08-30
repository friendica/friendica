{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="basic-content">
	<div class="hover-card-details">
		<div class="hover-card-header">
			<div class="hover-card-pic">
				<span class="image-wrapper medium">
					<a href="{{$profile.url}}" title="{{$profile.name}}"><img href="" class="left-align thumbnail" src="{{$profile.thumb}}" alt="{{$profile.name}}"></a>
				</span>
			</div>
			<div class="hover-card-content">
				<div class="profile-entry-name">
					<h4 class="left-align1"><a href="{{$profile.url}}">{{$profile.name}}</a></h4>
					{{if $profile.is_admin}}<span class="badge badge-admin"><i class="ri ri-medal-2-fill" aria-hidden="true"></i> {{$profile.admin_title}}</span>{{/if}}
					{{if $profile.is_mod}}<span class="badge badge-mod"><i class="ri ri-shield-user-line" aria-hidden="true"></i> {{$profile.moderator_title}}</span>{{/if}}

					{{if $profile.account_type_name}}
						{{if $profile.account_type == 4}}
							{{$acct_icon = "ri-broadcast-line"}}
						{{else if $profile.account_type == 3}}
							{{if $profile.private == 1}}
								{{$acct_icon = "ri-spy-line"}}
							{{else if $profile.manually_approve == 1}}
								{{$acct_icon = "ri-group-3-line"}}
							{{else}}
								{{$acct_icon = "ri-team-line"}}
							{{/if}}
						{{else if $profile.account_type == 2}}
								{{$acct_icon = "ri-newspaper-line"}}
						{{else if $profile.account_type == 1}}
								{{$acct_icon = "ri-building-4-line"}}
						{{else if $profile.account_type == 0 && $profile.manually_approve == 0}}
								{{$acct_icon = "ri-megaphone-line"}}
						{{else}}
							{{$acct_icon = ""}}
						{{/if}}
						<span><i class="{{$acct_icon}}" aria-hidden="true"></i> {{$profile.account_type_name}}</span>
					{{/if}}
				</div>
				<div class="profile-details">
					<span class="profile-addr">{{$profile.addr}}</span>
					{{if $profile.network_link}}<span class="profile-network">{{$profile.network_link nofilter}}</span>{{/if}}
				</div>

			</div>
		<div class="clearfix"></div>
			<div class="hover-card-actions">
				{{* here are the different actions like private message, delete and so on *}}
				{{* @todo we have two different photo menus one for contacts and one for items at the network stream. We currently use the contact photo menu, so the items options are missing We need to move them *}}
				{{if $profile.actions.pm}}
					<a class="btn btn-labeled btn-primary btn-sm add-to-modal" href="{{$profile.actions.pm.1}}">
						<i class="ri ri-mail-line" aria-hidden="true"></i>
						<span class="action-label">{{$profile.actions.pm.0}}</span>
					</a>
				{{/if}}

				{{if $profile.addr && !$profile.self}}
					<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.mention.1}}">
						<i class="ri ri-edit-box-line" aria-hidden="true"></i>
						<span class="action-label">{{$profile.actions.mention.0}}</span>
					</a>
				{{/if}}

				{{if $profile.actions.network}}
					<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.network.1}}">
							<i class="ri ri-{{($profile.contact_type==3) ? 'discuss-line' : 'cloud-line'}}" aria-hidden="true"></i>
							<span class="action-label">{{$profile.actions.network.0}}</span>
					</a>
				{{/if}}
				{{if $profile.actions.edit}}
					<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.edit.1}}">
						<i class="ri ri-user-line" aria-hidden="true"></i>
						 <span class="action-label">{{$profile.actions.edit.0}}</span>
					</a>
				{{/if}}
				{{if $profile.actions.follow}}
					<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.follow.1}}">
						<i class="ri ri-user-add-line" aria-hidden="true"></i>
						<span class="action-label">{{$profile.actions.follow.0}}</span>
					</a>
				 {{/if}}
				{{if $profile.actions.unfollow}}
					<a class="btn btn-labeled btn-primary btn-sm" href="{{$profile.actions.unfollow.1}}">
						<i class="ri ri-user-unfollow-line" aria-hidden="true"></i>
						<span class="action-label">{{$profile.actions.unfollow.0}}</span>
					</a>
				{{/if}}
			</div>
		</div>
	</div>

		<div class="clearfix"></div>

	</div>
</div>
{{if $profile.tags}}
	<div class="hover-card-footer tags">
    {{foreach $profile.tags as $tag}}
			<a href="{{$tag.url}}" class="tag label border border-default">{{$tag.label}}</a>
    {{/foreach}}
	</div>
{{/if}}
