<header>
	<div id="site-location">$sitelocation</div>
	<div id="banner">$banner</div>
</header>
<nav>
			
			
	<ul>
			
			
			{{ if $nav.network }}
			<li id="nav-network-link" class="nav-menu-icon">
				<a class="$nav.network.2" href="$nav.network.0" title="$nav.network.3" >
				<span class="icon notifications">Benachrichtigungen</span></a>
				<span id="net-update" class="nav-notify"></span>
			</li>
		    {{ endif }}
	
			{{ if $nav.contacts }}
			<li class="nav-menu-icon" id="nav-contacts-linkmenu">
				<a href="$nav.contacts.0" rel="#nav-contacts-menu" title="$nav.contacts.1">
				<span class="icon contacts">$nav.contacts.1</span></a>
				<span id="intro-update" class="nav-notify"></span>
				<ul id="nav-contacts-menu" class="menu-popup">
					<li id="nav-contacts-see-intro"><a href="$nav.notifications.0">$nav.introductions.1</a><span id="intro-update" class="nav-notify"></span></li>
					<li id="nav-contacts-all"><a href="contacts">$nav.contacts.1</a></li> 
				</ul>
			</li>	
			{{ endif }}
			
			{{ if $nav.messages }}
			<li  id="nav-messages-linkmenu" class="nav-menu-icon">
			  <a href="$nav.messages.0" rel="#nav-messages-menu" title="$nav.messages.1">
			  <span class="icon messages">$nav.messages.1</span></a>
				<span id="mail-update" class="nav-notify"></span>
				<ul id="nav-messages-menu" class="menu-popup">
					<li id="nav-messages-see-all"><a href="$nav.messages.0">$nav.messages.1</a></li>
					<li id="nav-messages-see-all"><a href="$nav.messages.inbox.0">$nav.messages.inbox.1</a></li>
					<li id="nav-messages-see-all"><a href="$nav.messages.outbox.0">$nav.messages.outbox.1</a></li>
					<li id="nav-messages-see-all"><a href="$nav.messages.new.0">$nav.messages.new.1</a></li>
				</ul>
			</li>		
			{{ endif }}
		
      {{ if $nav.notifications }}
			<li  id="nav-notifications-linkmenu" class="nav-menu-icon"><a href="$nav.notifications.0" rel="#nav-notifications-menu" title="$nav.notifications.1"><span class="icon notify">$nav.notifications.1</span></a>
				<span id="notify-update" class="nav-notify"></span>
				<ul id="nav-notifications-menu" class="menu-popup">
					<li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">$nav.notifications.mark.1</a></li>
					<li id="nav-notifications-see-all"><a href="$nav.notifications.all.0">$nav.notifications.all.1</a></li>
					<li class="empty">$emptynotifications</li>
				</ul>
			</li>		
		{{ endif }}	
			
		{{ if $nav.search}}
		<li id="search-box">
			<form method="get" action="$nav.search.0">
				<input id="search-text" class="nav-menu-search" type="text" value="" name="search">
			</form>
		</li>		
		{{ endif }}	
		
		<li id="nav-site-linkmenu" class="nav-menu-icon"><a href="#" rel="#nav-site-menu"><span class="icon s22 gear">Site</span></a>
			<ul id="nav-site-menu" class="menu-popup">
				{{ if $nav.manage }}<li><a class="$nav.manage.2" href="$nav.manage.0" title="$nav.manage.3">$nav.manage.1</a></li>{{ endif }}				

				{{ if $nav.help }} <li><a class="$nav.help.2" target="friendika-help" href="$nav.help.0" title="$nav.help.3" >$nav.help.1</a></li>{{ endif }}


										<li ><a class="$nav.community.2" href="$nav.community.0" title="$nav.community.3" >$nav.community.1</a></li>


										<li><a class="$nav.search.2" href="$nav.search.0" title="$nav.search.3" >$nav.search.1</a></li>
										
										<li><a class="$nav.search.2" href="friendica" title="Site Info / Impressum" >Info/Impressum</a></li>

				{{ if $nav.settings }}<li><a class="menu-sep $nav.settings.2" href="$nav.settings.0" title="$nav.settings.3">$nav.settings.1</a></li>{{ endif }}
				{{ if $nav.admin }}<li><a class="$nav.admin.2" href="$nav.admin.0" title="$nav.admin.3" >$nav.admin.1</a></li>{{ endif }}

				{{ if $nav.logout }}<li><a class="menu-sep $nav.logout.2" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a></li>{{ endif }}

				
			</ul>		
		</li>
		
		
		{{ if $nav.directory }}
		<li id="nav-directory-link" class="nav-menu $sel.directory">
			<a class="$nav.directory.2" href="$nav.directory.0" title="$nav.directory.3" >$nav.directory.1</a>
		</li>
		{{ endif }}
		
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
		
      {{ if $nav.home }}
			<li id="nav-home-link" class="nav-menu $sel.home">
				<a class="$nav.home.2" href="$nav.home.0" title="$nav.home.3" >$nav.home.1</a>
				<span id="home-update" class="nav-notify"></span>
			</li>
		{{ endif }}		
		
		{{ if $userinfo }}
			<li id="nav-user-linkmenu" class="nav-menu-icon"><a href="#" rel="#nav-user-menu" title="$sitelocation"><img src="$userinfo.icon" alt="$userinfo.name"></a>
				<ul id="nav-user-menu" class="menu-popup">
					{{ for $nav.usermenu as $usermenu }}
						<li><a class="$usermenu.2" href="$usermenu.0" title="$usermenu.3">$usermenu.1</a></li>
					{{ endfor }}
					
					{{ if $nav.profiles }}<li><a class="menu-sep $nav.profiles.2" href="$nav.profiles.0" title="$nav.profiles.3" >$nav.profiles.3</a></li>{{ endif }}
					{{ if $nav.notifications }}<li><a class="$nav.notifications.2" href="$nav.notifications.0" title="$nav.notifications.3" >$nav.notifications.1</a></li>{{ endif }}
					{{ if $nav.messages }}<li><a class="$nav.messages.2" href="$nav.messages.0" title="$nav.messages.3" >$nav.messages.1</a></li>{{ endif }}
					{{ if $nav.contacts }}<li><a class="$nav.contacts.2" href="$nav.contacts.0" title="$nav.contacts.3" >$nav.contacts.1</a></li>{{ endif }}	
				</ul>
			</li>
		{{ endif }}
		
					{{ if $nav.login }}
					<li id="nav-home-link" class="nav-menu $sel.home">
						<a class="$nav.login.2" href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a>
					<li>
					{{ endif }}
		
		
		
	</ul>	


	
</nav>


<div style="position: fixed; bottom: 5px; right: 10px;"><a href="javascript:scroll(0,0); "><img src="view/theme/diabook/icons/scroll_top.png" title="scroll to top"></a></div>
<div style="position: fixed; bottom: 3px; left: 25px;">$langselector</div>
<div style="position: fixed; bottom: 23px; left: 5px;"><a href="http://pad.toktan.org/p/diabook" target="blank" ><img src="view/theme/diabook/icons/bluebug.png" title="report bugs for the theme diabook"/></a></div>



<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li class="{4}"><a href="{0}"><img src="{1}">{2} <span class="notif-when">{3}</span></a></li>
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
