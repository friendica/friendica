<nav>
	$langselector

	<div id="site-location">$sitelocation</div>

	{{ if $nav.logout }}<a id="nav-logout-link" class="nav-link $nav.logout.2" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a> {{ endif }}
	{{ if $nav.login }}<a id="nav-login-link" class="nav-login-link $nav.login.2" href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a> {{ endif }}

	<span id="nav-link-wrapper" >

	{{ if $nav.register }}<a id="nav-register-link" class="nav-commlink $nav.register.2 $sel.register" href="$nav.register.0" title="$nav.register.3" >$nav.register.1</a>{{ endif }}
		
	{{ if $nav.help }} <a id="nav-help-link" class="nav-link $nav.help.2" target="friendika-help" href="$nav.help.0" title="$nav.help.3" >$nav.help.1</a>{{ endif }}
		
	{{ if $nav.apps }}<a id="nav-apps-link" class="nav-link $nav.apps.2" href="$nav.apps.0" title="$nav.apps.3" >$nav.apps.1</a>{{ endif }}

	<a id="nav-search-link" class="nav-link $nav.search.2" href="$nav.search.0" title="$nav.search.3" >$nav.search.1</a>
	<a id="nav-directory-link" class="nav-link $nav.directory.2" href="$nav.directory.0" title="$nav.directory.3" >$nav.directory.1</a>

	{{ if $nav.admin }}<a id="nav-admin-link" class="nav-link $nav.admin.2" href="$nav.admin.0" title="$nav.admin.3" >$nav.admin.1</a>{{ endif }}

	{{ if $nav.network }}
	<a id="nav-network-link" class="nav-commlink $nav.network.2 $sel.network" href="$nav.network.0" title="$nav.network.3" >$nav.network.1</a>
	<span id="net-update" class="nav-ajax-left"></span>
	{{ endif }}
	{{ if $nav.home }}
	<a id="nav-home-link" class="nav-commlink $nav.home.2 $sel.home" href="$nav.home.0" title="$nav.home.3" >$nav.home.1</a>
	<span id="home-update" class="nav-ajax-left"></span>
	{{ endif }}
	{{ if $nav.community }}
	<a id="nav-community-link" class="nav-commlink $nav.community.2 $sel.community" href="$nav.community.0" title="$nav.community.3" >$nav.community.1</a>
	{{ endif }}
	{{ if $nav.introductions }}
	<a id="nav-notify-link" class="nav-commlink $nav.introductions.2 $sel.introductions" href="$nav.introductions.0" title="$nav.introductions.3" >$nav.introductions.1</a>
	<span id="intro-update" class="nav-ajax-left"></span>
	{{ endif }}
	{{ if $nav.messages }}
	<a id="nav-messages-link" class="nav-commlink $nav.messages.2 $sel.messages" href="$nav.messages.0" title="$nav.messages.3" >$nav.messages.1</a>
	<span id="mail-update" class="nav-ajax-left"></span>
	{{ endif }}



	{{ if $nav.manage }}<a id="nav-manage-link" class="nav-commlink $nav.manage.2 $sel.manage" href="$nav.manage.0" title="$nav.manage.3">$nav.manage.1</a>{{ endif }}


		{{ if $nav.notifications }}
			<a id="nav-notifications-linkmenu" class="nav-commlink" href="$nav.notifications.0" rel="#nav-notifications-menu" title="$nav.notifications.1">$nav.notifications.1</a>
				<span id="notify-update" class="nav-ajax-left"></span>
				<ul id="nav-notifications-menu" class="menu-popup">
					<li id="nav-notifications-see-all"><a href="$nav.notifications.all.0">$nav.notifications.all.1</a></li>
					<li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">$nav.notifications.mark.1</a></li>
					<li class="empty">$emptynotifications</li>
				</ul>
		{{ endif }}		

	{{ if $nav.settings }}<a id="nav-settings-link" class="nav-link $nav.settings.2" href="$nav.settings.0" title="$nav.settings.3">$nav.settings.1</a>{{ endif }}
	{{ if $nav.profiles }}<a id="nav-profiles-link" class="nav-link $nav.profiles.2" href="$nav.profiles.0" title="$nav.profiles.3" >$nav.profiles.1</a>{{ endif }}

	{{ if $nav.contacts }}<a id="nav-contacts-link" class="nav-link $nav.contacts.2" href="$nav.contacts.0" title="$nav.contacts.3" >$nav.contacts.1</a>{{ endif }}
	</span>
	<span id="nav-end"></span>
	<span id="banner">$banner</span>
</nav>

<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li class="{4}"><a href="{0}"><img src="{1}" height="24" width="24" alt="" />{2} <span class="notif-when">{3}</span></a></li>
</ul>
