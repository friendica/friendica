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

function theme_content(App $a) {
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	$align = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'quattro', 'align' );
	$color = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'quattro', 'color' );
	$tfs = DI::pConfig()->get(DI::userSession()->getLocalUserId(),"quattro","tfs");
	$pfs = DI::pConfig()->get(DI::userSession()->getLocalUserId(),"quattro","pfs");

	return quattro_form($a,$align, $color, $tfs, $pfs);
}

function theme_post(App $a) {
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	if (isset($_POST['quattro-settings-submit'])){
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'quattro', 'align', $_POST['quattro_align']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'quattro', 'color', $_POST['quattro_color']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'quattro', 'tfs', $_POST['quattro_tfs']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'quattro', 'pfs', $_POST['quattro_pfs']);
	}
}

function theme_admin(App $a) {
	$align = DI::config()->get('quattro', 'align' );
	$color = DI::config()->get('quattro', 'color' );
	$tfs = DI::config()->get("quattro","tfs");
	$pfs = DI::config()->get("quattro","pfs");

	return quattro_form($a,$align, $color, $tfs, $pfs);
}

function theme_admin_post(App $a) {
	if (isset($_POST['quattro-settings-submit'])){
		DI::config()->set('quattro', 'align', $_POST['quattro_align']);
		DI::config()->set('quattro', 'color', $_POST['quattro_color']);
		DI::config()->set('quattro', 'tfs', $_POST['quattro_tfs']);
		DI::config()->set('quattro', 'pfs', $_POST['quattro_pfs']);
	}
}

/// @TODO $a is no longer used here
function quattro_form(App $a, $align, $color, $tfs, $pfs) {
	$colors = [
		"dark"  => "Quattro",
		"lilac" => "Lilac",
		"green" => "Green",
	];

	if ($tfs === false) {
		$tfs = "20";
	}
	if ($pfs === false) {
		$pfs = "12";
	}

	$t = Renderer::getMarkupTemplate("theme_settings.tpl" );
	$o = Renderer::replaceMacros($t, [
		'$submit'  => DI::l10n()->t('Submit'),
		'$title'   => DI::l10n()->t("Theme settings"),
		'$align'   => ['quattro_align', DI::l10n()->t('Alignment'), $align, '', ['left' => DI::l10n()->t('Left'), 'center' => DI::l10n()->t('Center')]],
		'$color'   => ['quattro_color', DI::l10n()->t('Color scheme'), $color, '', $colors],
		'$pfs'     => ['quattro_pfs', DI::l10n()->t('Posts font size'), $pfs],
		'$tfs'     => ['quattro_tfs', DI::l10n()->t('Textareas font size'), $tfs],
	]);
	return $o;
}
