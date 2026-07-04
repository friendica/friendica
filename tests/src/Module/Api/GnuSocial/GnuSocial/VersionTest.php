<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\GnuSocial\GnuSocial;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\GNUSocial\GNUSocial\Version;
use Friendica\Test\ApiTestCase;

class VersionTest extends ApiTestCase
{
	public function test(): void
	{
		$response = (new Version(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock);

		self::assertEquals([
			'Content-type'                => ['application/json'],
			ICanCreateResponses::X_HEADER => ['json'],
		], $response->getHeaders());
		self::assertEquals('"0.9.7"', $response->getBody());
	}
}
