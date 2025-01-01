<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Addon\Exception;

/**
 * Exception in case one or more config files of the addons are invalid
 */
class AddonInvalidConfigFileException extends \RuntimeException
{
	public function __construct($message = '')
	{
		parent::__construct($message, 500);
	}
}
