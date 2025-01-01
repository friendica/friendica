<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Core\Theme;

/**
 * Prints theme specific details as a JSON string
 */
class ThemeDetails extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (!empty($_REQUEST['theme'])) {
			$theme = $_REQUEST['theme'];
			$info  = Theme::getInfo($theme);

			// Unfortunately there will be no translation for this string
			$description = $info['description'] ?? '';
			$version     = $info['version']     ?? '';
			$credits     = $info['credits']     ?? '';

			$this->jsonExit([
				'img'     => Theme::getScreenshot($theme),
				'desc'    => $description,
				'version' => $version,
				'credits' => $credits,
			]);
		}
		System::exit();
	}
}
