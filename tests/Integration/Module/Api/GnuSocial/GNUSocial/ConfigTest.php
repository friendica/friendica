<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\GnuSocial\GNUSocial;

use Friendica\DI;
use Friendica\Module\Api\GNUSocial\GNUSocial\Config;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class ConfigTest extends ApiTestCase
{
	public function testApiStatusnetConfig(): void
	{
		$module = new Config(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []);

		$request = new ServerRequest('GET', 'https://friendica.local/api/gnusocial/config');

		$response = $module->handleRequest($request);

		self::assertEquals(200, $response->getStatusCode());

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
