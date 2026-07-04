<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network;

use Exception;

/**
 * Throwable exceptions to return HTTP status code
 *
 * This list of Exception has been extracted from
 * here http://racksburg.com/choosing-an-http-status-code/
 */
abstract class HTTPException extends Exception
{
	protected $httpdesc    = '';
	protected $explanation = '';

	public function __construct(string $message = '', ?Exception $previous = null)
	{
		parent::__construct($message, $this->code, $previous);
	}

	public function getDescription()
	{
		return $this->httpdesc;
	}

	public function getExplanation()
	{
		return $this->explanation;
	}
}
