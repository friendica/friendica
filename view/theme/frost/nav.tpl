<nav>
	$langselector

	<div id="site-location">$sitelocation</div>

	<span id="nav-link-wrapper" >

<!--	<a id="system-menu-link" class="nav-link" href="#system-menu" title="Menu">Menu</a>-->
	<div class="nav-button-container nav-menu-link" rel="#system-menu-list">
	<a class="system-menu-link nav-link" href="$nav.settings.0" title="Main Menu">
	<img class="system-menu-link" src="/view/theme/frost/images/menu.png">
	</a>
	<ul id="system-menu-list" class="nav-menu-list">
		{{ if $nav.login }}
		<a id="nav-login-link" class="nav-load-page-link $nav.login.2" href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a>
		{{ endif }}

		{{ if $nav.register }}
		<a id="nav-register-link" class="nav-load-page-link $nav.register.2 $sel.register" href="$nav.register.0" title="$nav.register.3" >$nav.register.1</a>
		{{ endif }}

		{{ if $nav.manage }}
		<li>
		<a id="nav-manage-link" class="nav-load-page-link $nav.manage.2 $sel.manage" href="$nav.manage.0" title="$nav.manage.3">$nav.manage.1</a>
		</li>
		{{ endif }}

		{{ if $nav.settings }}
		<li><a id="nav-settings-link" class="$nav.settings.2 nav-load-page-link" href="$nav.settings.0" title="$nav.settings.3">$nav.settings.1</a></li>
		{{ endif }}

		{{ if $nav.profiles }}
		<li><a id="nav-profiles-link" class="$nav.profiles.2 nav-load-page-link" href="$nav.profiles.0" title="$nav.profiles.3" >$nav.profiles.1</a></li>
		{{ endif }}

		{{ if $nav.admin }}
		<li><a id="nav-admin-link" class="$nav.admin.2 nav-load-page-link" href="$nav.admin.0" title="$nav.admin.3" >$nav.admin.1</a></li>
		{{ endif }}

		<li><a id="nav-search-link" class="$nav.search.2 nav-load-page-link" href="$nav.search.0" title="$nav.search.3" >$nav.search.1</a></li>

		{{ if $nav.apps }}
		<li><a id="nav-apps-link" class="$nav.apps.2 nav-load-page-link" href="$nav.apps.0" title="$nav.apps.3" >$nav.apps.1</a></li>
		{{ endif }}

		{{ if $nav.help }}
		<li><a id="nav-help-link" class="$nav.help.2 nav-load-page-link" target="friendika-help" href="$nav.help.0" title="$nav.help.3" >$nav.help.1</a></li>
		{{ endif }}
		
		{{ if $nav.logout }}
		<li><a id="nav-logout-link" class="$nav.logout.2" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a></li>
		{{ endif }}
	</ul>
	</div>

	{{ if $nav.notifications }}
<!--	<a id="nav-notifications-linkmenu" class="nav-link" href="$nav.notifications.0" rel="#nav-notifications-menu" title="$nav.notifications.1">$nav.notifications.1</a>-->
	<div class="nav-button-container">
	<a id="nav-notifications-linkmenu" class="nav-link" href="$nav.notifications.0" rel="#nav-notifications-menu" title="$nav.notifications.1">
	<img rel="#nav-notifications-menu" src="/view/theme/frost/images/notifications.png">
	</a>
	<span id="notify-update" class="nav-ajax-left"></span>
	<ul id="nav-notifications-menu" class="notifications-menu-popup">
		<li id="nav-notifications-see-all"><a href="$nav.notifications.all.0">$nav.notifications.all.1</a></li>
		<li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">$nav.notifications.mark.1</a></li>
		<li class="empty">$emptynotifications</li>
	</ul>
	</div>
	{{ endif }}		

<!--	<a id="contacts-menu-link" class="nav-link" href="#contacts-menu" title="Contacts">Contacts</a>-->
	<div class="nav-button-container nav-menu-link" rel="#contacts-menu-list">
	<a class="contacts-menu-link nav-link" href="$nav.contacts.0" title="Contacts">
	<img class="contacts-menu-link" src="/view/theme/frost/images/contacts.png">
	</a>
	{{ if $nav.introductions }}
	<span id="intro-update" class="nav-ajax-left"></span>
	{{ endif }}
	<ul id="contacts-menu-list" class="nav-menu-list">
		{{ if $nav.contacts }}
		<li><a id="nav-contacts-link" class="$nav.contacts.2 nav-load-page-link" href="$nav.contacts.0" title="$nav.contacts.3" >$nav.contacts.1</a><li>
		{{ endif }}

		<li><a id="nav-directory-link" class="$nav.directory.2 nav-load-page-link" href="$nav.directory.0" title="$nav.directory.3" >$nav.directory.1</a><li>

		{{ if $nav.introductions }}
		<li>
		<a id="nav-notify-link" class="$nav.introductions.2 $sel.introductions nav-load-page-link" href="$nav.introductions.0" title="$nav.introductions.3" >$nav.introductions.1</a>
		</li>
		{{ endif }}
	</ul>
	</div>

	{{ if $nav.messages }}
<!--	<a id="nav-messages-link" class="nav-link $nav.messages.2 $sel.messages nav-load-page-link" href="$nav.messages.0" title="$nav.messages.3" >$nav.messages.1</a>-->
	<div class="nav-button-container">
	<a id="nav-messages-link" class="nav-link $nav.messages.2 $sel.messages nav-load-page-link" href="$nav.messages.0" title="$nav.messages.3" >
	<img src="/view/theme/frost/images/message.png">
	</a>
	<span id="mail-update" class="nav-ajax-left"></span>
	</div>
	{{ endif }}

<!--	<a id="network-menu-link" class="nav-link" href="#network-menu" title="Network">Network</a>-->
	<div class="nav-button-container nav-menu-link" rel="#network-menu-list">
	<a class="network-menu-link nav-link" href="$nav.network.0" title="Network">
	<img class="network-menu-link" src="/view/theme/frost/images/network.png">
	</a>
	{{ if $nav.network }}
	<span id="net-update" class="nav-ajax-left"></span>
	{{ endif }}
	<ul id="network-menu-list" class="nav-menu-list">
		{{ if $nav.network }}
		<li>
		<a id="nav-network-link" class="$nav.network.2 $sel.network nav-load-page-link" href="$nav.network.0" title="$nav.network.3" >$nav.network.1</a>
		</li>
		<!--<span id="net-update" class="nav-ajax-left"></span>-->
		{{ endif }}

		{{ if $nav.home }}
		<li><a id="nav-home-link" class="$nav.home.2 $sel.home nav-load-page-link" href="$nav.home.0" title="$nav.home.3" >$nav.home.1</a></li>
		<!--<span id="home-update" class="nav-ajax-left"></span>-->
		{{ endif }}

		{{ if $nav.community }}
		<li>
		<a id="nav-community-link" class="$nav.community.2 $sel.community nav-load-page-link" href="$nav.community.0" title="$nav.community.3" >$nav.community.1</a>
		</li>
		{{ endif }}
	</ul>
	</div>
		
	</span>
	<!--<span id="nav-end"></span>-->
	<span id="banner">$banner</span>
</nav>

<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li class="{4}"><a href="{0}"><img src="{1}" height="24" width="24" alt="" />{2} <span class="notif-when">{3}</span></a></li>
</ul>
