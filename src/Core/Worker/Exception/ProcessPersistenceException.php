<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Worker\Exception;

use Throwable;

class ProcessPersistenceException extends \RuntimeException
{
	public function __construct($message = "", ?Throwable $previous = null)
	{
		parent::__construct($message, 500, $previous);
	}
}
