<?php

/*
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Name: Gnome
 * Licence: AGPL
 * Author: Daniel Buck <tealk@rollenspiel.monster> tealk@friendica.rollenspiel.monster
 * Overwrites: nav_bg, nav_icon_color, link_color, background_color, background_image, login_bg_color, contentbg_transp
 * Accented: yes
 */

require_once 'view/theme/frio/php/PHPColors/Color.php';

$accentColor = new Color($scheme_accent);

$menu_background_hover_color = '#' . $accentColor->darken(10);
$nav_bg                      = '#2c2c34';
$link_color                  = '#' . $accentColor->lighten(1);
$nav_icon_color              = '#d4d4d4';
$background_color            = '#1b1b1b';
$contentbg_transp            = '0';
$font_color                  = '#cccccc';
$font_color_darker           = '#acacac';
$font_color_lighter          = '#444444';
$background_image            = '';
