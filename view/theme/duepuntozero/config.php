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

function theme_content(App $a)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	$colorset = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'duepuntozero', 'colorset');
	$user = true;

	return clean_form($a, $colorset, $user);
}

function theme_post(App $a)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	if (isset($_POST['duepuntozero-settings-submit'])) {
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

function theme_admin(App $a)
{
	$colorset = DI::config()->get('duepuntozero', 'colorset');
	$user = false;

	return clean_form($a, $colorset, $user);
}

function theme_admin_post(App $a)
{
	if (isset($_POST['duepuntozero-settings-submit'])) {
		DI::config()->set('duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

/// @TODO $a is no longer used
function clean_form(App $a, &$colorset, $user)
{
	$colorset = [
		'default'     => DI::l10n()->t('default'),
		'greenzero'   => DI::l10n()->t('greenzero'),
		'purplezero'  => DI::l10n()->t('purplezero'),
		'easterbunny' => DI::l10n()->t('easterbunny'),
		'darkzero'    => DI::l10n()->t('darkzero'),
		'comix'       => DI::l10n()->t('comix'),
		'slackr'      => DI::l10n()->t('slackr'),
	];

	if ($user) {
		$color = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'duepuntozero', 'colorset');
	} else {
		$color = DI::config()->get('duepuntozero', 'colorset');
	}

	$t = Renderer::getMarkupTemplate("theme_settings.tpl");
	$o = Renderer::replaceMacros($t, [
		'$submit'   => DI::l10n()->t('Submit'),
		'$title'    => DI::l10n()->t("Theme settings"),
		'$colorset' => ['duepuntozero_colorset', DI::l10n()->t('Variations'), $color, '', $colorset],
	]);

	return $o;
}
