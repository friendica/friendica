{#
 # LOGIN/REGISTER
 #}
<center>
{# Use nested if's since the Friendica template engine doesn't support AND or OR in if statements #}
{{ if $nav.login }}
<div id="navigation-login-wrapper" >
{{ else }}
{{ if $nav.register }}
<div id="navigation-login-wrapper" >
{{ endif }}
{{ endif }}
{{ if $nav.login }}<a id="navigation-login-link" class="navigation-link $nav.login.2" href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a><br/> {{ endif }}
{{ if $nav.register }}<a id="navigation-register-link" class="navigation-link $nav.register.2 $sel.register" href="$nav.register.0" title="$nav.register.3" >$nav.register.1</a><br/>{{ endif }}
{{ if $nav.login }}
</div>
{{ else }}
{{ if $nav.register }}
</div>
{{ endif }}
{{ endif }}

{#
 # NETWORK/HOME
 #}
{{ if $nav.network }}
<div id="navigation-network-wrapper" >
{{ else }}
{{ if $nav.home }}
<div id="navigation-network-wrapper" >
{{ else }}
{{ if $nav.community }}
<div id="navigation-network-wrapper" >
{{ endif }}
{{ endif }}
{{ endif }}
{{ if $nav.network }}
<a id="navigation-network-link" class="navigation-link navigation-commlink $nav.network.2 $sel.network" href="$nav.network.0" title="$nav.network.3" >$nav.network.1</a><br/>
<a class="navigation-link navigation-commlink" href="$nav.net_reset.0" title="$nav.net_reset.3">$nav.net_reset.1</a><br/>
{{ endif }}
{{ if $nav.home }}
<a id="navigation-home-link" class="navigation-link navigation-commlink $nav.home.2 $sel.home" href="$nav.home.0" title="$nav.home.3" >$nav.home.1</a><br/>
{{ endif }}
{{ if $nav.community }}
<a id="navigation-community-link" class="navigation-link navigation-commlink $nav.community.2 $sel.community" href="$nav.community.0" title="$nav.community.3" >$nav.community.1</a><br/>
{{ endif }}
{{ if $nav.network }}
</div>
{{ else }}
{{ if $nav.home }}
</div>
{{ else }}
{{ if $nav.community }}
</div>
{{ endif }}
{{ endif }}
{{ endif }}

{#
 # PRIVATE MESSAGES
 #}
{{ if $nav.messages }}
<div id="navigation-messages-wrapper">
<a id="navigation-messages-link" class="navigation-link navigation-commlink $nav.messages.2 $sel.messages" href="$nav.messages.0" title="$nav.messages.3" >$nav.messages.1</a><br/>
</div>
{{ endif }}

	
{#
 # CONTACTS
 #}
<div id="navigation-contacts-wrapper">
{{ if $nav.contacts }}<a id="navigation-contacts-link" class="navigation-link $nav.contacts.2" href="$nav.contacts.0" title="$nav.contacts.3" >$nav.contacts.1</a><br/>{{ endif }}
<a id="navigation-directory-link" class="navigation-link $nav.directory.2" href="$nav.directory.0" title="$nav.directory.3" >$nav.directory.1</a><br/>
{{ if $nav.introductions }}
<a id="navigation-notify-link" class="navigation-link navigation-commlink $nav.introductions.2 $sel.introductions" href="$nav.introductions.0" title="$nav.introductions.3" >$nav.introductions.1</a><br/>
{{ endif }}
</div>

{#
 # NOTIFICATIONS
 #}
{{ if $nav.notifications }}
<div id="navigation-notifications-wrapper">
<a id="navigation-notifications-link" class="navigation-link navigation-commlink" href="$nav.notifications.0" rel="#navigation-notifications-menu" title="$nav.notifications.1">$nav.notifications.1</a><br/>
</div>
{{ endif }}		

{#
 # MISCELLANEOUS
 #}
<div id="navigation-misc-wrapper">
{{ if $nav.settings }}<a id="navigation-settings-link" class="navigation-link $nav.settings.2" href="$nav.settings.0" title="$nav.settings.3">$nav.settings.1</a><br/>{{ endif }}
{{ if $nav.manage }}<a id="navigation-manage-link" class="navigation-link navigation-commlink $nav.manage.2 $sel.manage" href="$nav.manage.0" title="$nav.manage.3">$nav.manage.1</a><br/>{{ endif }}
{{ if $nav.profiles }}<a id="navigation-profiles-link" class="navigation-link $nav.profiles.2" href="$nav.profiles.0" title="$nav.profiles.3" >$nav.profiles.1</a><br/>{{ endif }}
{{ if $nav.admin }}<a id="navigation-admin-link" class="navigation-link $nav.admin.2" href="$nav.admin.0" title="$nav.admin.3" >$nav.admin.1</a><br/>{{ endif }}
<a id="navigation-search-link" class="navigation-link $nav.search.2" href="$nav.search.0" title="$nav.search.3" >$nav.search.1</a><br/>
{{ if $nav.apps }}<a id="navigation-apps-link" class="navigation-link $nav.apps.2" href="$nav.apps.0" title="$nav.apps.3" >$nav.apps.1</a><br/>{{ endif }}
{{ if $nav.help }} <a id="navigation-help-link" class="navigation-link $nav.help.2" target="friendica-help" href="$nav.help.0" title="$nav.help.3" >$nav.help.1</a><br/>{{ endif }}
</div>

{{ if $nav.logout }}<a id="navigation-logout-link" class="navigation-link $nav.logout.2" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a><br/>{{ endif }}
</center>
