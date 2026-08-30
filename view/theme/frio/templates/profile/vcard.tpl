{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="vcard h-card widget">

	<div id="profile-photo-wrapper">
		<a class="vcard-anchor" href="{{$picture_dest_url}}" style="position: relative;">
			<img class="photo u-photo" src="{{$profile.photo}}" alt="{{$profile.name}}" />
			{{if $is_owner }}
				<div id="change-profile-picture">{{$change_profile_picture_text}}</div>
			{{/if}}
		</a>
	</div>

	{{* The short information which will appended to the second navbar by scrollspy *}}
	<div id="vcard-short-info-wrapper" style="display: none;">
		<div id="vcard-short-info" class="media" style="display: none">
			<div id="vcard-short-photo-wrapper" class="pull-left">
				<img class="media-object" src="{{$profile.photo}}" alt="{{$profile.name}}"></a>
			</div>

			<div id="vcard-short-desc" class="media-body">
				<h4 class="media-heading">{{$profile.name}}</h4>
				{{if $profile.addr}}<div class="vcard-short-addr">{{$profile.addr}}</div>{{/if}}
			</div>
		</div>
	</div>

	<div class="panel-body">
		<div class="profile-header">
			<h3 class="fn p-name" dir="auto">{{$profile.name}}</h3>
			{{if $is_admin}}<span class="badge badge-admin"><i class="ri ri-medal-2-fill" aria-hidden="true"></i> {{$admin_title}}</span>{{/if}}
			{{if $is_mod}}<span class="badge badge-mod"><i class="ri ri-shield-user-line" aria-hidden="true"></i> {{$moderator_title}}</span>{{/if}}

			{{if $profile.addr}}<div class="p-addr">{{include file="sub/punct_wrap.tpl" text=$profile.addr}}</div>{{/if}}
			{{if $is_owner }}
				<div class="edit-profile-link-wrapper">
					<a class="btn btn-primary" href="{{$edit_profile_link.url}}">
						<i class="ri ri-pencil-line" aria-hidden="true"></i>
						{{$edit_profile_link.text}}
					</a>
				</div>
			{{/if}}

			{{if $profile.about}}<div class="title p-about" dir="auto">{{$profile.about nofilter}}</div>{{/if}}
			{{if $account_type == 1 }}
				{{$acct_icon = "ri-building-4-line"}}
			{{else if $account_type == 2}}
				{{$acct_icon = "ri-newspaper-line"}}
			{{else if $account_type == 3 && $page_flags == 2}}
				{{$acct_icon = "ri-team-line"}}
			{{else if $account_type == 3 && $page_flags == 6}}
				{{$acct_icon = "ri-group-3-line"}}
			{{else if $account_type == 3 && $page_flags == 5}}
				{{$acct_icon = "ri-spy-line"}}
			{{else if $account_type == 4}}
				{{$acct_icon = "ri-broadcast-line"}}
			{{else}}
				{{$acct_icon = ''}}
			{{/if}}
			{{if $account_type_name}}<div class="account-type" data-acct="{{$account_type}}" data-flag="{{$page_flags}}">(<i class="ri {{$acct_icon}}" aria-hidden="true"></i> {{$account_type_name}})</div>{{/if}}

		</div>

		{{if $follow_link || $unfollow_link || $wallmessage_link}}
			<div id="profile-extra-links">
				{{if $follow_link || $unfollow_link}}
					<div id="dfrn-request-link-button">
						{{if $unfollow_link}}
							<a id="dfrn-request-link" class="btn btn-labeled btn-primary" href="{{$unfollow_link}}">
								<span><i class="ri ri-user-unfollow-line"></i></span>
								<span>{{$unfollow}}</span>
							</a>
						{{else}}
							<a id="dfrn-request-link" class="btn btn-labeled btn-primary" href="{{$follow_link}}">
								<span><i class="ri ri-user-add-line"></i></span>
								<span>{{$follow}}</span>
							</a>
						{{/if}}
					</div>
				{{/if}}
				{{if $subscribe_feed_link}}
					<div id="subscribe-feed-link-button">
						<a id="subscribe-feed-link" class="btn btn-labeled btn-primary" href="{{$subscribe_feed_link}}" up-follow="false">
							<span><i class="ri ri-rss-line"></i></span>
							<span>{{$subscribe_feed}}</span>
						</a>
					</div>
				{{/if}}
				{{if $wallmessage_link}}
					<div id="wallmessage-link-button">
						<button type="button" id="wallmessage-link" class="btn btn-labeled btn-primary" onclick="openWallMessage('{{$wallmessage_link}}')">
							<span><i class="ri ri-mail-line"></i></span>
							<span>{{$wallmessage}}</span>
						</button>
					</div>
				{{/if}}
				{{if $profile.addr}}
					<div id="jotOpen" class="pull-right">
						<button type="button" id="mention-link" class="action-button btn btn-labeled btn-primary" onclick="{{if $always_open_compose}}window.location.href='{{$mention_url}}'{{else}}openWallMessage('{{$mention_url}}'){{/if}}">
							<i class="ri ri-lg ri-pencil-line"></i>
							<span>{{$mention_label}}</span>
						</button>
					</div>
				{{/if}}
				{{if $network_label}}
				{{* NOTE: This effectively links to the Contact's posts/conversations URL *}}
				{{* Despite the naming here this is not currently only used for groups but also other accounts *}}
					<div id="showgroup-button">
						<a id="showgroup" class="btn btn-labeled btn-primary" href="{{$network_url}}">
							<span><i class="ri {{$network_icon}}"></i></span>
							<span>{{$network_label}}</span>
						</a>
					</div>
				{{/if}}
			</div>
		{{/if}}

		<div class="clear"></div>

		{{if $location}}
			<div class="location detail">
				<span class="location-label icon"><i class="ri ri-map-pin-line" title="{{$location}}"></i></span>
				<span class="adr">
					{{if $profile.address}}<span class="street-address p-street-address">{{$profile.address nofilter}}</span>
					{{/if}}
					{{if $profile.location}}<span class="p-location">{{$profile.location}}</span>{{/if}}
				</span>
			</div>
		{{/if}}

		{{if $profile.xmpp}}
			<div class="xmpp">
				<span class="xmpp-label icon"><i class="ri ri-chat-3-line" title="{{$xmpp}}"></i></span>
				<span class="xmpp-data"><a href="xmpp:{{$profile.xmpp}}" rel="me" target="_blank" rel="noopener noreferrer">{{include file="sub/punct_wrap.tpl" text=$profile.xmpp}}</a></span>
			</div>
		{{/if}}

		{{if $profile.matrix}}
			<div class="matrix">
				<span class="matrix-label icon"><i class="ri ri-grid-line" title="{{$matrix}}"></i></span>
				<span class="matrix-data"><a href="matrix:{{$profile.matrix}}" rel="me" target="_blank" rel="noopener noreferrer">{{include file="sub/punct_wrap.tpl" text=$profile.matrix}}</a></span>
			</div>
		{{/if}}

		{{if $profile.upubkey}}<div class="key u-key" style="display:none;">{{$profile.upubkey}}</div>{{/if}}

		{{if $contacts}}<div class="contacts" style="display:none;">{{$contacts}}</div>{{/if}}

		{{if $updated}}<div class="updated" style="display:none;">{{$updated}}</div>{{/if}}

		{{if $homepage}}
			<div class="homepage detail">
				<span class="homepage-label icon"><i class="ri ri-external-link-line" title="{{$homepage}}"></i></span>
				<span class="homepage-url u-url"><a href="{{$profile.homepage}}" rel="me" target="_blank" rel="noopener noreferrer">{{include file="sub/punct_wrap.tpl" text=$profile.homepage}}</a>{{if $profile.homepage_verified}}
					<span title="{{$homepage_verified}}">✔</span>{{/if}}</span>
			</div>
		{{/if}}

		{{if $member_since}}
				<p class="member-since">
					<strong>{{$member_since.0}}</strong>
					<span>{{$member_since.1}}</span>
				</p>
		{{/if}}

		{{if $about}}<dl class="about" style="display:none;">
				<dt class="about-label">{{$about}}</dt>
				<dd class="x-network">{{$profile.about nofilter}}</dd>
		</dl>{{/if}}

		{{include file="diaspora_vcard.tpl"}}
	</div>
</div>

{{if $contact_block}}
	<nav class="widget" id="widget-contacts">
		{{$contact_block nofilter}}
	</nav>
{{/if}}
