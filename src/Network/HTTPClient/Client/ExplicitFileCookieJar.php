<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Network\HTTPClient\Client;

use GuzzleHttp\Cookie\FileCookieJar;

/**
 * File cookie jar that is only written by an explicit save() call.
 *
 * Guzzle's FileCookieJar saves in its destructor.
 * A failed write there (e.g. full disk) throws outside of any try block.
 */
final class ExplicitFileCookieJar extends FileCookieJar
{
	public function __destruct()
	{
		// Saving is done by the caller
	}
}
