<?php

/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Get info header of the scheme
 *
 * This function parses the header of the schemename.php file for informations like
 * Author, Description and Overwrites. Most of the code comes from the Addon::getInfo()
 * function. We use this to get the variables which get overwritten through the scheme.
 * All color variables which get overwritten through the theme have to be
 * listed (comma separated) in the scheme header under Overwrites:
 * This seems not to be the best solution. We need to investigate further.
 *
 * @param string $scheme Name of the scheme
 * @return array With theme information
 *    'author' => Author Name
 *    'description' => Scheme description
 *    'version' => Scheme version
 *    'overwrites' => Variables which overwriting custom settings
 */

use Friendica\DI;
use Friendica\Util\Strings;

require_once 'view/theme/frio/theme.php';

function get_scheme_info($scheme)
{
	$theme     = DI::appHelper()->getCurrentTheme();
	$themepath = 'view/theme/' . $theme . '/';
	$scheme    = Strings::sanitizeFilePathItem($scheme) ?: FRIO_DEFAULT_SCHEME;

	$info = [
		'name'        => $scheme,
		'description' => '',
		'author'      => [],
		'version'     => '',
		'overwrites'  => [],
		'accented'    => false,
	];

	if (!is_file($themepath . 'scheme/' . $scheme . '.php')) {
		return $info;
	}

	$f = file_get_contents($themepath . 'scheme/' . $scheme . '.php');

	$r = preg_match('|/\*.*\*/|msU', $f, $m);

	if ($r) {
		$ll = explode("\n", $m[0]);
		foreach ($ll as $l) {
			$l = trim($l, "\t\n\r */");
			if ($l != '') {
				$values = array_map('trim', explode(':', $l, 2));
				if (count($values) < 2) {
					continue;
				}
				[$k, $v] = $values;
				$k       = strtolower($k);
				if ($k == 'author') {
					$r = preg_match('|([^<]+)<([^>]+)>|', $v, $m);
					if ($r) {
						$info['author'][] = ['name' => $m[1], 'link' => $m[2]];
					} else {
						$info['author'][] = ['name' => $v];
					}
				} elseif ($k == 'overwrites') {
					$theme_settings = explode(',', str_replace(' ', '', $v));
					foreach ($theme_settings as $key => $value) {
						$info['overwrites'][$value] = true;
					}
				} elseif ($k == 'accented') {
					$info['accented'] = $v && $v != 'false' && $v != 'no';
				} else {
					if (array_key_exists($k, $info)) {
						$info[$k] = $v;
					}
				}
			}
		}
	}

	return $info;
}

function frio_scheme_get_list(): array
{
	$schemes = [
		'light' => DI::l10n()->t('Light'),
		'dark'  => DI::l10n()->t('Dark'),
		'black' => DI::l10n()->t('Black'),
	];

	foreach (glob('view/theme/frio/scheme/*.php') ?: [] as $file) {
		$scheme = basename($file, '.php');
		if (!in_array($scheme, ['default', 'light', 'dark', 'black'])) {
			$scheme_info      = get_scheme_info($scheme);
			$schemes[$scheme] = $scheme_info['name'] ?? ucfirst($scheme);
		}
	}

	$schemes[FRIO_CUSTOM_SCHEME] = DI::l10n()->t('Custom');

	return $schemes;
}

function frio_scheme_get_current()
{
	$available = array_keys(frio_scheme_get_list());

	$scheme = DI::config()->get('frio', 'scheme') ?: DI::config()->get('frio', 'schema');

	if (!in_array($scheme, $available)) {
		return FRIO_DEFAULT_SCHEME;
	}

	return $scheme;
}

function frio_scheme_get_current_for_user(int $uid)
{
	$available = array_keys(frio_scheme_get_list());

	$scheme = DI::pConfig()->get($uid, 'frio', 'scheme') ?:
			DI::pConfig()->get($uid, 'frio', 'schema') ?:
				DI::config()->get('frio', 'scheme') ?:
					DI::config()->get('frio', 'schema');

	if (!in_array($scheme, $available)) {
		return FRIO_DEFAULT_SCHEME;
	}

	return $scheme;
}
