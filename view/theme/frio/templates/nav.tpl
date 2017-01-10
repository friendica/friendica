{{* we have modified the navmenu (look at function frio_remote_nav() ) to have remote links. $nav.userinfo is a new variable and replaces the original $userinfo variable *}}
{{if $nav.userinfo}}
<header>
	{{* {{$langselector}} *}}

	<div id="site-location">{{$sitelocation}}</div>
	<div id="banner" class="hidden-sm hidden-xs">
		{{* show on remote/visitor connections an other logo which symols that fact*}}
		{{if $nav.remote}}
		<a href="{{$baseurl}}"><div id="remote-logo-img"></div></a>
		{{else}}
		{{* #logo-img is the the placeholder to insert a mask (friendica logo) into this div
		For Firefox we have to call the paths of the mask (look at the bottom of this file).
		Because for FF we need relative paths we apply them with js after the page is loaded (look at theme.js *}}
		<a href="{{$baseurl}}"><div id="logo-img"></div></a>
		{{/if}}
	</div>
</header>
<nav id="topbar-first" class="topbar">
	<div class="container">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 no-padding"><!-- div for navbar width-->
			<!-- Brand and toggle get grouped for better mobile display -->
			<div class="topbar-nav" role=”navigation”>

				{{* Buttons for the mobile view *}}
				<button role="menu" type="button" class="navbar-toggle collapsed pull-right" data-toggle="offcanvas" data-target="#myNavmenu">
					<span class="sr-only">Toggle navigation</span>
					<i class="fa fa-ellipsis-v"></i>
				</button>
				<button type="button" class="navbar-toggle collapsed pull-right" data-toggle="collapse" data-target="#search-mobile" aria-expanded="false" aria-controls="navbar">
					<span class="sr-only">Toggle Search</span>
					<i class="fa fa-search" style="color:#FFF;"></i>
				</button>
				<button type="button" class="navbar-toggle collapsed pull-left visible-sm visible-xs" data-toggle="offcanvas" data-target="aside"  >
					<span class="sr-only">Toggle navigation</span>
					<i class="fa fa-ellipsis-v"></i>
				</button>

				{{* Left section of the NavBar with navigation shortcuts/icons *}}
				<ul class="nav navbar-nav navbar-left" role="menubar">
					<li id="nav-communication" class="nav-segment" role="presentation">
						{{if $nav.network}}
						<a role="menuitem" class="nav-menu {{$sel.network}}" href="{{$nav.network.0}}" data-toggle="tooltip" title="{{$nav.network.3}}"><i class="fa fa-lg fa-th"></i><span id="net-update" class="nav-network-badge badge nav-notify"></span></a>
						{{/if}}

						{{if $nav.home}}
						<a role="menuitem" class="nav-menu {{$sel.home}}" href="{{$nav.home.0}}" data-toggle="tooltip" title="{{$nav.home.3}}"><i class="fa fa-lg fa-home"></i><span id="home-update" class="nav-home-badge badge nav-notify"></span></a>
						{{/if}}

						{{if $nav.community}}
						<a role="menuitem" class="nav-menu {{$sel.community}}" href="{{$nav.community.0}}" data-toggle="tooltip" title="{{$nav.community.3}}"><i class="fa fa-lg fa-bullseye"></i></a>
						{{/if}}
					</li>

					<li id="nav-personal" class="nav-segment hidden-xs" role="presentation">
						{{if $nav.messages}}
						<a role="menuitem" id="nav-messages-link" href="{{$nav.messages.0}}" data-toggle="tooltip" title="{{$nav.messages.1}}" class="nav-menu {{$sel.messages}}"><i class="fa fa-envelope fa-lg"></i><span id="mail-update" class="nav-mail-badge badge nav-notify"></span></a>
						{{/if}}

						{{if $nav.events}}
						<a role="menuitem" id="nav-events-link" href="{{$nav.events.0}}" data-toggle="tooltip" data-toggle="tooltip" title="{{$nav.events.1}}" class="nav-menu"><i class="fa fa-lg fa-calendar"></i></a>
						{{/if}}

						{{if $nav.contacts}}
						<a role="menuitem" id="nav-contacts-link" class="nav-menu {{$sel.contacts}} {{$nav.contacts.2}}" href="{{$nav.contacts.0}}" data-toggle="tooltip" title="{{$nav.contacts.1}}" ><i class="fa fa-users fa-lg"></i></a>
						<span id="intro-update" class="nav-intro-badge badge nav-notify" href="#" onclick="window.location.href = '{{$nav.introductions.0}}' " data-toggle="tooltip" title="{{$nav.introductions.3}}"></span>
						{{/if}}
					</li>

					{{* The notifications dropdown *}}
					{{if $nav.notifications}}
						<li id="nav-notification" class="nav-segment hidden-xs" role="presentation">
							<a role="menuitem" href="{{$nav.notifications.0}}" rel="#nav-notifications-menu" data-toggle="tooltip" data-toggle="tooltip" title="{{$nav.notifications.1}}" class="btn-link" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="fa fa-exclamation-circle fa-lg"></i>
								<span class="sr-only">{{$nav.notifications.1}}</span>
								<span id="notify-update" class="nav-notify-badge badge nav-notify dropdown" data-toggle="dropdown"></span>
							</a>

							{{* The notifications dropdown menu. There are two parts of menu. The second is at the bottom of this file. It is loaded via js. Look at nav-notifications-template *}}
							<ul id="nav-notifications-menu" class="dropdown-menu menu-popup" role="menu" aria-labelledby="dropdownMenu1" style="display: none;">

								{{* the following list entry must have the id "nav-notificaionts-mark-all". Without it this isn't visable. ....strange behavior :-/ *}}
								<li role="menuitem" id="nav-notifications-mark-all" class="dropdown-header">
									<div class="arrow"></div>
									{{$nav.notifications.1}}
									<div class="dropdown-header-link">
										<a href="#" onclick="notifyMarkAll(); return false;" data-toggle="tooltip" title="{{$nav.notifications.mark.3}}" class="">{{$nav.notifications.mark.1}}</a>
									</div>

								</li>

								<li role="menuitem">
									<p class="text-muted"><i>{{$emptynotifications}}</i></p>
								</li>
							</ul>
						</li>
						{{/if}}

				</ul>
			</div>

			{{* This is the right part of the NavBar. It includes the search and the user menu *}}
			<div class="topbar-actions pull-right">
				<ul class="nav">

					{{* The search box *}}
					{{if $nav.search}}
					<li id="search-box" class="hidden-xs">
							<form class="navbar-form" role="search" method="get" action="{{$nav.search.0}}">
								<!-- <img class="hidden-xs" src="{{$nav.userinfo.icon}}" alt="{{$nav.userinfo.name}}" style="max-width:33px; max-height:33px; min-width:33px; min-height:33px; width:33px; height:33px;"> -->
								<div class="form-group form-group-search">
									<input id="nav-search-input-field" class="form-control form-search" type="text" name="search" data-toggle="tooltip" title="{{$search_hint}}" placeholder="{{$nav.search.1}}">
									<button class="btn btn-default btn-sm form-button-search" type="submit">{{$nav.search.1}}</button>
								</div>
							</form>
					</li>
					{{/if}}

					{{* The user dropdown menu *}}
					{{if $nav.userinfo}}
					<li role="menu" id="nav-user-linkmenu" class="dropdown account nav-menu hidden-xs">
						<a href="#" id="main-menu" class="dropdown-toggle nav-avatar " data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							<div class="user-title pull-left hidden-xs hidden-sm hidden-md">
								<strong>{{$nav.userinfo.name}}</strong><br>
								{{if $nav.remote}}<span class="trunctate">{{$nav.remote}}</span>{{/if}}
							</div>

							<img  id="avatar" src="{{$nav.userinfo.icon}}" alt="{{$nav.userinfo.name}}">
							<span class="caret"></span>

						</a>

						{{* The list of available usermenu links *}}
						<ul id="nav-user-menu" class="dropdown-menu pull-right menu-popup" role="group" aria-labelledby="main-menu">
							{{if $nav.remote}}{{if $nav.sitename}}
							<li id="nav-sitename" role="menuitem">{{$nav.sitename}}</li>
							<li role="separator" class="divider"></li>
							{{/if}}{{/if}}
							{{foreach $nav.usermenu as $usermenu}}
							<li role="menuitem"><a class="{{$usermenu.2}}" href="{{$usermenu.0}}" title="{{$usermenu.3}}">{{$usermenu.1}}</a></li>
							{{/foreach}}
							<li role="separator" class="divider"></li>
							{{if $nav.notifications}}
							<li role="menuitem"><a href="{{$nav.notifications.all.0}}" title="{{$nav.notifications.1}}"><i class="fa fa-exclamation-circle fa-fw"></i> {{$nav.notifications.1}}</a></li>
							{{/if}}
							{{if $nav.messages}}
							<li role="menuitem"><a class="nav-commlink {{$nav.messages.2}} {{$sel.messages}}" href="{{$nav.messages.0}}" title="{{$nav.messages.3}}" ><i class="fa fa-envelope fa-fw"></i> {{$nav.messages.1}} <span id="mail-update-li" class="nav-mail-badge badge nav-notify"></span></a></li>
							{{/if}}
							<li role="separator" class="divider"></li>
							{{if $nav.contacts}}
							<li role="menuitem"><a id="nav-contacts-link" class="nav-link {{$nav.contacts.2}}" href="{{$nav.contacts.0}}" title="{{$nav.contacts.3}}"><i class="fa fa-users fa-fw"></i> {{$nav.contacts.1}}</a><span id="intro-update-li" class="nav-intro-badge badge nav-notify"></span></li>
							{{/if}}
							{{if $nav.manage}}
							<li role="menuitem"><a id="nav-manage-link" class="nav-commlink {{$nav.manage.2}} {{$sel.manage}}" href="{{$nav.manage.0}}" title="{{$nav.manage.3}}"><i class="fa fa-flag fa-fw"></i> {{$nav.manage.1}}</a></li>
							{{/if}}
							<li role="menuitem"><a id="nav-directory-link" class="nav-link {{$nav.directory.2}}" href="{{$nav.directory.0}}" title="{{$nav.directory.3}}"><i class="fa fa-sitemap fa-fw"></i>{{$nav.directory.1}}</a></li>
							<li role="separator" class="divider"></li>
							{{if $nav.apps}}
							<li role="menuitem"><a id="nav-apps-link" class="nav-link {{$nav.apps.2}} {{$sel.manage}}" href="{{$nav.apps.0}}" title="{{$nav.apps.3}}" ><i class="fa fa-puzzle-piece fa-fw"></i> {{$nav.apps.1}}</a>
							<li role="separator" class="divider"></li>
							{{/if}}
							{{if $nav.help}}
							<li role="menuitem"><a id="nav-help-link" class="nav-link {{$nav.help.2}}" href="{{$nav.help.0}}" title="{{$nav.help.3}}" ><i class="fa fa-question-circle fa-fw"></i> {{$nav.help.1}}</a></li>
							{{/if}}
							{{if $nav.settings}}
							<li role="menuitem"><a id="nav-settings-link" class="nav-link {{$nav.settings.2}}" href="{{$nav.settings.0}}" title="{{$nav.settings.3}}"><i class="fa fa-cog fa-fw"></i> {{$nav.settings.1}}</a></li>
							{{/if}}
							{{if $nav.admin}}
							<li role="menuitem"><a id="nav-admin-link" class="nav-link {{$nav.admin.2}}" href="{{$nav.admin.0}}" title="{{$nav.admin.3}}" ><i class="fa fa-user-secret fa-fw"></i> {{$nav.admin.1}}</a></li>
							{{/if}}
							<li role="separator" class="divider"></li>
							{{if $nav.logout}}
							<li role="menuitem"><a id="nav-logout-link" class="nav-link {{$nav.logout.2}}" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" ><i class="fa fa fa-sign-out fa-fw"></i> {{$nav.logout.1}}</a></li>
							{{else}}
							<li role="menuitem"><a id="nav-login-link" class="nav-login-link {{$nav.login.2}}" href="{{$nav.login.0}}" title="{{$nav.login.3}}" ><i class="fa fa-power-off fa-fw"></i> {{$nav.login.1}}</a></li>
							{{/if}}
						</ul>
					</li>{{* End of userinfo dropdown menu *}}
					{{/if}}

				<!-- Language selector, I do not find it relevant, activate if necessary.
					<li>{{$langselector}}</li>
				-->
				</ul>
			</div>{{* End of right navbar *}}

			{{* The usermenu dropdown for the mobile view. It is called via the buttons. Have a look at the top of this file *}}
			<div id="myNavmenu" class="navmenu navmenu-default navmenu-fixed-right offcanvas">
				<div class="nav-container">
					<div class="list-group">
						{{if $nav.remote}}{{if $nav.sitename}}
						<li role="menuitem" class="nav-sitename list-group-item" role="menuitem">{{$nav.sitename}}</li>
						{{/if}}{{/if}}
						<li role="menuitem" class="list-group-item"><img src="{{$nav.userinfo.icon}}" alt="{{$nav.userinfo.name}}" style="max-width:15px; max-height:15px; min-width:15px; min-height:15px; width:15px; height:15px;"> {{$nav.userinfo.name}}{{if $nav.remote}} ({{$nav.remote}}){{/if}}</li>
						{{foreach $nav.usermenu as $usermenu}}
						<a role="menuitem" class="{{$usermenu.2}} list-group-item" href="{{$usermenu.0}}" title="{{$usermenu.3}}">{{$usermenu.1}}</a>
						{{/foreach}}
						{{if $nav.notifications}}
						<a role="menuitem" href="{{$nav.notifications.all.0}}" title="{{$nav.notifications.1}}" class="list-group-item"><i class="fa fa-exclamation-circle fa-fw"></i> {{$nav.notifications.1}}</a>
						{{/if}}
						{{if $nav.contacts}}
						<a role="menuitem" class="nav-link {{$nav.contacts.2}} list-group-item" href="{{$nav.contacts.0}}" title="{{$nav.contacts.3}}"><i class="fa fa-users fa-fw"></i> {{$nav.contacts.1}}</a>
						{{/if}}
						{{if $nav.messages}}
						<a role="menuitem" class="nav-link {{$nav.messages.2}} {{$sel.messages}} list-group-item" href="{{$nav.messages.0}}" title="{{$nav.messages.3}}" ><i class="fa fa-envelope fa-fw"></i> {{$nav.messages.1}}</a>
						{{/if}}
						{{if $nav.manage}}
						<a role="menuitem" class="nav-commlink {{$nav.manage.2}} {{$sel.manage}} list-group-item" href="{{$nav.manage.0}}" title="{{$nav.manage.3}}"><i class="fa fa-flag fa-fw"></i> {{$nav.manage.1}}</a>
						{{/if}}
						{{if $nav.settings}}
						<a role="menuitem" class="nav-link {{$nav.settings.2}} list-group-item" href="{{$nav.settings.0}}" title="{{$nav.settings.3}}"><i class="fa fa-cog fa-fw"></i> {{$nav.settings.1}}</a>
						{{/if}}
						{{if $nav.admin}}
						<a role="menuitem" class="nav-link {{$nav.admin.2}} list-group-item" href="{{$nav.admin.0}}" title="{{$nav.admin.3}}" ><i class="fa fa-user-secret fa-fw"></i> {{$nav.admin.1}}</a>
						{{/if}}
						{{if $nav.logout}}
						<a role="menuitem" class="nav-link {{$nav.logout.2}} list-group-item" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" ><i class="fa fa fa-sign-out fa-fw"></i> {{$nav.logout.1}}</a>
						{{else}}
						<a role="menuitem" class="nav-login-link {{$nav.login.2}} list-group-item" href="{{$nav.login.0}}" title="{{$nav.login.3}}" ><i class="fa fa-power-off fa-fw"></i> {{$nav.login.1}}</a>
						{{/if}}
					</div>
				</div>
			</div><!--/.sidebar-offcanvas-->
		</div><!-- end of div for navbar width-->
	</div><!-- /.container -->
</nav><!-- /.navbar -->
{{/if}}


{{* The navbar for users which are not logged in *}}
{{if $nav.userinfo == ''}}
<nav class="navbar navbar-fixed-top">
	<div class="container">
		<div class="navbar-header pull-left">
			<a class="navbar-brand" href="#"><div id="navbrand-container">
				<div id="logo-img"></div>
				<div id="navbar-brand-text"> Friendica</div></div>
			</a>
		</div>
		<div class="pull-right">
			<ul class="nav navbar-nav navbar-right">
				<li><a href="register" data-toggle="tooltip" title="{{$register.title}}"><i class="fa fa-street-view fa-fw"></i> {{$register.desc}}</a></li>
				<li>
					<a href="login?mode=none" id="nav-login"
						data-toggle="tooltip" title="{{$nav.login.3}}">
							<i class="fa fa-sign-in fa-fw"></i>
					</a>
				</li>
			</ul>
		</div>
	</div>
</nav>

{{/if}}

{{* provide a a search input for mobile view, which expands by pressing the search icon *}}
<div id="search-mobile" class="hidden-lg hidden-md collapse">
	<form class="navbar-form" role="search" method="get" action="{{$nav.search.0}}">
		<!-- <img class="hidden-xs" src="{{$nav.userinfo.icon}}" alt="{{$nav.userinfo.name}}" style="max-width:33px; max-height:33px; min-width:33px; min-height:33px; width:33px; height:33px;"> -->
		<div class="form-group form-group-search">
			<input id="nav-search-input-field-mobile" class="form-control form-search" type="text" name="search" data-toggle="tooltip" title="{{$search_hint}}" placeholder="{{$nav.search.1}}">
			<button class="btn btn-default btn-sm form-button-search" type="submit">{{$nav.search.1}}</button>
		</div>
	</form>
</div>

{{* The second navbar which contains nav points of the actual page - (nav points are actual handled by this theme throug js *}}
<div id="topbar-second" class="topbar">
	<div class="container">
		<div class="col-lg-3 col-md-3 hidden-sm hidden-xs" id="nav-short-info"></div>
		<div class="col-lg-7 col-md-7 col-sm-11 col-xs-10" id="tabmenu"></div>
		<div class="col-lg-2 col-md-2 col-sm-1 col-xs-2" id="navbar-button"></div>
	</div>
</div>

{{*The second part of the notifications dropdown menu. It handles the notifications *}}
{{if $nav.notifications}}
<ul id="nav-notifications-template" class="media-list" style="display:none;" rel="template">
	<li class="{4} notif-entry">
		<div class="notif-entry-wrapper media">
			<div class="notif-photo-wrapper media-object pull-left"><a href="{6}" class="userinfo"><img data-src="{1}"></a></div>
			<a href="{0}" class="notif-desc-wrapper media-body">
				{2}
				<div><time class="notif-when time" data-toggle="tooltip" title="{5}">{3}</time></div>
			</a>
		</div>
	</li>
</ul>
{{/if}}

{{* This is the mask of the firefox logo. We set the background of #logo-img to the user icon color and apply this mask to it
The result is a friendica logo in the user icon color.*}}
<svg id="friendica-logo-mask" x="0px" y="0px" width="0px" height="0px" viewBox="0 0 250 250">
	<defs>
		<mask id="logo-mask" maskUnits="objectBoundingBox" maskContentUnits="objectBoundingBox">
			<path style="fill-rule:evenodd;clip-rule:evenodd;fill:#ffffff;" d="M0.796,0L0.172,0.004C0.068,0.008,0.008,0.068,0,0.172V0.824c0,0.076,0.06,0.16,0.168,0.172h0.652c0.072,0,0.148-0.06,0.172-0.144V0.14C1,0.06,0.908,0,0.796,0zM0.812,0.968H0.36v-0.224h0.312v-0.24H0.36V0.3h0.316l0-0.264l0.116-0c0.088,0,0.164,0.044,0.164,0.096l0,0.696C0.96,0.912,0.876,0.968,0.812,0.968z"/>
		</mask>
	</defs>
</svg>
