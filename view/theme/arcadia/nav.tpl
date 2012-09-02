$langselector
<div id="banner">$banner</div>
<nav id="user_nav">
	<h1 id="site-location" class="button" onclick="toggle('user_nav-menu');">$sitelocation</h1>
	<div class="menu" id="user_nav-menu" onBlur="hide('user_nav-menu');" tabindex="-1">
		<ul>
			{{ if $nav.contacts }}
			<li onclick="link_act('$nav.contacts.0');" title="$nav.contacts.3">
				<a id="nav-contacts-link" class="nav-link $nav.contacts.2" href="$nav.contacts.0" title="$nav.contacts.3" >$nav.contacts.1</a>
			</li>
			{{ endif }}
			{{ if $nav.profiles }}
			<li onclick="link_act('$nav.profiles.0');" title="$nav.profiles.3">
				<a id="nav-profiles-link" class="nav-link $nav.profiles.2" href="$nav.profiles.0" title="$nav.profiles.3" >$nav.profiles.1</a>
			</li>
			{{ endif }}
			{{ if $nav.settings }}
			<li onclick="link_act('$nav.settings.0');" title="$nav.settings.3">
				<a id="nav-settings-link" class="nav-link $nav.settings.2" href="$nav.settings.0" title="$nav.settings.3">$nav.settings.1</a>
			</li>
			{{ endif }}
			{{ if $nav.manage }}
			<li onclick="link_act('$nav.manage.0');" title="$nav.manage.3">
				<a id="nav-manage-link" class="nav-commlink $nav.manage.2 $sel.manage" href="$nav.manage.0" title="$nav.manage.3">$nav.manage.1</a>
			</li>
			{{ endif }}
			{{ if $nav.admin }}
			<li onclick="link_act('$nav.admin.0');" title="$nav.admin.3">
				<a id="nav-admin-link" class="nav-link $nav.admin.2" href="$nav.admin.0" title="$nav.admin.3" >$nav.admin.1</a>
			</li>
			{{ endif }}
			{{ if $nav.logout }}
			<li onclick="link_act('$nav.logout.0');" title="$nav.logout.3">
				<a id="nav-logout-link" class="nav-link $nav.logout.2" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a>
			</li>
			{{ endif }}
			{{ if $nav.login }}
			<li onclick="link_act('$nav.login.0');" title="$nav.login.3">
				<a id="nav-login-link" class="nav-login-link $nav.login.2" href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a> 
			</li>
			{{ endif }}
		</ul>
	</div>
</nav>
<nav id="site_nav">
	<ul>
		<li class="button" onclick="link_act('$nav.directory.0');" title="$nav.directory.3">
			<a id="nav-directory-link" class="nav-link $nav.directory.2" href="$nav.directory.0" title="$nav.directory.3" >$nav.directory.1</a>
		</li>
		<li class="button" onclick="link_act('$nav.search.0');" title="$nav.search.3">
			<a id="nav-search-link" class="nav-link $nav.search.2" href="$nav.search.0" title="$nav.search.3" >$nav.search.1</a>
		</li>
		{{ if $nav.help }}<li class="button" onclick="link_act('$nav.help.0');" title="$nav.help.3">
			<a id="nav-help-link" class="nav-link $nav.help.2" target="friendika-help" href="$nav.help.0" title="$nav.help.3" >$nav.help.1</a>
		</li>{{ endif }}
		{{ if $nav.register }}<li class="button" onclick="link_act('$nav.register.0');" title="$nav.register.3">
			<a id="nav-register-link" class="nav-commlink $nav.register.2 $sel.register" href="$nav.register.0" title="$nav.register.3" >$nav.register.1</a>
		</li>{{ endif }}
	</ul>
	
	
	
</nav>
<nav id="comm_nav">
	<ul>
		{{ if $nav.network }}
		<li class="button" onclick="link_act('$nav.network.0');" title="$nav.network.3">
			<a id="nav-network-link" class="nav-commlink $nav.network.2 $sel.network" href="$nav.network.0" title="$nav.network.3" >$nav.network.1</a>
			<span id="net-update" class="nav-ajax-left"></span>
		</li>
		{{ endif }}
		{{ if $nav.community }}
		<li class="button" onclick="link_act('$nav.community.0');" title="$nav.community.3">
			<a id="nav-community-link" class="nav-commlink $nav.community.2 $sel.community" href="$nav.community.0" title="$nav.community.3" >$nav.community.1</a>
		</li>
		{{ endif }}
		{{ if $nav.home }}
		<li class="button" onclick="link_act('$nav.home.0');" title="$nav.home.3">
			<a id="nav-home-link" class="nav-commlink $nav.home.2 $sel.home" href="$nav.home.0" title="$nav.home.3" >$nav.home.1</a>
			<span id="home-update" class="nav-ajax-left"></span>
		</li>
		{{ endif }}
		{{ if $nav.introductions }}
		<li class="button" onclick="link_act('$nav.introductions.0');" title="$nav.introductions.3">
			<a id="nav-notify-link" class="nav-commlink $nav.introductions.2 $sel.introductions" href="$nav.introductions.0" title="$nav.introductions.3" >$nav.introductions.1</a>
			<span id="intro-update" class="nav-ajax-left"></span>
		</li>
		{{ endif }}
		{{ if $nav.messages }}
		<li class="button" onclick="link_act('$nav.messages.0');" title="$nav.messages.3">
			<a id="nav-messages-link" class="nav-commlink $nav.messages.2 $sel.messages" href="$nav.messages.0" title="$nav.messages.3" >$nav.messages.1</a>
			<span id="mail-update" class="nav-ajax-left"></span>
		</li>
		{{ endif }}
		{{ if $nav.notifications }}
		<li class="button" onclick="toggle('notifications_menu');" title="$nav.notifications.1">
			<span id="nav-notifications-linkmenu" class="nav-commlink" rel="#nav-notifications-menu" title="$nav.notifications.1">$nav.notifications.1</span>
			<span id="notify-update" class="nav-ajax-left"></span>
			<div id="notifications_menu" class="menu" onBlur="hide('notifications_menu');" tabindex="-1">
				<ul id="nav-notifications-menu" class="menu-popup">
					<li id="nav-notifications-see-all"><a href="$nav.notifications.all.0">$nav.notifications.all.1</a></li>
					<li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">$nav.notifications.mark.1</a></li>
					<li class="empty">$emptynotifications</li>
				</ul>
			</div>
		{{ endif }}
		{{ if $nav.apps }}
		<li class="button" onclick="link_act('$nav.apps.0');" title="$nav.apps.3">
			<a id="nav-apps-link" class="nav-link $nav.apps.2" href="$nav.apps.0" title="$nav.apps.3" >$nav.apps.1</a>
		</li>
		{{ endif }}
	</ul>
</nav>
<span id="nav-end"></span>

<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li class="{4}" onclick="link_act('{0}');"><a href="{0}"><img src="{1}" height="24" width="24" alt="" />{2} <span class="notif-when">{3}</span></a></li>
</ul>
