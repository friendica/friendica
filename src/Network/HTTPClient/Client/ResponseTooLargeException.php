<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\HTTPClient\Client;

use GuzzleHttp\Exception\TransferException;

/**
 * Thrown when a response body exceeds the configured maximum size
 */
class ResponseTooLargeException extends TransferException
{
	public function __construct(public readonly int $limit)
	{
		parent::__construct('The file is too big!');
	}
}
