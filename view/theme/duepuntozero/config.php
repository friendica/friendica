<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

use Friendica\AppHelper;
use Friendica\Core\Renderer;
use Friendica\DI;

function theme_content(AppHelper $appHelper)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	$colorset = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'duepuntozero', 'colorset');
	$user = true;

	return clean_form($appHelper, $colorset, $user);
}

function theme_post(AppHelper $appHelper)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	if (isset($_POST['duepuntozero-settings-submit'])) {
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

function theme_admin(AppHelper $appHelper)
{
	$colorset = DI::config()->get('duepuntozero', 'colorset');
	$user = false;

	return clean_form($appHelper, $colorset, $user);
}

function theme_admin_post()
{
	if (isset($_POST['duepuntozero-settings-submit'])) {
		DI::config()->set('duepuntozero', 'colorset', $_POST['duepuntozero_colorset']);
	}
}

/// @TODO $appHelper is no longer used
function clean_form(AppHelper $appHelper, &$colorset, $user)
{
	$colorset = [
		'default'     => 'default',
		'greenzero'   => 'greenzero',
		'purplezero'  => 'purplezero',
		'easterbunny' => 'easterbunny',
		'darkzero'    => 'darkzero',
		'comix'       => 'comix',
		'slackr'      => 'slackr',
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
