<?php
/*
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Name: Dark
 * Licence: AGPL
 * Author: Hypolite Petovan <hypolite@friendica.mrpetovan.com>
 * Overwrites: nav_bg, nav_icon_color, link_color, background_color, background_image, contentbg_transp
 * Accented: yes
 */

require_once 'view/theme/larpnet_notifications/php/PHPColors/Color.php';

$accentColor = new Color($scheme_accent);

$menu_background_hover_color = '#' . $accentColor->darken(20);
switch ($scheme_accent) {
	default:
		$link_color = '#' . $accentColor->lighten(25);
}
$nav_icon_color = '#' . $accentColor->lighten(40);
$nav_icon_hover_color = '#' . $accentColor->darken(20);

switch ($scheme_accent) {
	case LARPNET_SCHEME_ACCENT_GREEN:
	case LARPNET_SCHEME_ACCENT_RED:
		$nav_bg = '#' . $accentColor->darken(27);
		$background_color = '#' . $accentColor->darken(29);
		break;
	default:
		$nav_bg = '#' . $accentColor->darken(30);
		$background_color = '#' . $accentColor->darken(33);
}

$contentbg_transp = 4;
$font_color = '#e4e4e4';
$font_color_darker = '#dcdcdc';
$font_color_lighter = '#555555';
$background_image = '';
