<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Exception;

use Throwable;

/**
 * Exception in case the used logging instance is unusable because of some circumstances
 */
class LoggerUnusableException extends \RuntimeException
{
	public function __construct($message = '', ?Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
