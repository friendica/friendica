<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Friendica\App;
use Friendica\Core\Renderer;
use Friendica\DI;

require_once 'view/theme/frio/php/Image.php';

function theme_post(App $a)
{
	if (!DI::userSession()->getLocalUserId()) {
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
				DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'frio', $field, $_POST['frio_' . $field]);
			}

		}

		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'frio', 'css_modified',     time());
	}
}

function theme_admin_post(App $a)
{
	if (!$a->isSiteAdmin()) {
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

		DI::config()->set('frio', 'css_modified',     time());
	}
}

function theme_content(): string
{
	if (!DI::userSession()->getLocalUserId()) {
		return '';
	}

	$arr = [
		'scheme' => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'scheme',
			DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'schema',
				DI::config()->get('frio', 'scheme',
					DI::config()->get('frio', 'schema')
				)
			)
		),

		'share_string'        => '',
		'scheme_accent'       => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'scheme_accent'      , DI::config()->get('frio', 'scheme_accent')),
		'nav_bg'              => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'nav_bg'             , DI::config()->get('frio', 'nav_bg')),
		'nav_icon_color'      => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'nav_icon_color'     , DI::config()->get('frio', 'nav_icon_color')),
		'link_color'          => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'link_color'         , DI::config()->get('frio', 'link_color')),
		'background_color'    => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'background_color'   , DI::config()->get('frio', 'background_color')),
		'contentbg_transp'    => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'contentbg_transp'   , DI::config()->get('frio', 'contentbg_transp')),
		'background_image'    => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'background_image'   , DI::config()->get('frio', 'background_image')),
		'bg_image_option'     => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'bg_image_option'    , DI::config()->get('frio', 'bg_image_option')),
		'always_open_compose' => DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'frio', 'always_open_compose', DI::config()->get('frio', 'always_open_compose', false)),
	];

	return frio_form($arr);
}

function theme_admin(): string
{
	if (!DI::userSession()->getLocalUserId()) {
		return '';
	}

	$arr = [
		'scheme'              => DI::config()->get('frio', 'scheme', DI::config()->get('frio', 'schema')),
		'scheme_accent'       => DI::config()->get('frio', 'scheme_accent'),
		'share_string'        => '',
		'nav_bg'              => DI::config()->get('frio', 'nav_bg'),
		'nav_icon_color'      => DI::config()->get('frio', 'nav_icon_color'),
		'link_color'          => DI::config()->get('frio', 'link_color'),
		'background_color'    => DI::config()->get('frio', 'background_color'),
		'contentbg_transp'    => DI::config()->get('frio', 'contentbg_transp'),
		'background_image'    => DI::config()->get('frio', 'background_image'),
		'bg_image_option'     => DI::config()->get('frio', 'bg_image_option'),
		'login_bg_image'      => DI::config()->get('frio', 'login_bg_image'),
		'login_bg_color'      => DI::config()->get('frio', 'login_bg_color'),
		'always_open_compose' => DI::config()->get('frio', 'always_open_compose', false),
	];

	return frio_form($arr);
}

function frio_form($arr)
{
	require_once 'view/theme/frio/php/scheme.php';
	require_once 'view/theme/frio/theme.php';

	$scheme_info = get_scheme_info($arr['scheme']);
	$disable = $scheme_info['overwrites'];

	$schemes = [
		'light' => DI::l10n()->t('Light (Accented)'),
		'dark'  => DI::l10n()->t('Dark (Accented)'),
		'black' => DI::l10n()->t('Black (Accented)'),
	];

	$legacy_schemes = [];
	foreach (glob('view/theme/frio/scheme/*.php') ?: [] as $file) {
		$scheme = basename($file, '.php');
		if (!in_array($scheme, ['default', 'light', 'dark', 'black'])) {
			$scheme_name = ucfirst($scheme);
			$legacy_schemes[$scheme] = $scheme_name;
		}
	}

	$background_image_help = '<strong>' . DI::l10n()->t('Note') . ': </strong>' . DI::l10n()->t('Check image permissions if all users are allowed to see the image');

	$t = Renderer::getMarkupTemplate('theme_settings.tpl');
	$ctx = [
		'$submit'           => DI::l10n()->t('Submit'),
		'$title'            => DI::l10n()->t('Theme settings'),
		'$custom'           => DI::l10n()->t('Custom'),
		'$legacy'           => DI::l10n()->t('Legacy'),
		'$accented'         => DI::l10n()->t('Accented'),
		'$scheme'           => ['frio_scheme', DI::l10n()->t('Select color scheme'), $arr['scheme'], $schemes, $legacy_schemes],
		'$scheme_accent'    => !$scheme_info['accented'] ? '' : ['frio_scheme_accent', DI::l10n()->t('Select scheme accent'), $arr['scheme_accent'], ['blue' => DI::l10n()->t('Blue'), 'red' => DI::l10n()->t('Red'), 'purple' => DI::l10n()->t('Purple'), 'green' => DI::l10n()->t('Green'), 'pink' => DI::l10n()->t('Pink')]],
		'$share_string'     => $arr['scheme'] != '---' ? '' : ['frio_share_string', DI::l10n()->t('Copy or paste schemestring'), $arr['share_string'], DI::l10n()->t('You can copy this string to share your theme with others. Pasting here applies the schemestring'), false, false],
		'$nav_bg'           => array_key_exists('nav_bg', $disable) ? '' : ['frio_nav_bg', DI::l10n()->t('Navigation bar background color'), $arr['nav_bg'], '', false],
		'$nav_icon_color'   => array_key_exists('nav_icon_color', $disable) ? '' : ['frio_nav_icon_color', DI::l10n()->t('Navigation bar icon color '), $arr['nav_icon_color'], '', false],
		'$link_color'       => array_key_exists('link_color', $disable) ? '' : ['frio_link_color', DI::l10n()->t('Link color'), $arr['link_color'], '', false],
		'$background_color' => array_key_exists('background_color', $disable) ? '' : ['frio_background_color', DI::l10n()->t('Set the background color'), $arr['background_color'], '', false],
		'$contentbg_transp' => array_key_exists('contentbg_transp', $disable) ? '' : ['frio_contentbg_transp', DI::l10n()->t('Content background opacity'), $arr['contentbg_transp'] ?? 100, ''],
		'$background_image' => array_key_exists('background_image', $disable) ? '' : ['frio_background_image', DI::l10n()->t('Set the background image'), $arr['background_image'], $background_image_help, false],
		'$bg_image_options_title' => DI::l10n()->t('Background image style'),
		'$bg_image_options' => Image::get_options($arr),

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
