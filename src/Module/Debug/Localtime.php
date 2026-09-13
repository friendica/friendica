<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\Installer;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

class Localtime extends BaseModule
{
	public static $mod_localtime = '';

	protected function post(array $request = [])
	{
		$time = ($_REQUEST['time'] ?? '') ?: 'now';

		$bd_format = DI::l10n()->t('l F d, Y \@ g:i A');

		if (!empty($_POST['timezone'])) {
			self::$mod_localtime = DateTimeFormat::convert($time, $_POST['timezone'], 'UTC', $bd_format);
		}
	}

	protected function content(array $request = []): string
	{
		$time = ($_REQUEST['time'] ?? '') ?: 'now';

		$output = '<h3>' . DI::l10n()->t('Time Conversion') . '</h3>';
		$output .= '<p>' . DI::l10n()->t('Friendica provides this service for sharing events with other networks and friends in unknown timezones.') . '</p>';
		$output .= '<p>' . DI::l10n()->t('UTC time: %s', htmlspecialchars((string) $time, ENT_QUOTES)) . '</p>';

		if (!empty($_REQUEST['timezone'])) {
			$output .= '<p>' . DI::l10n()->t('Current timezone: %s', htmlspecialchars((string) $_REQUEST['timezone'], ENT_QUOTES)) . '</p>';
		}

		if (!empty(self::$mod_localtime)) {
			$output .= '<p>' . DI::l10n()->t('Converted localtime: %s', htmlspecialchars((string) self::$mod_localtime, ENT_QUOTES)) . '</p>';
		}

		$output .= '<form action ="localtime?time=' . urlencode((string) $time) . '" method="post">';
		$output .= '<p>' . DI::l10n()->t('Please select your timezone:') . '</p>';
		$output .= Temporal::getTimezoneSelect(($_REQUEST['timezone'] ?? '') ?: Installer::DEFAULT_TZ);
		$output .= '<input type="submit" name="submit" value="' . DI::l10n()->t('Submit') . '" /></form>';

		return $output;
	}
}
