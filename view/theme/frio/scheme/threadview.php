<?php
/*
 * Name: Light
 * Licence: AGPL
 * Author: Hypolite Petovan <hypolite@friendica.mrpetovan.com>
 * Overwrites: nav_bg, nav_icon_color, link_color, background_color, background_image, login_bg_color, contentbg_transp
 * Accented: yes
 */

require_once 'view/theme/frio/php/PHPColors/Color.php';

$accentColor = new Color($scheme_accent);

$nav_bg = '#' . $accentColor->darken(10);
$menu_background_hover_color = '#' . $accentColor->darken(5);
$nav_icon_color = "#fff";
$link_color = '#' . $accentColor->getHex();
$background_color = "#ededed";
$login_bg_color = "#ededed";
$contentbg_transp = 100;
$background_image = '';
