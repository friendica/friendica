<nav>
	$langselector

	<span id="banner">$banner</span>

	<div id="notifications">	
		{{ if $nav.network }}<a id="net-update" class="nav-ajax-update" href="$nav.network.0" title="$nav.network.1"></a>{{ endif }}
		{{ if $nav.home }}<a id="home-update" class="nav-ajax-update" href="$nav.home.0" title="$nav.home.1"></a>{{ endif }}
<!--		{{ if $nav.notifications }}<a id="intro-update" class="nav-ajax-update" href="$nav.notifications.0" title="$nav.notifications.1"></a>{{ endif }} -->
		{{ if $nav.introductions }}<a id="intro-update" class="nav-ajax-update" href="$nav.introductions.0" title="$nav.introductions.1"></a>{{ endif }}
		{{ if $nav.messages }}<a id="mail-update" class="nav-ajax-update" href="$nav.messages.0" title="$nav.messages.1"></a>{{ endif }}
		{{ if $nav.notifications }}<a rel="#nav-notifications-menu" id="notify-update" class="nav-ajax-update" href="$nav.notifications.0"  title="$nav.notifications.1"></a>{{ endif }}

		<ul id="nav-notifications-menu" class="menu-popup">
			<li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">$nav.notifications.mark.1</a></li>
			<li id="nav-notifications-see-all"><a href="$nav.notifications.all.0">$nav.notifications.all.1</a></li>
			<li class="empty">$emptynotifications</li>
		</ul>
	</div>
	
	<div id="user-menu" >
		<a id="user-menu-label" onclick="openClose('user-menu-popup'); return false" href="$nav.home.0">$sitelocation</a>
		
		<ul id="user-menu-popup" 
			 onmouseover="if (typeof tmenu != 'undefined') clearTimeout(tmenu); openMenu('user-menu-popup')" 
			 onmouseout="tmenu=setTimeout('closeMenu(\'user-menu-popup\');',200)">

			{{ if $nav.register }}<li><a id="nav-register-link" class="nav-commlink $nav.register.2" href="$nav.register.0">$nav.register.1</a></li>{{ endif }}
			
			{{ if $nav.home }}<li><a id="nav-home-link" class="nav-commlink $nav.home.2" href="$nav.home.0">$nav.home.1</a></li>{{ endif }}
		
			{{ if $nav.network }}<li><a id="nav-network-link" class="nav-commlink $nav.network.2" href="$nav.network.0">$nav.network.1</a></li>{{ endif }}
		
				{{ if $nav.community }}
				<li><a id="nav-community-link" class="nav-commlink $nav.community.2" href="$nav.community.0">$nav.community.1</a></li>
				{{ endif }}

			<li><a id="nav-search-link" class="nav-link $nav.search.2" href="$nav.search.0">$nav.search.1</a></li>
			<li><a id="nav-directory-link" class="nav-link $nav.directory.2" href="$nav.directory.0">$nav.directory.1</a></li>
			{{ if $nav.apps }}<li><a id="nav-apps-link" class="nav-link $nav.apps.2" href="$nav.apps.0">$nav.apps.1</a></li>{{ endif }}
			
			{{ if $nav.notifications }}<li><a id="nav-notify-link" class="nav-commlink nav-sep $nav.notifications.2" href="$nav.notifications.0">$nav.notifications.1</a></li>{{ endif }}
			{{ if $nav.messages }}<li><a id="nav-messages-link" class="nav-commlink $nav.messages.2" href="$nav.messages.0">$nav.messages.1</a></li>{{ endif }}
			{{ if $nav.contacts }}<li><a id="nav-contacts-link" class="nav-commlink $nav.contacts.2" href="$nav.contacts.0">$nav.contacts.1</a></li>{{ endif }}
		
			{{ if $nav.profiles }}<li><a id="nav-profiles-link" class="nav-commlink nav-sep $nav.profiles.2" href="$nav.profiles.0">$nav.profiles.1</a></li>{{ endif }}
			{{ if $nav.settings }}<li><a id="nav-settings-link" class="nav-commlink $nav.settings.2" href="$nav.settings.0">$nav.settings.1</a></li>{{ endif }}
			
			{{ if $nav.manage }}<li><a id="nav-manage-link" class="nav-commlink $nav.manage.2" href="$nav.manage.0">$nav.manage.1</a></li>{{ endif }}
		
			{{ if $nav.admin }}<li><a id="nav-admin-link" class="nav-commlink $nav.admin.2" href="$nav.admin.0">$nav.admin.1</a></li>{{ endif }}
			
			{{ if $nav.help }}<li><a id="nav-help-link" class="nav-link $nav.help.2" href="$nav.help.0">$nav.help.1</a></li>{{ endif }}

			{{ if $nav.login }}<li><a id="nav-login-link" class="nav-link $nav.login.2" href="$nav.login.0">$nav.login.1</a></li> {{ endif }}
			{{ if $nav.logout }}<li><a id="nav-logout-link" class="nav-commlink nav-sep $nav.logout.2" href="$nav.logout.0">$nav.logout.1</a></li> {{ endif }}
		</ul>
	</div>
</nav>


<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li class="{4}"><a href="{0}"><img src="{1}" height="24" width="24" alt="" />{2} <span class="notif-when">{3}</span></a></li>
</ul>


