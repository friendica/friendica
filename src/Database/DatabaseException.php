<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Database;

use Exception;
use Throwable;

/**
 * A database fatal exception, which shouldn't occur
 */
class DatabaseException extends Exception
{
	protected $query;

	/**
	 * Construct the exception. Note: The message is NOT binary safe.
	 *
	 * @link https://php.net/manual/en/exception.construct.php
	 *
	 * @param string         $message  The Database error message.
	 * @param int            $code     The Database error code.
	 * @param string         $query    The Database error query.
	 * @param Throwable|null $previous [optional] The previous throwable used for the exception chaining.
	 */
	public function __construct(
		string $message,
		int $code,
		string $query,
		?Throwable $previous = null,
	) {
		parent::__construct(sprintf('"%s" at "%s"', $message, $query), $code, $previous);
		$this->query = $query;
	}

	/**
	 * Returns the query, which caused the exception
	 *
	 * @return string
	 */
	public function getQuery(): string
	{
		return $this->query;
	}
}
