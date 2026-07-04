<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\GnuSocial\GnuSocial;

use Friendica\DI;
use Friendica\Module\Api\GNUSocial\GNUSocial\Config;
use Friendica\Test\ApiTestCase;

class ConfigTest extends ApiTestCase
{
	/**
	 * Test the api_statusnet_config() function.
	 */
	public function testApiStatusnetConfig(): void
	{
		$response = (new Config(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock);
		$json = $this->toJson($response);

		self::assertEquals(DI::baseUrl()->getHost(), $json->site->server);
		self::assertEquals(DI::config()->get('system', 'theme'), $json->site->theme);
		self::assertEquals(DI::baseUrl() . '/images/friendica-64.png', $json->site->logo);
		self::assertTrue($json->site->fancy);
		self::assertEquals(DI::config()->get('system', 'language'), $json->site->language);
		self::assertEquals(DI::config()->get('system', 'default_timezone'), $json->site->timezone);
		self::assertEquals(200000, $json->site->textlimit);
		self::assertFalse($json->site->private);
		self::assertEquals('always', $json->site->ssl);
	}
}
