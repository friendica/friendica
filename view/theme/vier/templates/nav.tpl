
<header>
	{{* {{$langselector}} *}}

	<div id="site-location">{{$sitelocation}}</div>
	<div id="banner">{{$banner}}</div>
</header>
<nav role="menubar">
	<ul>
		<li class="mobile-aside-toggle" style="display:none;">
			<a href="#">
				<i class="icons icon-reorder"></i>
			</a>
		</li>
		{{if $nav.home}}
			<li role="menuitem" id="nav-home-link" class="nav-menu {{$sel.home}}">
				<a accesskey="p" class="{{$nav.home.2}}" href="{{$nav.home.0}}" title="{{$nav.home.3}}" >{{$nav.home.1}}</a>
				<span id="home-update" class="nav-notify"></span>
			</li>
		{{/if}}
		{{if $nav.network}}
			<li role="menuitem" id="nav-network-link" class="nav-menu {{$sel.network}}">
				<a accesskey="n" class="{{$nav.network.2}}" href="{{$nav.network.0}}" title="{{$nav.network.3}}" >{{$nav.network.1}}</a>
				<span id="net-update" class="nav-notify"></span>
			</li>
		{{/if}}
		{{if $nav.events}}
			<li role="menuitem" id="nav-events-link" class="nav-menu {{$sel.events}}">
				<a accesskey="e" class="{{$nav.events.2}}" href="{{$nav.events.0}}" title="{{$nav.events.3}}" >{{$nav.events.1}}</a>
			</li>
		{{/if}}
		{{if $nav.community}}
			<li role="menuitem" id="nav-community-link" class="nav-menu {{$sel.community}}">
				<a accesskey="c" class="{{$nav.community.2}}" href="{{$nav.community.0}}" title="{{$nav.community.3}}" >{{$nav.community.1}}</a>
			</li>
		{{/if}}

		<li role="menu" aria-haspopup="true" id="nav-site-linkmenu" class="nav-menu-icon"><a><span class="icon s22 icon-question"><span class="sr-only">{{$nav.help.3}}</span></span></a>
			<ul id="nav-site-menu" class="menu-popup">
				{{if $nav.help}} <li role="menuitem"><a class="{{$nav.help.2}}" href="{{$nav.help.0}}" title="{{$nav.help.3}}" >{{$nav.help.1}}</a></li>{{/if}}
				<li role="menuitem"><a class="{{$nav.about.2}}" href="{{$nav.about.0}}" title="{{$nav.about.3}}" >{{$nav.about.1}}</a></li>
				<li role="menuitem"><a class="{{$nav.directory.2}}" href="{{$nav.directory.0}}" title="{{$nav.directory.3}}" >{{$nav.directory.1}}</a></li>
			</ul>
		</li>

		{{if $nav.notifications}}
			<li role="menu" aria-haspopup="true" id="nav-notifications-linkmenu" class="nav-menu-icon"><a title="{{$nav.notifications.1}}"><span class="icon s22 icon-bell tilted-icon"><span class="sr-only">{{$nav.notifications.1}}</span></span></a>
				<span id="notify-update" class="nav-notify"></span>
				<ul id="nav-notifications-menu" class="menu-popup">
					<li role="menuitem" id="nav-notifications-mark-all"><a onclick="notifyMarkAll(); return false;">{{$nav.notifications.mark.1}}</a></li>
					<li role="menuitem" id="nav-notifications-see-all"><a href="{{$nav.notifications.all.0}}">{{$nav.notifications.all.1}}</a></li>
					<li role="menuitem" class="empty">{{$emptynotifications}}</li>
				</ul>
			</li>
		{{/if}}

		{{if $userinfo}}
			<li role="menu" aria-haspopup="true" id="nav-user-linkmenu" class="nav-menu">
				<a accesskey="u" title="{{$sitelocation}}"><img src="{{$userinfo.icon}}" alt="{{$userinfo.name}}"><span id="nav-user-linklabel">{{$userinfo.name}}</span><span id="intro-update" class="nav-notify"></span></a>
				<ul id="nav-user-menu" class="menu-popup">
					{{if $nav.introductions}}<li role="menuitem"><a class="{{$nav.introductions.2}}" href="{{$nav.introductions.0}}" title="{{$nav.introductions.3}}" >{{$nav.introductions.1}}</a><span id="intro-update-li" class="nav-notify"></span></li>{{/if}}
					{{if $nav.contacts}}<li role="menuitem"><a class="{{$nav.contacts.2}}" href="{{$nav.contacts.0}}" title="{{$nav.contacts.3}}" >{{$nav.contacts.1}}</a></li>{{/if}}
					{{if $nav.messages}}<li role="menuitem"><a class="{{$nav.messages.2}}" href="{{$nav.messages.0}}" title="{{$nav.messages.3}}" >{{$nav.messages.1}}</a><span id="mail-update" class="nav-notify"></span></a></li>{{/if}}
					{{if $nav.manage}}<li role="menuitem"><a class="{{$nav.manage.2}}" href="{{$nav.manage.0}}" title="{{$nav.manage.3}}">{{$nav.manage.1}}</a></li>{{/if}}
					{{if $nav.usermenu.1}}<li role="menuitem"><a class="{{$nav.usermenu.1.2}}" href="{{$nav.usermenu.1.0}}" title="{{$nav.usermenu.1.3}}">{{$nav.usermenu.1.1}}</a></li>{{/if}}
					{{if $nav.settings}}<li role="menuitem"><a class="{{$nav.settings.2}}" href="{{$nav.settings.0}}" title="{{$nav.settings.3}}">{{$nav.settings.1}}</a></li>{{/if}}
					{{if $nav.logout}}<li role="menuitem"><a class="menu-sep {{$nav.logout.2}}" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" >{{$nav.logout.1}}</a></li>{{/if}}
				</ul>
			</li>
		{{/if}}

		{{if $nav.login}}
			<li role="menuitem" id="nav-login-link" class="nav-menu">
				<a class="{{$nav.login.2}}" href="{{$nav.login.0}}" title="{{$nav.login.3}}" >{{$nav.login.1}}</a>
			</li>
		{{/if}}

		{{if $nav.search}}
			<li role="search" id="search-box">
				<form method="get" action="{{$nav.search.0}}">
					<input accesskey="s" id="search-text" class="nav-menu-search" type="text" value="" name="search">
					<select name="search-option">
						<option value="fulltext">{{$nav.searchoption.0}}</option>
						<option value="tags">{{$nav.searchoption.1}}</option>
						<option value="contacts">{{$nav.searchoption.2}}</option>
						{{if $nav.searchoption.3}}<option value="forums">{{$nav.searchoption.3}}</option>{{/if}}
					</select>
				</form>
			</li>
		{{/if}}

		{{if $nav.admin}}
			<li role="menuitem" id="nav-admin-link" class="nav-menu">
				<a accesskey="a" class="{{$nav.admin.2}} icon-sliders" href="{{$nav.admin.0}}" title="{{$nav.admin.3}}" ><span class="sr-only">{{$nav.admin.3}}</span></a>
			</li>
		{{/if}}

		{{if $nav.apps}}
			<li role="menu" aria-haspopup="true" id="nav-apps-link" class="nav-menu {{$sel.apps}}">
				<a class=" {{$nav.apps.2}}" title="{{$nav.apps.3}}" >{{$nav.apps.1}}</a>
				<ul id="nav-apps-menu" class="menu-popup">
					{{foreach $apps as $ap}}
					<li role="menuitem">{{$ap}}</li>
					{{/foreach}}
				</ul>
			</li>
		{{/if}}
	</ul>

</nav>
<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li class="{4}"><a href="{0}" title="{5}"><img data-src="{1}">{2} <span class="notif-when">{3}</span></a></li>
</ul>
<!--
<div class="icon-flag" style="position: fixed; bottom: 10px; left: 20px; z-index:9;">{{$langselector}}</div>
-->
