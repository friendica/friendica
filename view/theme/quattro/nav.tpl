<header>
	{# $langselector #}

	<div id="site-location">$sitelocation</div>
	<div id="banner">$banner</div>
</header>
<nav>
	<ul>
		{{ if $userinfo }}
			<li id="nav-user-linkmenu" class="nav-menu-icon"><a href="#" rel="#nav-user-menu" title="$sitelocation"><img src="$userinfo.icon" alt="$userinfo.name"></a>
				<ul id="nav-user-menu" class="menu-popup">
					{{ for $nav.usermenu as $usermenu }}
						<li><a class="$usermenu.2" href="$usermenu.0" title="$usermenu.3">$usermenu.1</a></li>
					{{ endfor }}
					
					{{ if $nav.notifications }}<li><a class="$nav.notifications.2" href="$nav.notifications.0" title="$nav.notifications.3" >$nav.notifications.1</a></li>{{ endif }}
					{{ if $nav.messages }}<li><a class="$nav.messages.2" href="$nav.messages.0" title="$nav.messages.3" >$nav.messages.1</a></li>{{ endif }}
					{{ if $nav.contacts }}<li><a class="$nav.contacts.2" href="$nav.contacts.0" title="$nav.contacts.3" >$nav.contacts.1</a></li>{{ endif }}	
				</ul>
			</li>
		{{ endif }}
		
		{{ if $nav.community }}
			<li id="nav-community-link" class="nav-menu $sel.community">
				<a class="$nav.community.2" href="$nav.community.0" title="$nav.community.3" >$nav.community.1</a>
			</li>
		{{ endif }}
		
		{{ if $nav.network }}
			<li id="nav-network-link" class="nav-menu $sel.network">
				<a class="$nav.network.2" href="$nav.network.0" title="$nav.network.3" >$nav.network.1</a>
				<span id="net-update" class="nav-notify"></span>
			</li>
		{{ endif }}
		{{ if $nav.home }}
			<li id="nav-home-link" class="nav-menu $sel.home">
				<a class="$nav.home.2" href="$nav.home.0" title="$nav.home.3" >$nav.home.1</a>
				<span id="home-update" class="nav-notify"></span>
			</li>
		{{ endif }}
		
		{{ if $nav.notifications }}
			<li  id="nav-notifications-linkmenu" class="nav-menu-icon"><a href="$nav.notifications.0" rel="#nav-notifications-menu" title="$nav.notifications.1"><span class="icon s22 notify">$nav.notifications.1</span></a>
				<span id="notify-update" class="nav-notify"></span>
				<ul id="nav-notifications-menu" class="menu-popup">
					<li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">$nav.notifications.mark.1</a></li>
					<li id="nav-notifications-see-all"><a href="$nav.notifications.all.0">$nav.notifications.all.1</a></li>
					<li class="empty">$emptynotifications</li>
				</ul>
			</li>		
		{{ endif }}		
		
		<li id="nav-site-linkmenu" class="nav-menu-icon"><a href="#" rel="#nav-site-menu"><span class="icon s22 gear">Site</span></a>
			<ul id="nav-site-menu" class="menu-popup">
				{{ if $nav.manage }}<li><a class="$nav.manage.2" href="$nav.manage.0" title="$nav.manage.3">$nav.manage.1</a></li>{{ endif }}				

				{{ if $nav.settings }}<li><a class="$nav.settings.2" href="$nav.settings.0" title="$nav.settings.3">$nav.settings.1</a></li>{{ endif }}
				{{ if $nav.admin }}<li><a class="$nav.admin.2" href="$nav.admin.0" title="$nav.admin.3" >$nav.admin.1</a></li>{{ endif }}

				{{ if $nav.logout }}<li><a class="menu-sep $nav.logout.2" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a></li>{{ endif }}
				{{ if $nav.login }}<li><a class="$nav.login.2" href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a><li>{{ endif }}
			</ul>		
		</li>
		
		{{ if $nav.help }} 
		<li id="nav-help-link" class="nav-menu $sel.help">
			<a class="$nav.help.2" target="friendika-help" href="$nav.help.0" title="$nav.help.3" >$nav.help.1</a>
		</li>
		{{ endif }}

		<li id="nav-search-link" class="nav-menu $sel.search">
			<a class="$nav.search.2" href="$nav.search.0" title="$nav.search.3" >$nav.search.1</a>
		</li>
		<li id="nav-directory-link" class="nav-menu $sel.directory">
			<a class="$nav.directory.2" href="$nav.directory.0" title="$nav.directory.3" >$nav.directory.1</a>
		</li>
		
		{{ if $nav.apps }}
			<li id="nav-apps-link" class="nav-menu $sel.apps">
				<a class=" $nav.apps.2" href="#" rel="#nav-apps-menu" title="$nav.apps.3" >$nav.apps.1</a>
				<ul id="nav-apps-menu" class="menu-popup">
					{{ for $apps as $ap }}
					<li>$ap</li>
					{{ endfor }}
				</ul>
			</li>
		{{ endif }}
	</ul>

</nav>
<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li><a href="{0}"><img src="{1}">{2} <span class="notif-when">{3}</span></a></li>
</ul>

{#

{{ if $nav.logout }}<a id="nav-logout-link" class="nav-link $nav.logout.2" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a> {{ endif }}
{{ if $nav.login }}<a id="nav-login-link" class="nav-login-link $nav.login.2" href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a> {{ endif }}

<span id="nav-link-wrapper" >

{{ if $nav.register }}<a id="nav-register-link" class="nav-commlink $nav.register.2" href="$nav.register.0" title="$nav.register.3" >$nav.register.1</a>{{ endif }}
	
<a id="nav-help-link" class="nav-link $nav.help.2" target="friendika-help" href="$nav.help.0" title="$nav.help.3" >$nav.help.1</a>
	
{{ if $nav.apps }}<a id="nav-apps-link" class="nav-link $nav.apps.2" href="$nav.apps.0" title="$nav.apps.3" >$nav.apps.1</a>{{ endif }}

<a id="nav-search-link" class="nav-link $nav.search.2" href="$nav.search.0" title="$nav.search.3" >$nav.search.1</a>
<a id="nav-directory-link" class="nav-link $nav.directory.2" href="$nav.directory.0" title="$nav.directory.3" >$nav.directory.1</a>

{{ if $nav.admin }}<a id="nav-admin-link" class="nav-link $nav.admin.2" href="$nav.admin.0" title="$nav.admin.3" >$nav.admin.1</a>{{ endif }}

{{ if $nav.notifications }}
<a id="nav-notify-link" class="nav-commlink $nav.notifications.2" href="$nav.notifications.0" title="$nav.notifications.3" >$nav.notifications.1</a>
<span id="notify-update" class="nav-ajax-left"></span>
{{ endif }}
{{ if $nav.messages }}
<a id="nav-messages-link" class="nav-commlink $nav.messages.2" href="$nav.messages.0" title="$nav.messages.3" >$nav.messages.1</a>
<span id="mail-update" class="nav-ajax-left"></span>
{{ endif }}

{{ if $nav.manage }}<a id="nav-manage-link" class="nav-commlink $nav.manage.2" href="$nav.manage.0" title="$nav.manage.3">$nav.manage.1</a>{{ endif }}

{{ if $nav.settings }}<a id="nav-settings-link" class="nav-link $nav.settings.2" href="$nav.settings.0" title="$nav.settings.3">$nav.settings.1</a>{{ endif }}
{{ if $nav.profiles }}<a id="nav-profiles-link" class="nav-link $nav.profiles.2" href="$nav.profiles.0" title="$nav.profiles.3" >$nav.profiles.1</a>{{ endif }}


</span>
<span id="nav-end"></span>
<span id="banner">$banner</span>
#}
