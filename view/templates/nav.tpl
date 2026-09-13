{{*
  * Copyright (C) 2010-2026, the Friendica project
  * SPDX-FileCopyrightText: 2010-2026 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}

<header>
	<div id="site-location">{{$sitelocation}}</div>
	<div id="banner">{{$banner nofilter}}</div>
</header>
<nav id="topbar-first" role="menubar">
	<ul>
		<li class="mobile-aside-toggle" style="display:none;">
			<a href="#">
				<i class="icons icon-reorder"></i>
			</a>
		</li>

		{{if $nav.back}}
			<!-- Link back home to one's own instance, only visible to visitors -->
			<li role="menuitem" id="nav-back-link" class="nav-menu">
				<a accesskey="b" class="{{$nav.back.2}}" href="{{$nav.back.0}}" title="{{$nav.back.3}}">
					<span class="desktop-view">{{$nav.back.1}}</span>
					<i class="ri ri-xl ri-arrow-go-back-line ri-fw" aria-hidden="true"></i>
					<span class="sr-only">{{$nav.back.1}}</span>
				</a>
			</li>
		{{/if}}
		{{if $nav.network}}
			<li role="menuitem" id="nav-network-link" class="nav-menu {{$sel.network}}">
				<a accesskey="n" class="{{$nav.network.2}}" href="{{$nav.network.0}}" title="{{$nav.network.3}}">
					<span class="desktop-view">{{$nav.network.1}}</span>
					<i class="icon s22 icon-th mobile-view"><span class="sr-only">{{$nav.network.1}}</span></i>
					<span id="net-update" class="nav-notification"></span>
				</a>
			</li>
		{{/if}}
		{{if $nav.calendar}}
			<li role="menuitem" id="nav-calendar-link" class="nav-menu {{$sel.calendar}}">
				<a accesskey="e" class="{{$nav.calendar.2}} desktop-view" href="{{$nav.calendar.0}}" title="{{$nav.calendar.3}}">{{$nav.calendar.1}}</a>
				<a class="{{$nav.calendar.2}} mobile-view" href="{{$nav.calendar.0}}" title="{{$nav.calendar.3}}"><i class="icon s22 icon-calendar"></i></a>
			</li>
		{{/if}}
		{{if $nav.channel}}
			<!-- Note: This is currently never displayed -->
			<li role="menuitem" id="nav-channel-link" class="nav-menu {{$sel.channel}}">
				<a accesskey="l" class="{{$nav.channel.2}} desktop-view" href="{{$nav.channel.0}}" title="{{$nav.channel.3}}">{{$nav.channel.1}}</a>
				<a class="{{$nav.channel.2}} mobile-view" href="{{$nav.channel.0}}" title="{{$nav.channel.3}}"><i class="icon s22 icon-bullseye"></i></a>
			</li>
		{{/if}}
		{{if $nav.community}}
			<li role="menuitem" id="nav-community-link" class="nav-menu {{$sel.community}}">
				<a accesskey="c" class="{{$nav.community.2}} desktop-view" href="{{$nav.community.0}}" title="{{$nav.community.3}}">{{$nav.community.1}}</a>
				<a class="{{$nav.community.2}} mobile-view" href="{{$nav.community.0}}" title="{{$nav.community.3}}"><i class="icon s22 icon-bullseye"></i></a>
			</li>
		{{/if}}

		{{if $profile_link}}
			<li role="menuitem" id="nav-my-profile-link" class="nav-menu {{$sel.my_profile}}">
				<a accesskey="p" class="" href="{{$profile_link}}" title="{{$profile_link_title}}">
					<span class="desktop-view">{{$profile_link_title}}</span>
					<i class="ri ri-xl ri-user-line ri-fw mobile-view" aria-hidden="true"></i>
					<span id="my-profile-update" class="nav-notification"></span>
				</a>
			</li>
		{{/if}}

		<li role="menu" aria-haspopup="true" id="nav-site-linkmenu" class="nav-menu-icon"><a><span class="icon s22 icon-question"><span class="sr-only">{{$nav.help.3}}</span></span></a>
			<ul id="nav-site-menu" class="menu-popup">
				{{if $nav.help}} <li role="menuitem"><a class="{{$nav.help.2}}" href="{{$nav.help.0}}" title="{{$nav.help.3}}">{{$nav.help.1}}</a></li>{{/if}}
				<li role="menuitem"><a class="{{$nav.about.2}}" href="{{$nav.about.0}}" title="{{$nav.about.3}}">{{$nav.about.1}}</a></li>
				{{if $nav.tos}}<li role="menuitem"><a class="{{$nav.tos.2}}" href="{{$nav.tos.0}}" title="{{$nav.tos.3}}">{{$nav.tos.1}}</a></li>{{/if}}
				<li role="menuitem"><a class="{{$nav.directory.2}}" href="{{$nav.directory.0}}" title="{{$nav.directory.3}}">{{$nav.directory.1}}</a></li>
			</ul>
		</li>

		{{if $nav.messages}}
			<li role="menu" aria-haspopup="true" id="nav-messages-linkmenu" class="nav-menu-icon">
				<a href="{{$nav.messages.0}}" title="{{$nav.messages.1}}">
					<span class="icon s22 icon-envelope"><span class="sr-only">{{$nav.messages.1}}</span></span>
					<span id="mail-update" class="nav-notification"></span>
				</a>
			</li>
		{{/if}}


		{{if $nav.notifications}}
			<li role="menu" aria-haspopup="true" id="nav-notifications-linkmenu" class="nav-menu-icon">
				<a title="{{$nav.notifications.1}}">
					<span class="icon s22 icon-bell tilted-icon"><span class="sr-only">{{$nav.notifications.1}}</span></span>
					<span id="notification-update" class="nav-notification"></span>
				</a>
				<ul id="nav-notifications-menu" class="menu-popup">
					<li role="menuitem" id="nav-notifications-mark-all"><a onclick="notificationMarkAll(); return false;">{{$nav.notifications.mark.1}}</a></li>
					<li role="menuitem" id="nav-notifications-see-all"><a href="{{$nav.notifications.all.0}}">{{$nav.notifications.all.1}}</a></li>
					<li role="menuitem" class="empty">{{$emptynotifications}}</li>
				</ul>
			</li>
		{{/if}}

		{{if $userinfo}}
			<li role="menu" aria-haspopup="true" id="nav-user-linkmenu" class="nav-menu" tabindex="0">
				<a accesskey="u" title="{{$sitelocation}}"><img src="{{$userinfo.icon}}" alt="{{$userinfo.name}}"><span id="nav-user-linklabel">{{$userinfo.name}}</span><span id="intro-update" class="nav-notification"></span></a>
				<ul id="nav-user-menu" class="menu-popup">
					{{if $nav.delegation}}<li role="menuitem"><a class="{{$nav.delegation.2}}" href="{{$nav.delegation.0}}" title="{{$nav.delegation.3}}" up-follow="false">{{$nav.delegation.1}}</a></li>{{/if}}
					<li role="menuitem"> <a  class="{{$nav.search.2}}" href="{{$nav.search.0}}" title="{{$nav.search.3}}">{{$nav.search.1}}</a> </li>
					{{if $nav.introductions}}<li role="menuitem"><a class="{{$nav.introductions.2}}" href="{{$nav.introductions.0}}" title="{{$nav.introductions.3}}">{{$nav.introductions.1}}<span id="intro-update-li" class="nav-notification"></span></a></li>{{/if}}
					{{if $nav.contacts}}<li role="menuitem"><a class="{{$nav.contacts.2}}" href="{{$nav.contacts.0}}" title="{{$nav.contacts.3}}">{{$nav.contacts.1}}</a></li>{{/if}}
					{{if $nav.messages}}<li role="menuitem"><a class="{{$nav.messages.2}}" href="{{$nav.messages.0}}" title="{{$nav.messages.3}}">{{$nav.messages.1}} <span id="mail-update-li" class="nav-notification"></span></a></li>{{/if}}
					{{* Links to Profile: *}}
					{{foreach $nav.usermenu as $usermenu}}
					<li role="menuitem"><a role="menuitem" class="{{$usermenu.2}}" href="{{$usermenu.0}}" title="{{$usermenu.3}}">{{$usermenu.1}}</a></li>
					{{/foreach}}
					{{if $nav.settings}}<li role="menuitem"><a class="{{$nav.settings.2}}" href="{{$nav.settings.0}}" title="{{$nav.settings.3}}">{{$nav.settings.1}}</a></li>{{/if}}
					{{if $nav.admin}}
					<li role="menuitem">
						<a accesskey="a" class="{{$nav.admin.2}}" href="{{$nav.admin.0}}" title="{{$nav.admin.3}}">{{$nav.admin.1}}</a>
					</li>
					{{/if}}
					{{if $nav.moderation}}
						<li role="menuitem">
							<a accesskey="m" class="{{$nav.moderation.2}}" href="{{$nav.moderation.0}}" title="{{$nav.moderation.3}}">{{$nav.moderation.1}}</a>
						</li>
					{{/if}}
					{{if $nav.logout}}<li role="menuitem"><a class="menu-sep {{$nav.logout.2}}" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" up-follow="false">{{$nav.logout.1}}</a></li>{{/if}}
				</ul>
			</li>
		{{/if}}

		{{if $nav.login}}
			<li role="menuitem" id="nav-login-link" class="nav-menu">
				<a class="{{$nav.login.2}}" href="{{$nav.login.0}}" title="{{$nav.login.3}}">{{$nav.login.1}}</a>
			</li>
		{{/if}}
		{{if $nav.logout}}
			<li role="menuitem" id="nav-logout-link" class="nav-menu">
				<a class="{{$nav.logout.2}}" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" up-follow="false">{{$nav.logout.1}}</a>
			</li>
		{{/if}}

		{{if $nav.search}}
			<li role="search" id="nav-search-box">
				<form method="get" action="{{$nav.search.0}}">
					<input accesskey="s" id="nav-search-text" class="nav-menu-search" type="text" value="" name="q" placeholder=" {{$search_placeholder}}">
					<select name="search-option">
						<option value="fulltext">{{$nav.searchoption.0}}</option>
						<option value="tags">{{$nav.searchoption.1}}</option>
						<option value="contacts">{{$nav.searchoption.2}}</option>
						{{if $nav.searchoption.3}}<option value="groups">{{$nav.searchoption.3}}</option>{{/if}}
					</select>
				</form>
			</li>
		{{/if}}

		{{if $nav.admin}}
			<li role="menuitem" id="nav-admin-link" class="nav-menu">
				<a accesskey="a" class="{{$nav.admin.2}} icon-sliders" href="{{$nav.admin.0}}" title="{{$nav.admin.3}}"><span class="sr-only">{{$nav.admin.3}}</span></a>
			</li>
		{{/if}}

		{{if $nav.moderation}}
			<li role="menuitem" id="nav-moderation-link" class="nav-menu">
				<a accesskey="a" class="{{$nav.moderation.2}} icon-sliders" href="{{$nav.moderation.0}}" title="{{$nav.moderation.3}}"><span class="sr-only">{{$nav.moderation.3}}</span></a>
			</li>
		{{/if}}

		{{if $nav.apps}}
			<li role="menu" aria-haspopup="true" id="nav-apps-link" class="nav-menu {{$sel.apps}}">
				<a class=" {{$nav.apps.2}}" title="{{$nav.apps.3}}">{{$nav.apps.1}}</a>
				<ul id="nav-apps-menu" class="menu-popup">
					{{foreach $apps as $ap}}
					<li role="menuitem">{{$ap nofilter}}</li>
					{{/foreach}}
				</ul>
			</li>
		{{/if}}
	</ul>

</nav>
