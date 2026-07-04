<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;

/**
 * Standardized way of exposing metadata about the project security policy
 * @see https://securitytxt.org
 */
class SecurityTxt extends BaseModule
{
	protected function rawContent(array $request = []): never
	{
		$name = 'security.txt';
		$fp   = fopen($name, 'rt');

		header('Content-type: text/plain; charset=utf-8');
		header("Content-Length: " . filesize($name));

		fpassthru($fp);
		exit;
	}
}
