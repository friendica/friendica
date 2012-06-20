<nav id="pagenav">

<div id="banner">$banner</div>
<div id="site-location">$sitelocation</div>

<a name="top" id="top"></a>
<div id="nav-floater">
    <ul id="nav-buttons">
    {{ if $nav.login }}
    <li><a id="nav-login-link" class="nav-login-link $nav.login.2"
            href="$nav.login.0" title="$nav.login.3" >$nav.login.1</a></li>
    {{ endif }}
    {{ if $nav.home }}
    <li><a id="nav-home-link" class="nav-link $nav.home.2"
            href="$nav.home.0" title="$nav.home.1">$nav.home.1</a></li>
    {{ endif }}
    {{ if $nav.network }}
    <li><a id="nav-network-link" class="nav-link $nav.network.2"
            href="$nav.network.0" title="$nav.network.1">$nav.network.1</a></li>
    {{ endif }}
    {{ if $nav.notifications }}
    <li><a id="nav-notifications-linkmenu" class="nav-link $nav.notifications.2"
            href="$nav.notifications.0"
            rel="#nav-notifications-menu" title="$nav.notifications.1">$nav.notifications.1</a></li>
        <ul id="nav-notifications-menu" class="menu-popup">
            <li id="nav-notifications-see-all"><a href="$nav.notifications.all.0">$nav.notifications.all.1</a></li>
            <li id="nav-notifications-mark-all"><a href="#" onclick="notifyMarkAll(); return false;">$nav.notifications.mark.1</a></li>
            <li class="empty">$emptynotifications</li>
        </ul>
    {{ endif }}
    {{ if $nav.messages }}
    <li><a id="nav-messages-link" class="nav-link $nav.messages.2"
            href="$nav.messages.0" title="$nav.messages.1">$nav.messages.1</a></li>
    {{ endif }}
    {{ if $nav.community }}
    <li><a id="nav-community-link" class="nav-link $nav.community.2"
            href="$nav.community.0" title="$nav.community.1">$nav.community.1</a></li>
    {{ endif }}
    <li><a id="nav-directory-link" class="nav-link $nav.directory.2"
            href="$nav.directory.0" title="$nav.directory.1">$nav.directory.1</a></li>
    <li><a id="nav-search-link" class="nav-link $nav.search.2"
            href="$nav.search.0" title="$nav.search.1">$nav.search.1</a></li>
    {{ if $nav.apps }}
    <li><a id="nav-apps-link" class="nav-link $nav.apps.2"
        href="$nav.apps.0" title="$nav.apps.1">$nav.apps.1</a></li>
    {{ endif }}
    {{ if $nav.help }}
    <li><a id="nav-help-link" class="nav-link $nav.help.2"
            href="$nav.help.0" title="$nav.help.1">$nav.help.1</a></li>
    {{ endif }}
    </ul>

    <div id="user-menu">
        <a id="user-menu-label" onclick="openClose('user-menu-popup'); return false;" href="$nav.home.0"><span class="">User Menu</span></a>
        <ul id="user-menu-popup"
            onmouseover="if (typeof tmenu != 'undefined') clearTimeout(tmenu); openMenu('user-menu-popup')"
            onmouseout="tmenu=setTimeout('closeMenu(\'user-menu-popup\');',200)">

        {{ if $nav.register }}
        <li>
        <a id="nav-register-link" class="nav-commlink $nav.register.2" href="$nav.register.0" title="$nav.register.1"></a>
        </li>
        {{ endif }}
        {{ if $nav.contacts }}
        <li><a id="nav-contacts-link" class="nav-commlink $nav.contacts.2" href="$nav.contacts.0" title="$nav.contacts.1">$nav.contacts.1</a></li>
        {{ endif }}
        {{ if $nav.profiles }}
        <li><a id="nav-profiles-link" class="nav-commlink $nav.profiles.2" href="$nav.profiles.0" title="$nav.profiles.1">$nav.profiles.1</a></li>
        {{ endif }}
        {{ if $nav.settings }}
        <li><a id="nav-settings-link" class="nav-commlink $nav.settings.2" href="$nav.settings.0" title="$nav.settings.1">$nav.settings.1</a></li>
        {{ endif }}
        {{ if $nav.login }}
        <li><a id="nav-login-link" class="nav-commlink $nav.login.2" href="$nav.login.0" title="$nav.login.1">$nav.login.1</a></li>
        {{ endif }}
        {{ if $nav.logout }}
        <li><a id="nav-logout-link" class="nav-commlink $nav.logout.2" href="$nav.logout.0" title="$nav.logout.3" >$nav.logout.1</a></li>
        {{ endif }}
        </ul>
    </div>

    <ul id="nav-buttons-2">
    {{ if $nav.introductions }}
    <li><a id="nav-intro-link" class="nav-link $nav.introductions.2 $sel.introductions" href="$nav.introductions.0" title="$nav.introductions.3" >$nav.introductions.1</a></li>
    {{ endif }}
    {{ if $nav.admin }}
    <li><a id="nav-admin-link" class="nav-link $nav.admin.2" href="$nav.admin.0" title="$nav.admin.1">$nav.admin.1</a></li>
    {{ endif }}
    {{ if $nav.manage }}
    <li><a id="nav-manage-link" class="nav-link $nav.manage.2" href="$nav.manage.0" title="$nav.manage.1">$nav.manage.1</a></li>
    {{ endif }}
    </ul>

{{ if $userinfo }}
        <ul id="nav-user-menu" class="menu-popup">
            {{ for $nav.usermenu as $usermenu }}
                <li>
                    <a class="$usermenu.2" href="$usermenu.0" title="$usermenu.3">$usermenu.1</a>
                </li>
            {{ endfor }}
        </ul>
{{ endif }}

    <div id="notifications">
        {{ if $nav.home }}
        <a id="home-update" class="nav-ajax-left" href="$nav.home.0" title="$nav.home.1"></a>
        {{ endif }}
        {{ if $nav.network }}
        <a id="net-update" class="nav-ajax-left" href="$nav.network.0" title="$nav.network.1"></a>
        {{ endif }}
        {{ if $nav.notifications }}
        <a id="notify-update" class="nav-ajax-left" href="$nav.notifications.0" title="$nav.notifications.1"></a>
        {{ endif }}
        {{ if $nav.messages }}
        <a id="mail-update" class="nav-ajax-left" href="$nav.messages.0" title="$nav.messages.1"></a>
        {{ endif }}
        {{if $nav.introductions }}
        <a id="intro-update" class="nav-ajax-left" href="$nav.introductions.0"></a>
        {{ endif }}
    </div>
</div>
    <a href="#" class="floaterflip"></a>
</nav>

<div id="lang-sel-wrap">
$langselector
</div>

<div id="scrollup">
<a href="#top"><img src="view/theme/dispy/icons/scroll_top.png"
    alt="back to top" title="Back to top" /></a>
</div>

<div class="search-box">
    <form method="get" action="$nav.search.0">
        <input id="mini-search-text" class="nav-menu-search" type="search" placeholder="Search" value="" id="search" name="search" />
    </form>
</div>

<ul id="nav-notifications-template" style="display:none;" rel="template">
    <li class="{4}">
    <a href="{0}"><img src="{1}" height="24" width="24" alt="" />{2} <span class="notif-when">{3}</span></a>
    </li>
</ul>

