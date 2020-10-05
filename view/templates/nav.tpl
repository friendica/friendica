
<nav>
	{{$langselector}}

	<div id="site-location">{{$sitelocation}}</div>

	{{if $nav.logout}}<a id="nav-logout-link" class="nav-link {{$nav.logout.2}}" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" >{{$nav.logout.1}}</a> {{/if}}
	{{if $nav.login}}<a id="nav-login-link" class="nav-login-link {{$nav.login.2}}" href="{{$nav.login.0}}" title="{{$nav.login.3}}" >{{$nav.login.1}}</a> {{/if}}

	<span id="nav-link-wrapper" >

	{{if $nav.register}}<a id="nav-register-link" class="nav-commlink {{$nav.register.2}} {{$sel.register}}" href="{{$nav.register.0}}" title="{{$nav.register.3}}" >{{$nav.register.1}}</a>{{/if}}
		
	{{if $nav.help}} <a id="nav-help-link" class="nav-link {{$nav.help.2}}" target="friendica-help" href="{{$nav.help.0}}" title="{{$nav.help.3}}" >{{$nav.help.1}}</a>{{/if}}

	{{if $nav.tos}} <a id="nav-tos-link" class="nav-link {{$nav.tos.2}}" href="{{$nav.tos.0}}" title="{{$nav.tos.3}}" >{{$nav.tos.1}}</a>{{/if}}
		
	{{if $nav.apps}}<a id="nav-apps-link" class="nav-link {{$nav.apps.2}}" href="{{$nav.apps.0}}" title="{{$nav.apps.3}}" >{{$nav.apps.1}}</a>{{/if}}

	<a accesskey="s" id="nav-search-link" class="nav-link {{$nav.search.2}}" href="{{$nav.search.0}}" title="{{$nav.search.3}}" >{{$nav.search.1}}</a>
	<a id="nav-directory-link" class="nav-link {{$nav.directory.2}}" href="{{$nav.directory.0}}" title="{{$nav.directory.3}}" >{{$nav.directory.1}}</a>

	{{if $nav.admin}}<a accesskey="a" id="nav-admin-link" class="nav-link {{$nav.admin.2}}" href="{{$nav.admin.0}}" title="{{$nav.admin.3}}" >{{$nav.admin.1}}</a>{{/if}}

	{{if $nav.network}}
	<a accesskey="n" id="nav-network-link" class="nav-commlink {{$nav.network.2}} {{$sel.network}}" href="{{$nav.network.0}}" title="{{$nav.network.3}}" >{{$nav.network.1}}</a>
	<span id="net-update" class="nav-ajax-left"></span>
	{{/if}}
	{{if $nav.home}}
	<a accesskey="p" id="nav-home-link" class="nav-commlink {{$nav.home.2}} {{$sel.home}}" href="{{$nav.home.0}}" title="{{$nav.home.3}}" >{{$nav.home.1}}</a>
	<span id="home-update" class="nav-ajax-left"></span>
	{{/if}}
	{{if $nav.community}}
	<a accesskey="c" id="nav-community-link" class="nav-commlink {{$nav.community.2}} {{$sel.community}}" href="{{$nav.community.0}}" title="{{$nav.community.3}}" >{{$nav.community.1}}</a>
	{{/if}}
	{{if $nav.introductions}}
	<a id="nav-notification-link" class="nav-commlink {{$nav.introductions.2}} {{$sel.introductions}}" href="{{$nav.introductions.0}}" title="{{$nav.introductions.3}}" >{{$nav.introductions.1}}</a>
	<span id="intro-update" class="nav-ajax-left"></span>
	{{/if}}
	{{if $nav.messages}}
	<a id="nav-messages-link" class="nav-commlink {{$nav.messages.2}} {{$sel.messages}}" href="{{$nav.messages.0}}" title="{{$nav.messages.3}}" >{{$nav.messages.1}}</a>
	<span id="mail-update" class="nav-ajax-left"></span>
	{{/if}}



	{{if $nav.delegation}}<a id="nav-delegation-link" class="nav-commlink {{$nav.delegation.2}} {{$sel.delegation}}" href="{{$nav.delegation.0}}" title="{{$nav.delegation.3}}">{{$nav.delegation.1}}</a>{{/if}}


		{{if $nav.notifications}}
			<a accesskey="f" id="nav-notifications-linkmenu" class="nav-commlink" href="{{$nav.notifications.0}}" rel="#nav-notifications-menu" title="{{$nav.notifications.1}}">{{$nav.notifications.1}}</a>
				<span id="notification-update" class="nav-ajax-left"></span>
				<ul id="nav-notificationns-menu" class="menu-popup">
					<li id="nav-notifications-see-all"><a href="{{$nav.notifications.all.0}}">{{$nav.notifications.all.1}}</a></li>
					{{if $nav.notifications.pause}}<li id="nav-notifications-pause"><a href="#" onclick="notificationPause(); return false;">{{$nav.notifications.pause}}</a></li>{{/if}}
					{{if $nav.notifications.unpause}}<li id="nav-notifications-unpause"><a href="#" onclick="notificationUnpause(); return false;">{{$nav.notifications.unpause}}</a></li>{{/if}}
					<li id="nav-notifications-mark-all"><a href="#" onclick="notificationMarkAll(); return false;">{{$nav.notifications.mark.3}}</a></li>
					<li class="empty">{{$emptynotifications}}</li>
				</ul>
		{{/if}}		

	{{if $nav.settings}}<a id="nav-settings-link" class="nav-link {{$nav.settings.2}}" href="{{$nav.settings.0}}" title="{{$nav.settings.3}}">{{$nav.settings.1}}</a>{{/if}}
	{{if $nav.profiles}}<a id="nav-profiles-link" class="nav-link {{$nav.profiles.2}}" href="{{$nav.profiles.0}}" title="{{$nav.profiles.3}}" >{{$nav.profiles.1}}</a>{{/if}}

	{{if $nav.contacts}}<a id="nav-contacts-link" class="nav-link {{$nav.contacts.2}}" href="{{$nav.contacts.0}}" title="{{$nav.contacts.3}}" >{{$nav.contacts.1}}</a>{{/if}}
	</span>
	<span id="nav-end"></span>
	<span id="banner">{{$banner nofilter}}</span>
</nav>

<ul id="nav-notifications-template" style="display:none;" rel="template">
	<li class="{4}"><a href="{0}" title="{5}"><img data-src="{1}" height="24" width="24" alt="" />{2} <span class="notif-when">{3}</span></a></li>
</ul>
