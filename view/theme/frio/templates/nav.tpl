{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
{{* we have modified the navmenu (look at function frio_remote_nav() ) to have remote links. *}}
{{if $userinfo}}
	<header>
		{{* {{$langselector}} *}}

		<div id="site-location" aria-hidden="true">{{$sitelocation}}</div>
		<div id="banner" class="hidden-sm hidden-xs">
			{{* show on remote/visitor connections another logo which symbols that fact*}}
			{{if $nav.remote}}
				<a href="{{$baseurl}}" aria-hidden="true">
					<div id="remote-logo-img" aria-label="{{$home}}"></div>
				</a>
			{{else}}
				{{* #logo-img is the placeholder to insert a mask (friendica logo) into this div
				For Firefox we have to call the paths of the mask (look at the bottom of this file).
				Because for FF we need relative paths we apply them with js after the page is loaded (look at theme.js *}}
				<a href="{{$baseurl}}" aria-hidden="true">
					<div id="logo-img" aria-label="{{$home}}"></div>
				</a>
			{{/if}}
		</div>
	</header>
	<nav id="topbar-first" class="topbar" role="menubar">
		<div class="container">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 no-padding">
				<!-- div for navbar width-->
				<!-- Brand and toggle get grouped for better mobile display -->
				<nav class="topbar-nav">

					{{* Buttons for the mobile view *}}
					<button type="button" class="navbar-toggle offcanvas-right-toggle pull-right"
						aria-controls="offcanvasUsermenu" aria-haspopup="true">
						<span class="sr-only">Toggle navigation</span>
						<i class="fa fa-ellipsis-v fa-fw fa-lg" aria-hidden="true"></i>
					</button>
					<button type="button" class="navbar-toggle collapsed pull-right" data-toggle="collapse"
						data-target="#search-mobile" aria-expanded="false" aria-controls="search-mobile">
						<span class="sr-only">Toggle Search</span>
						<i class="fa fa-search fa-fw fa-lg" aria-hidden="true"></i>
					</button>
					<button type="button" class="navbar-toggle collapsed pull-left visible-sm visible-xs"
						data-toggle="offcanvas" data-target="aside" aria-haspopup="true">
						<span class="sr-only">Toggle navigation</span>
						<i class="fa fa-angle-double-right fa-fw fa-lg" aria-hidden="true"></i>
					</button>

					{{* Left section of the NavBar with navigation shortcuts/icons *}}
					<ul class="nav navbar-left">
						<li class="sr-only">
							<button class="sr-only" onclick="document.getElementById('content').scrollIntoView(); document.getElementById('content').focus();">{{$skip}}</button>
						</li>
						<li class="sr-only">
							<a class="sr-only" href="{{$baseurl}}">{{$home}}</a>
						</li>
						{{if $nav.network}}
							<li class="nav-segment">
								<a accesskey="n" class="nav-menu {{$sel.network}}" href="{{$nav.network.0}}"
									data-toggle="tooltip" aria-label="{{$nav.network.3}}" title="{{$nav.network.3}}"><i
										class="fa fa-lg fa-th fa-fw" aria-hidden="true"></i><span id="net-update"
										class="nav-network-badge badge nav-notification"></span></a>
							</li>
						{{/if}}

						{{if $nav.channel}}
							<li class="nav-segment">
								<a accesskey="l" class="nav-menu {{$sel.channel}}" href="{{$nav.channel.0}}"
									data-toggle="tooltip" aria-label="{{$nav.channel.3}}" title="{{$nav.channel.3}}"><i
										class="fa fa-lg fa-newspaper-o fa-fw" aria-hidden="true"></i></a>
							</li>
						{{/if}}

						{{if $nav.home}}
							<li class="nav-segment">
								<a accesskey="p" class="nav-menu {{$sel.home}}" href="{{$nav.home.0}}" data-toggle="tooltip"
									aria-label="{{$nav.home.3}}" title="{{$nav.home.3}}"><i class="fa fa-lg fa-home fa-fw"
										aria-hidden="true"></i><span id="home-update"
										class="nav-home-badge badge nav-notification"></span></a>
							</li>
						{{/if}}

						{{if $nav.community}}
							<li class="nav-segment">
								<a accesskey="c" class="nav-menu {{$sel.community}}" href="{{$nav.community.0}}"
									data-toggle="tooltip" aria-label="{{$nav.community.3}}" title="{{$nav.community.3}}"><i
										class="fa fa-lg fa-bullseye fa-fw" aria-hidden="true"></i></a>
							</li>
						{{/if}}

						{{if $nav.messages}}
							<li class="nav-segment hidden-xs">
								<a accesskey="m" id="nav-messages-link" href="{{$nav.messages.0}}" data-toggle="tooltip"
									aria-label="{{$nav.messages.1}}" title="{{$nav.messages.1}}"
									class="nav-menu {{$sel.messages}}"><i class="fa fa-envelope fa-lg fa-fw"
										aria-hidden="true"></i><span id="mail-update"
										class="nav-mail-badge badge nav-notification"></span></a>
							</li>
						{{/if}}

						{{if $nav.calendar}}
							<li class="nav-segment hidden-xs">
								<a accesskey="e" id="nav-calendar-link" href="{{$nav.calendar.0}}" data-toggle="tooltip"
									aria-label="{{$nav.calendar.1}}" title="{{$nav.calendar.1}}" class="nav-menu"><i
										class="fa fa-lg fa-calendar fa-fw"></i></a>
							</li>
						{{/if}}

						{{if $nav.contacts}}
							<li class="nav-segment hidden-xs">
								<a accesskey="k" id="nav-contacts-link" href="{{$nav.contacts.0}}" data-toggle="tooltip"
									aria-label="{{$nav.contacts.1}}" title="{{$nav.contacts.1}}"
									class="nav-menu {{$sel.contacts}} {{$nav.contacts.2}}"><i
										class="fa fa-users fa-lg fa-fw"></i></a>
							</li>
						{{/if}}

						{{* The notifications dropdown *}}
						{{if $nav.notifications}}
							<li id="nav-notification" class="nav-segment dropdown">
								<button id="nav-notifications-menu-btn" class="btn-link dropdown-toggle" data-toggle="dropdown"
									type="button" aria-haspopup="true" aria-expanded="false"
									aria-controls="nav-notifications-menu">
									<span id="notification-update" class="nav-notification-badge badge nav-notification"></span>
									<i class="fa fa-bell fa-lg" aria-label="{{$nav.notifications.1}}"></i>
								</button>
								{{* The notifications dropdown menu. There are two parts of menu. The second is at the bottom of this file. It is loaded via js. Look at nav-notifications-template *}}
								<ul id="nav-notifications-menu" class="dropdown-menu menu-popup" role="menu"
									aria-labelledby="nav-notifications-menu-btn">
									{{* the following list entry must have the id "nav-notifications-mark-all". Without it this isn't visible. ....strange behavior :-/ *}}
									<li id="nav-notifications-mark-all" class="dropdown-header">
										<div class="arrow"></div>
										{{$nav.notifications.1}}
										<div class="dropdown-header-link">
											<button role="menuitem" type="button" class="btn-link"
												onclick="notificationMarkAll();" data-toggle="tooltip"
												aria-label="{{$nav.notifications.mark.3}}"
												title="{{$nav.notifications.mark.3}}">{{$nav.notifications.mark.1}}</button>
										</div>

									</li>

									<li>
										<p role="menuitem" class="text-muted"><i>{{$emptynotifications}}</i></p>
									</li>
								</ul>
							</li>
						{{/if}}

					</ul>
				</nav>

				{{* This is the right part of the NavBar. It includes the search and the user menu *}}
				<div class="topbar-actions pull-right">
					<ul class="nav">

						{{* The search box *}}
						{{if $nav.search}}
							<li id="search-box" class="hidden-xs">
								<form class="navbar-form" role="search" method="get" action="{{$nav.search.0}}">
									<div class="form-group form-group-search">
										<input accesskey="s" id="nav-search-input-field" class="form-control form-search"
											type="text" name="q" data-toggle="tooltip" title="{{$search_hint}}"
											placeholder="{{$nav.search.1}}">
										<button class="btn btn-default btn-sm form-button-search"
											type="submit">{{$nav.search.1}}</button>
									</div>
								</form>
							</li>
						{{/if}}

						{{* The user dropdown menu *}}
						{{if $userinfo}}
							<li id="nav-user-linkmenu" class="dropdown account nav-menu hidden-xs">
								<button accesskey="u" id="main-menu" class="btn-link dropdown-toggle nav-avatar"
									data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false"
									aria-controls="nav-user-menu">
									<div aria-hidden="true" class="user-title pull-left hidden-xs hidden-sm hidden-md">
										<strong>{{$userinfo.name}}</strong><br>
										{{if $nav.remote}}<span class="truncate">{{$nav.remote}}</span>{{/if}}
									</div>

									<img id="avatar" src="{{$userinfo.icon}}" alt="{{$userinfo.name}}">
									<span class="caret"></span>
								</button>

								{{* The list of available usermenu links *}}
								<ul id="nav-user-menu" class="dropdown-menu pull-right menu-popup" role="menu"
									aria-labelledby="main-menu">
									{{if $nav.remote}}
										{{if $nav.sitename}}
											<li id="nav-sitename" role="menuitem">{{$nav.sitename}}</li>
											<li class="divider"><hr></li>
										{{/if}}
									{{/if}}
									{{foreach $nav.usermenu as $usermenu}}
										<li>
											<a role="menuitem" class="{{$usermenu.2}}" href="{{$usermenu.0}}"
												title="{{$usermenu.3}}">
												{{$usermenu.1}}
											</a>
										</li>
									{{/foreach}}
									<li class="divider"><hr></li>
									{{if $nav.notifications}}
										<li>
											<a role="menuitem" href="{{$nav.notifications.all.0}}" title="{{$nav.notifications.1}}">
												<i class="fa fa-bell fa-fw" aria-hidden="true"></i>
												{{$nav.notifications.1}}
											</a>
										</li>
									{{/if}}
									{{if $nav.messages}}
										<li>
											<a role="menuitem"
												class="nav-commlink {{$nav.messages.2}} {{$sel.messages}}"
												href="{{$nav.messages.0}}" title="{{$nav.messages.3}}">
												<i class="fa fa-envelope fa-fw" aria-hidden="true"></i>
												{{$nav.messages.1}} <span id="mail-update-li"
													class="nav-mail-badge badge nav-notification"></span>
											</a>
										</li>
									{{/if}}
									<li class="divider"><hr></li>
									{{if $nav.contacts}}
										<li>
											<a role="menuitem" id="nav-menu-contacts-link"
												class="nav-link {{$nav.contacts.2}}" href="{{$nav.contacts.0}}"
												title="{{$nav.contacts.3}}">
												<i class="fa fa-users fa-fw" aria-hidden="true"></i>
												{{$nav.contacts.1}}
											</a>
										</li>
									{{/if}}
									{{if $nav.delegation}}
										<li>
											<a role="menuitem" id="nav-delegation-link"
												class="nav-commlink {{$nav.delegation.2}} {{$sel.delegation}}"
												href="{{$nav.delegation.0}}" title="{{$nav.delegation.3}}">
												<i class="fa fa-flag fa-fw" aria-hidden="true"></i> {{$nav.delegation.1}}
											</a>
										</li>
									{{/if}}
									<li>
										<a role="menuitem" id="nav-directory-link" class="nav-link {{$nav.directory.2}}"
											href="{{$nav.directory.0}}" title="{{$nav.directory.3}}">
											<i class="fa fa-sitemap fa-fw" aria-hidden="true"></i>{{$nav.directory.1}}
										</a>
									</li>
									<li class="divider"><hr></li>
									{{if $nav.apps}}
										<li>
											<a role="menuitem" id="nav-apps-link" class="nav-link {{$nav.apps.2}}"
												href="{{$nav.apps.0}}" title="{{$nav.apps.3}}">
												<i class="fa fa-puzzle-piece fa-fw" aria-hidden="true"></i> {{$nav.apps.1}}
											</a>
										</li>
										<li class="divider"><hr></li>
									{{/if}}
									{{if $nav.help}}
										<li>
											<a role="menuitem" id="nav-help-link" class="nav-link {{$nav.help.2}}"
												href="{{$nav.help.0}}" title="{{$nav.help.3}}">
												<i class="fa fa-question-circle fa-fw" aria-hidden="true"></i> {{$nav.help.1}}
											</a>
										</li>
									{{/if}}
									{{if $nav.settings}}
										<li>
											<a role="menuitem" id="nav-settings-link" class="nav-link {{$nav.settings.2}}"
												href="{{$nav.settings.0}}" title="{{$nav.settings.3}}">
												<i class="fa fa-cog fa-fw" aria-hidden="true"></i> {{$nav.settings.1}}
											</a>
										</li>
									{{/if}}
									{{if $nav.admin}}
										<li>
											<a accesskey="a" role="menuitem" id="nav-admin-link"
												class="nav-link {{$nav.admin.2}}" href="{{$nav.admin.0}}"
												title="{{$nav.admin.3}}"><i class="fa fa-user-secret fa-fw" aria-hidden="true"></i>
												{{$nav.admin.1}}
											</a>
										</li>
									{{/if}}
									{{if $nav.moderation}}
										<li>
											<a accesskey="m" role="menuitem" id="nav-moderation-link"
												class="nav-link {{$nav.moderation.2}}" href="{{$nav.moderation.0}}"
												title="{{$nav.moderation.3}}"><i class="fa fa-gavel fa-fw" aria-hidden="true"></i>
												{{$nav.moderation.1}}
											</a>
										</li>
									{{/if}}
									<li class="divider"><hr></li>
									<li>
										<a role="menuitem" id="nav-about-link" class="nav-link {{$nav.about.2}}"
											href="{{$nav.about.0}}" title="{{$nav.about.3}}">
											<i class="fa fa-info fa-fw" aria-hidden="true"></i> {{$nav.about.1}}
										</a>
									</li>
									{{if $nav.tos}}
										<li>
											<a role="menuitem" id="nav-tos-link" class="nav-link {{$nav.tos.2}}"
												href="{{$nav.tos.0}}" title="{{$nav.tos.3}}"><i class="fa fa-file-text"
													aria-hidden="true"></i> {{$nav.tos.1}}
											</a>
										</li>
									{{/if}}
									<li class="divider"><hr></li>
									{{if $nav.logout}}
										<li>
											<a role="menuitem" id="nav-logout-link"
												class="nav-link {{$nav.logout.2}}" href="{{$nav.logout.0}}"
												title="{{$nav.logout.3}}"><i class="fa fa fa-sign-out fa-fw" aria-hidden="true"></i>
												{{$nav.logout.1}}
											</a>
										</li>
									{{else}}
										<li>
											<a role="menuitem" id="nav-login-link"
												class="nav-login-link {{$nav.login.2}}" href="{{$nav.login.0}}"
												title="{{$nav.login.3}}"><i class="fa fa-power-off fa-fw" aria-hidden="true"></i>
												{{$nav.login.1}}
											</a>
										</li>
									{{/if}}
								</ul>
							</li>{{* End of userinfo dropdown menu *}}
						{{/if}}

						<!-- Language selector, I do not find it relevant, activate if necessary.
						<li>{{$langselector}}</li>
						-->
					</ul>
				</div>{{* End of right navbar *}}

				{{* The usermenu dropdown for the mobile view. Offcanvas on the right side of the screen.
					It is called via the buttons. Have a look at the top of this file *}}
				<div class="offcanvas-right-overlay visible-xs-block"></div>
				<div id="offcanvasUsermenu" class="offcanvas-right visible-xs-block">
					<div class="nav-container">
						<ul role="menu" class="list-group">
							{{if $nav.remote}}
								{{if $nav.sitename}}
									<li role="menuitem" class="nav-sitename list-group-item">{{$nav.sitename}}</li>
								{{/if}}
							{{/if}}
							<li class="list-group-item">
								<img src="{{$userinfo.icon}}" alt="{{$userinfo.name}}"
									style="max-width:15px; max-height:15px; min-width:15px; min-height:15px; width:15px; height:15px;">
								{{$userinfo.name}}{{if $nav.remote}} ({{$nav.remote}}){{/if}}
							</li>
							{{foreach $nav.usermenu as $usermenu}}
								<li class="list-group-item">
									<a role="menuitem" class="{{$usermenu.2}}"
										href="{{$usermenu.0}}" title="{{$usermenu.3}}">{{$usermenu.1}}
									</a>
								</li>
							{{/foreach}}
							{{if $nav.notifications || $nav.contacts || $nav.messages || $nav.delegation}}
								<li class="divider"><hr></li>
							{{/if}}
							{{if $nav.notifications}}
								<li class="list-group-item">
									<a role="menuitem"
										href="{{$nav.notifications.all.0}}" title="{{$nav.notifications.1}}"><i
											class="fa fa-bell fa-fw" aria-hidden="true"></i> {{$nav.notifications.1}}
									</a>
								</li>
							{{/if}}
							{{if $nav.contacts}}
								<li class="list-group-item">
									<a role="menuitem"
										class="nav-link {{$nav.contacts.2}}" href="{{$nav.contacts.0}}"
										title="{{$nav.contacts.3}}"><i class="fa fa-users fa-fw" aria-hidden="true"></i>
										{{$nav.contacts.1}}
									</a>
								</li>
							{{/if}}
							{{if $nav.messages}}
								<li class="list-group-item">
									<a role="menuitem"
										class="nav-link {{$nav.messages.2}} {{$sel.messages}}" href="{{$nav.messages.0}}"
										title="{{$nav.messages.3}}"><i class="fa fa-envelope fa-fw" aria-hidden="true"></i>
										{{$nav.messages.1}}
									</a>
								</li>
							{{/if}}
							{{if $nav.delegation}}
								<li class="list-group-item">
									<a role="menuitem"
										class="nav-commlink {{$nav.delegation.2}} {{$sel.delegation}}"
										href="{{$nav.delegation.0}}" title="{{$nav.delegation.3}}"><i class="fa fa-flag fa-fw"
											aria-hidden="true"></i> {{$nav.delegation.1}}
									</a>
								</li>
							{{/if}}
							{{if $nav.settings || $nav.admin || $nav.logout}}
								<li class="divider"><hr></li>
							{{/if}}
							{{if $nav.settings}}
								<li class="list-group-item">
									<a role="menuitem" class="nav-link {{$nav.settings.2}}" href="{{$nav.settings.0}}"
										title="{{$nav.settings.3}}"><i class="fa fa-cog fa-fw" aria-hidden="true"></i>
										{{$nav.settings.1}}
									</a>
								</li>
							{{/if}}
							{{if $nav.admin}}
								<li class="list-group-item">
									<a role="menuitem"
										class="nav-link {{$nav.admin.2}}" href="{{$nav.admin.0}}" title="{{$nav.admin.3}}"><i
											class="fa fa-user-secret fa-fw" aria-hidden="true"></i>
										{{$nav.admin.1}}
									</a>
								</li>
							{{/if}}
							{{if $nav.moderation}}
								<li class="list-group-item">
									<a role="menuitem"
										class="nav-link {{$nav.moderation.2}}" href="{{$nav.moderation.0}}" title="{{$nav.moderation.3}}"><i
											class="fa fa-gavel fa-fw" aria-hidden="true"></i>
										{{$nav.moderation.1}}
									</a>
								</li>
							{{/if}}
							<li class="divider"><hr></li>
							<li class="list-group-item">
								<a role="menuitem" class="nav-link {{$nav.about.2}}"
								   href="{{$nav.about.0}}" title="{{$nav.about.3}}">
									<i class="fa fa-info fa-fw" aria-hidden="true"></i> {{$nav.about.1}}
								</a>
							</li>
							<li class="divider"><hr></li>
							{{if $nav.logout}}
								<li class="list-group-item">
									<a role="menuitem"
										class="nav-link {{$nav.logout.2}}" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}"><i
											class="fa fa fa-sign-out fa-fw" aria-hidden="true"></i>
										{{$nav.logout.1}}
									</a>
								</li>
							{{else}}
								<li class="list-group-item">
									<a role="menuitem"
										class="nav-login-link {{$nav.login.2}}" href="{{$nav.login.0}}"
										title="{{$nav.login.3}}"><i class="fa fa-power-off fa-fw" aria-hidden="true"></i>
										{{$nav.login.1}}
									</a>
								</li>
							{{/if}}
						</ul>
					</div>
				</div>
				<!--/.sidebar-offcanvas-->
			</div><!-- end of div for navbar width-->
		</div><!-- /.container -->
	</nav><!-- /.navbar -->
{{else}}
	{{* The navbar for users which are not logged in *}}
	<nav class="navbar navbar-fixed-top">
		<div class="container">
			<div class="navbar-header pull-left">
				<button type="button" class="navbar-toggle collapsed pull-left visible-sm visible-xs"
				        data-toggle="offcanvas" data-target="aside" aria-haspopup="true">
					<span class="sr-only">Toggle navigation</span>
					<i class="fa fa-ellipsis-v fa-fw fa-lg" aria-hidden="true"></i>
				</button>
				<a class="navbar-brand" href="#">
					<div id="navbrand-container">
						<div id="logo-img"></div>
						<div id="navbar-brand-text"> Friendica</div>
					</div>
				</a>
			</div>
			<div class="pull-right">
				<ul class="nav navbar-nav navbar-right">
					<li>
						<a href="login?mode=none" id="nav-login" data-toggle="tooltip" aria-label="{{$nav.login.3}}"
							title="{{$nav.login.3}}">
							<i class="fa fa-sign-in fa-fw" aria-hidden="true"></i>
						</a>
					</li>
					<li>
						<a href="{{$nav.about.0}}" id="nav-about" data-toggle="tooltip" aria-label="{{$nav.about.3}}"
							title="{{$nav.about.3}}">
							<i class="fa fa-info fa-fw" aria-hidden="true"></i>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</nav>
{{/if}}

{{* provide a a search input for mobile view, which expands by pressing the search icon *}}
<div id="search-mobile" class="hidden-lg hidden-md hidden-sm collapse row well">
	<div class="col-xs-12">
		<form class="navbar-form" role="search" method="get" action="{{$nav.search.0}}">
			<div class="form-group form-group-search">
				<input id="nav-search-input-field-mobile" class="form-control form-search" type="text" name="q"
					data-toggle="tooltip" title="{{$search_hint}}" placeholder="{{$nav.search.1}}">
				<button class="btn btn-default btn-sm form-button-search" type="submit">{{$nav.search.1}}</button>
			</div>
		</form>
	</div>
</div>

{{* The second navbar which contains nav points of the actual page - (nav points are actual handled by this theme through js *}}
<div id="topbar-second" class="topbar">
	<div class="container">
		<div class="col-lg-3 col-md-3 hidden-sm hidden-xs" id="nav-short-info"></div>
		<div class="col-lg-7 col-md-7 col-sm-11 col-xs-10" id="tabmenu"></div>
		<div class="col-lg-2 col-md-2 col-sm-1 col-xs-2" id="navbar-button"></div>
	</div>
</div>

{{* This is the mask of the firefox logo. We set the background of #logo-img to the user icon color and apply this mask to it
The result is a friendica logo in the user icon color.*}}
<svg id="friendica-logo-mask" x="0px" y="0px" width="0px" height="0px" viewBox="0 0 250 250">
	<defs>
		<mask id="logo-mask" maskUnits="objectBoundingBox" maskContentUnits="objectBoundingBox">
			<path style="fill-rule:evenodd;clip-rule:evenodd;fill:#ffffff;"
				d="M0.796,0L0.172,0.004C0.068,0.008,0.008,0.068,0,0.172V0.824c0,0.076,0.06,0.16,0.168,0.172h0.652c0.072,0,0.148-0.06,0.172-0.144V0.14C1,0.06,0.908,0,0.796,0zM0.812,0.968H0.36v-0.224h0.312v-0.24H0.36V0.3h0.316l0-0.264l0.116-0c0.088,0,0.164,0.044,0.164,0.096l0,0.696C0.96,0.912,0.876,0.968,0.812,0.968z">
			</path>
		</mask>
	</defs>
</svg>
