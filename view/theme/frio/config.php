<?php

/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Friendica\AppHelper;
use Friendica\Content\ContactSelector;
use Friendica\Core\Renderer;
use Friendica\DI;

require_once 'view/theme/frio/php/Image.php';
require_once 'view/theme/frio/php/scheme.php';

function theme_post(AppHelper $appHelper)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	$previous_scheme = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'scheme');

	if (isset($_POST['frio-settings-submit'])) {
		foreach ([
			'scheme',
			'scheme_accent',
			'nav_bg',
			'nav_icon_color',
			'link_color',
			'background_color',
			'contentbg_transp',
			'background_image',
			'bg_image_option',
			'login_bg_image',
			'login_bg_color',
			'always_open_compose',
		] as $field) {
			if (isset($_POST['frio_' . $field])) {
				DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'frio', $field, $_POST['frio_' . $field]);
			}

		}
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'frio', 'css_modified', time());

		$current_scheme = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'scheme');

		if ($previous_scheme != $current_scheme) {
			$icon_style = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'accessibility', 'platform_icon_style');
			if (in_array($current_scheme, ['dark', 'black']) && in_array($icon_style, [ContactSelector::SVG_BLACK])) {
				DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'accessibility', 'platform_icon_style', ContactSelector::SVG_WHITE);
			} elseif (in_array($current_scheme, ['dark', 'black']) && in_array($icon_style, [ContactSelector::SVG_COLOR_BLACK])) {
				DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'accessibility', 'platform_icon_style', ContactSelector::SVG_COLOR_WHITE);
			} elseif (in_array($current_scheme, ['light']) && in_array($icon_style, [ContactSelector::SVG_WHITE])) {
				DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'accessibility', 'platform_icon_style', ContactSelector::SVG_BLACK);
			} elseif (in_array($current_scheme, ['light']) && in_array($icon_style, [ContactSelector::SVG_COLOR_WHITE])) {
				DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'accessibility', 'platform_icon_style', ContactSelector::SVG_COLOR_BLACK);
			}
		}
	}
}

function theme_admin_post()
{
	if (!DI::userSession()->isSiteAdmin()) {
		return;
	}

	if (isset($_POST['frio-settings-submit'])) {
		foreach ([
			'scheme',
			'scheme_accent',
			'nav_bg',
			'nav_icon_color',
			'link_color',
			'background_color',
			'contentbg_transp',
			'background_image',
			'bg_image_option',
			'login_bg_image',
			'login_bg_color',
			'always_open_compose',
		] as $field) {
			if (isset($_POST['frio_' . $field])) {
				DI::config()->set('frio', $field, $_POST['frio_' . $field]);
			}
		}

		DI::config()->set('frio', 'css_modified', time());
	}
}

function theme_content(AppHelper $appHelper): string
{
	if (!DI::userSession()->getLocalUserId()) {
		return '';
	}

	$arr = [
		'scheme'              => frio_scheme_get_current_for_user(DI::userSession()->getLocalUserId()),
		'share_string'        => '',
		'scheme_accent'       => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'scheme_accent', DI::config()->get('frio', 'scheme_accent')),
		'nav_bg'              => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'nav_bg', DI::config()->get('frio', 'nav_bg')),
		'nav_icon_color'      => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'nav_icon_color', DI::config()->get('frio', 'nav_icon_color')),
		'link_color'          => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'link_color', DI::config()->get('frio', 'link_color')),
		'background_color'    => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'background_color', DI::config()->get('frio', 'background_color')),
		'contentbg_transp'    => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'contentbg_transp', DI::config()->get('frio', 'contentbg_transp')),
		'background_image'    => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'background_image', DI::config()->get('frio', 'background_image')),
		'bg_image_option'     => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'bg_image_option', DI::config()->get('frio', 'bg_image_option')),
		'always_open_compose' => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'always_open_compose', DI::config()->get('frio', 'always_open_compose', false)),
	];

	return frio_form($arr);
}

function theme_admin(AppHelper $appHelper): string
{
	if (!DI::userSession()->getLocalUserId()) {
		return '';
	}

	$arr = [
		'admin_theme_settings' => true,
		'scheme'               => frio_scheme_get_current(),
		'scheme_accent'        => DI::config()->get('frio', 'scheme_accent') ?: FRIO_SCHEME_ACCENT_BLUE,
		'share_string'         => '',
		'nav_bg'               => DI::config()->get('frio', 'nav_bg'),
		'nav_icon_color'       => DI::config()->get('frio', 'nav_icon_color'),
		'link_color'           => DI::config()->get('frio', 'link_color'),
		'background_color'     => DI::config()->get('frio', 'background_color'),
		'contentbg_transp'     => DI::config()->get('frio', 'contentbg_transp'),
		'background_image'     => DI::config()->get('frio', 'background_image'),
		'bg_image_option'      => DI::config()->get('frio', 'bg_image_option'),
		'login_bg_image'       => DI::config()->get('frio', 'login_bg_image'),
		'login_bg_color'       => DI::config()->get('frio', 'login_bg_color'),
		'always_open_compose'  => DI::config()->get('frio', 'always_open_compose', false),
	];

	return frio_form($arr);
}

function frio_form($arr)
{
	require_once 'view/theme/frio/php/scheme.php';
	require_once 'view/theme/frio/theme.php';

	$scheme_info = get_scheme_info($arr['scheme']);
	$disable     = $scheme_info['overwrites'];

	$background_image_help = '<strong>' . DI::l10n()->t('Note') . ': </strong>' . DI::l10n()->t('Check image permissions if all users are allowed to see the image');

	$t   = Renderer::getMarkupTemplate('theme_settings.tpl');
	$ctx = [
		'$admin_theme_settings'   => $arr['admin_theme_settings'] ?? false,
		'$submit'                 => DI::l10n()->t('Save settings'),
		'$title'                  => DI::l10n()->t('Theme settings'),
		'$scheme'                 => ['frio_scheme', DI::l10n()->t('Appearance'), $arr['scheme'], frio_scheme_get_list()],
		'$scheme_accent'          => !$scheme_info['accented'] ? '' : ['frio_scheme_accent', DI::l10n()->t('Accent color'), $arr['scheme_accent']],
		'$share_string'           => $arr['scheme'] != FRIO_CUSTOM_SCHEME ? '' : ['frio_share_string', DI::l10n()->t('Copy or paste schemestring'), $arr['share_string'], DI::l10n()->t('You can copy this string to share your theme with others. Pasting here applies the schemestring'), false, false],
		'$nav_bg'                 => array_key_exists('nav_bg', $disable) ? '' : ['frio_nav_bg', DI::l10n()->t('Navigation bar background color'), $arr['nav_bg'], '', false],
		'$nav_icon_color'         => array_key_exists('nav_icon_color', $disable) ? '' : ['frio_nav_icon_color', DI::l10n()->t('Navigation bar icon color '), $arr['nav_icon_color'], '', false],
		'$link_color'             => array_key_exists('link_color', $disable) ? '' : ['frio_link_color', DI::l10n()->t('Link color'), $arr['link_color'], '', false],
		'$background_color'       => array_key_exists('background_color', $disable) ? '' : ['frio_background_color', DI::l10n()->t('Set the background color'), $arr['background_color'], '', false],
		'$contentbg_transp'       => array_key_exists('contentbg_transp', $disable) ? '' : ['frio_contentbg_transp', DI::l10n()->t('Content background opacity'), $arr['contentbg_transp'] ?? 100, ''],
		'$background_image'       => array_key_exists('background_image', $disable) ? '' : ['frio_background_image', DI::l10n()->t('Set the background image'), $arr['background_image'], $background_image_help, false],
		'$bg_image_options_title' => DI::l10n()->t('Background image style'),
		'$bg_image_options'       => Image::get_options($arr),

		'$always_open_compose' => ['frio_always_open_compose', DI::l10n()->t('Always open Compose page'), $arr['always_open_compose'], DI::l10n()->t('The New Post button always open the <a href="/compose">Compose page</a> instead of the modal form. When this is disabled, the Compose page can be accessed with a middle click on the link or from the modal.')],
	];

	if (array_key_exists('login_bg_image', $arr) && !array_key_exists('login_bg_image', $disable)) {
		$ctx['$login_bg_image'] = ['frio_login_bg_image', DI::l10n()->t('Login page background image'), $arr['login_bg_image'], $background_image_help, false];
	}

	if (array_key_exists('login_bg_color', $arr) && !array_key_exists('login_bg_color', $disable)) {
		$ctx['$login_bg_color'] = ['frio_login_bg_color', DI::l10n()->t('Login page background color'), $arr['login_bg_color'], DI::l10n()->t('Leave background image and color empty for theme defaults'), false];
	}

	return Renderer::replaceMacros($t, $ctx);
}
