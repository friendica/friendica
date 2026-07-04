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

require_once __DIR__ . '/theme.php';

function theme_content(AppHelper $appHelper)
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	if (!function_exists('get_vier_config')) {
		return;
	}

	$style = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'vier', 'style');

	if ($style == "") {
		$style = DI::config()->get('vier', 'style');
	}

	if ($style == "") {
		$style = "plus";
	}

	$show_pages     = get_vier_config('show_pages', true);
	$show_profiles  = get_vier_config('show_profiles', true);
	$show_helpers   = get_vier_config('show_helpers', true);
	$show_services  = get_vier_config('show_services', true);
	$show_friends   = get_vier_config('show_friends', true);
	$show_lastusers = get_vier_config('show_lastusers', true);

	return vier_form(
		$appHelper,
		$style,
		$show_pages,
		$show_profiles,
		$show_helpers,
		$show_services,
		$show_friends,
		$show_lastusers,
	);
}

function theme_post(AppHelper $appHelper): void
{
	if (!DI::userSession()->getLocalUserId()) {
		return;
	}

	if (isset($_POST['vier-settings-submit'])) {
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'vier', 'style', $_POST['vier_style']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'vier', 'show_pages', $_POST['vier_show_pages']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'vier', 'show_profiles', $_POST['vier_show_profiles']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'vier', 'show_helpers', $_POST['vier_show_helpers']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'vier', 'show_services', $_POST['vier_show_services']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'vier', 'show_friends', $_POST['vier_show_friends']);
		DI::pConfig()->set(DI::userSession()->getLocalUserId(), 'vier', 'show_lastusers', $_POST['vier_show_lastusers']);
	}
}


function theme_admin(AppHelper $appHelper)
{

	if (!function_exists('get_vier_config')) {
		return;
	}

	$style = DI::config()->get('vier', 'style');

	$helperlist = DI::config()->get('vier', 'helperlist');

	if ($helperlist == "") {
		$helperlist = "https://forum.friendi.ca/profile/helpers";
	}

	$t = Renderer::getMarkupTemplate("theme_admin_settings.tpl");
	$o = Renderer::replaceMacros($t, [
		'$helperlist' => ['vier_helperlist', DI::l10n()->t('Comma separated list of helper groups'), $helperlist, '', ''],
	]);

	$show_pages     = get_vier_config('show_pages', true, true);
	$show_profiles  = get_vier_config('show_profiles', true, true);
	$show_helpers   = get_vier_config('show_helpers', true, true);
	$show_services  = get_vier_config('show_services', true, true);
	$show_friends   = get_vier_config('show_friends', true, true);
	$show_lastusers = get_vier_config('show_lastusers', true, true);
	$o .= vier_form(
		$appHelper,
		$style,
		$show_pages,
		$show_profiles,
		$show_helpers,
		$show_services,
		$show_friends,
		$show_lastusers,
	);

	return $o;
}

function theme_admin_post(): void
{
	if (isset($_POST['vier-settings-submit'])) {
		DI::config()->set('vier', 'style', $_POST['vier_style']);
		DI::config()->set('vier', 'show_pages', $_POST['vier_show_pages']);
		DI::config()->set('vier', 'show_profiles', $_POST['vier_show_profiles']);
		DI::config()->set('vier', 'show_helpers', $_POST['vier_show_helpers']);
		DI::config()->set('vier', 'show_services', $_POST['vier_show_services']);
		DI::config()->set('vier', 'show_friends', $_POST['vier_show_friends']);
		DI::config()->set('vier', 'show_lastusers', $_POST['vier_show_lastusers']);
		DI::config()->set('vier', 'helperlist', $_POST['vier_helperlist']);
	}
}

/// @TODO $appHelper is no longer used
function vier_form(AppHelper $appHelper, $style, $show_pages, $show_profiles, $show_helpers, $show_services, $show_friends, $show_lastusers)
{
	$styles = [
		"breathe"   => "Breathe",
		"netcolour" => "Coloured Networks",
		"dark"      => "Dark",
		"flat"      => "Flat",
		"plus"      => "Plus",
		"plusminus" => "Plus Minus",
		"shadow"    => "Shadow",
	];

	$show_or_not = ['0' => DI::l10n()->t("don't show"), '1' => DI::l10n()->t("show"),];

	$t = Renderer::getMarkupTemplate("theme_settings.tpl");
	$o = Renderer::replaceMacros($t, [
		'$submit'         => DI::l10n()->t('Submit'),
		'$title'          => DI::l10n()->t("Theme settings"),
		'$style'          => ['vier_style', DI::l10n()->t('Set style'), $style, '', $styles],
		'$show_pages'     => ['vier_show_pages', DI::l10n()->t('Community Pages'), $show_pages, '', $show_or_not],
		'$show_profiles'  => ['vier_show_profiles', DI::l10n()->t('Community Profiles'), $show_profiles, '', $show_or_not],
		'$show_helpers'   => ['vier_show_helpers', DI::l10n()->t('Help or @NewHere ?'), $show_helpers, '', $show_or_not],
		'$show_services'  => ['vier_show_services', DI::l10n()->t('Connect Services'), $show_services, '', $show_or_not],
		'$show_friends'   => ['vier_show_friends', DI::l10n()->t('Find Friends'), $show_friends, '', $show_or_not],
		'$show_lastusers' => ['vier_show_lastusers', DI::l10n()->t('Last users'), $show_lastusers, '', $show_or_not],
	]);
	return $o;
}
