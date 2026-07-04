{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
<div class="vcard h-card widget">

	<div id="profile-photo-wrapper">
		{{if $url}}
			<a href="{{$url}}">
				<img class="photo u-photo" src="{{$photo}}" alt="{{$contact.name}}" />
			</a>
		{{else}}
			<img class="photo u-photo" src="{{$photo}}" alt="{{$contact.name}}" />
		{{/if}}
	</div>

	{{* The short information which will appended to the second navbar by scrollspy *}}
	<div id="vcard-short-info-wrapper" style="display: none;">
		<div id="vcard-short-info" class="media" style="display: none">
			<div id="vcard-short-photo-wrapper" class="pull-left">
				<img class="media-object" src="{{$photo}}" alt="{{$contact.name}}" />
			</div>

			<div id="vcard-short-desc" class="media-body">
				<h4 class="media-heading" dir="auto">{{$contact.name}}</h4>
				{{if $contact.addr}}<div class="vcard-short-addr">{{$contact.addr}}</div>{{/if}}
			</div>
		</div>
	</div>

	<div class="panel-body">
		<div class="profile-header">
			<h3 class="fn p-name" dir="auto">{{$contact.name}}</h3>
			{{if $is_admin}}<span class="badge badge-admin"><i class="ri ri-medal-2-fill" aria-hidden="true"></i> {{$admin_title}}</span>{{/if}}
			{{if $is_mod}}<span class="badge badge-mod"><i class="ri ri-shield-user-line" aria-hidden="true"></i> {{$moderator_title}}</span>{{/if}}

			{{if $contact.addr}}<div class="p-addr">{{$contact.addr}}</div>{{/if}}

			{{if $account_type == 1}}
				{{$acct_icon = "ri-building-4-line"}}
			{{else if $account_type == 2}}
				{{$acct_icon = "ri-newspaper-line"}}
			{{else if $account_type == 3 && $page_flags == 2 || $account_type == 3 && $manually_approve == 0}}
				{{$acct_icon = "ri-team-line"}}
				{{$page_flags = 2}}
			{{else if $account_type == 3 && $page_flags == 5 || $account_type == 3 && $manually_approve == 1 && $private == 1}}
				{{$acct_icon = "ri-spy-line"}}
				{{$page_flags = 5}}
			{{else if $account_type == 3 && $page_flags == 6 || $account_type == 3 && $manually_approve == 1 && $private == 0}}
				{{$acct_icon = "ri-group-3-line"}}
				{{$page_flags = 6}}
			{{else if $account_type == 4}}
				{{$acct_icon = "ri-broadcast-line"}}
			{{else}}
				{{$acct_icon = ''}}
			{{/if}}
			{{if $account_type_name}}<div class="account-type" data-acct="{{$account_type}}" data-flag="{{$page_flags}}">(<i class="ri {{$acct_icon}}" aria-hidden="true"></i> {{$account_type_name}})</div>{{/if}}

			{{if $about}}<div class="title" dir="auto">{{$about nofilter}}</div>{{/if}}
		</div>

		<div id="profile-extra-links">
			<div id="dfrn-request-link-button">
				{{if $follow_link}}
					<a id="dfrn-request-link" class="btn btn-labeled btn-primary" href="{{$follow_link}}"">
						<span><i class="ri ri-user-add-line"></i></span>
						<span>{{$follow}}</span>
					</a>
				{{/if}}
				{{if $unfollow_link}}
					<a id="dfrn-request-link" class="btn btn-labeled btn-primary" href="{{$unfollow_link}}">
						<span><i class="ri ri-user-unfollow-line"></i></span>
						<span>{{$unfollow}}</span>
					</a>
				{{/if}}
			</div>
			{{if $wallmessage_link}}
				<div id="wallmessage-link-button">
					<button type="button" id="wallmessage-link" class="btn btn-labeled btn-primary" onclick="openWallMessage('{{$wallmessage_link}}')">
						<span><i class="ri ri-mail-line"></i></span>
						<span>{{$wallmessage}}</span>
					</button>
				</div>
			{{/if}}
			{{if $mention_link}}
				<div id="jotOpen" class="pull-right">
					<button type="button" id="mention-link" class="action-button btn btn-labeled btn-primary{{if !$always_open_compose}} modal-open{{/if}}" onclick="{{if $always_open_compose}}window.location.href='{{$mention_link}}'{{else}}openWallMessage('{{$mention_link}}'){{/if}}" aria-label="{{$mention}}" oncontextmenu="openWallMessage('compose/0')">
						<i class="ri ri-lg ri-pencil-line"></i>
						<span>{{$mention}}</span>
					</button>
				</div>
				<script>jotOpen.addEventListener("contextmenu", (e) => {e.preventDefault()});</script>
			{{/if}}
			{{if $showgroup_link}}
				<div id="show-group-button">
					<a type="button" id="show-group" class="btn btn-labeled btn-primary" href="{{$showgroup_link}}" title="{{$showgroup}}" aria-label="{{$showgroup}}">
						<span class=""><i class="ri ri-discuss-line"></i></span>
						<span class="">{{$showgroup}}</span>
					</a>
				</div>
			{{/if}}
		</div>

		<div class="clear"></div>

		{{if $contact.location}}
		<div class="location detail">
			<span class="location-label icon"><i class="ri ri-map-pin-line"></i></span>
			<span class="adr p-location">{{$contact.location}}</span>
		</div>
		{{/if}}

		{{if $contact.xmpp}}
		<div class="xmpp detail">
			<span class="xmpp-label icon"><i class="ri ri-chat-3-line"></i></span>
			<span class="xmpp-data"><a href="xmpp:{{$contact.xmpp}}" rel="me" target="_blank" rel="noopener noreferrer">{{include file="sub/punct_wrap.tpl" text=$contact.xmpp}}</a></span>
		</div>
		{{/if}}

		{{if $contact.matrix}}
		<div class="matrix detail">
			<span class="matrix-label icon"><i class="ri ri-grid-line"></i></span>
			<span class="matrix-data"><a href="matrix:{{$contact.matrix}}" rel="me" target="_blank" rel="noopener noreferrer">{{include file="sub/punct_wrap.tpl" text=$contact.matrix}}</a></span>
		</div>
		{{/if}}

		{{if $network_link}}
		<div class="network detail">
			{{if $network_svg}}
				<span class="network-label icon"><img class="network-svg" src="{{$network_svg}}" loading="lazy" aria-hidden="true"/></span>
			{{else}}
				<span class="network-label icon"><i class="ri ri-{{$network_avatar}}"></i></span>
			{{/if}}
			<span class="x-network">{{$network_link nofilter}}</span>
		</div>
		{{/if}}
	</div>
</div>
