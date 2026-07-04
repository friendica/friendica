<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Database\Database;
use Friendica\Test\MockedTestCase;

class L10nTest extends MockedTestCase
{
	public static function dataDetectLanguage()
	{
		return [
			'empty' => [
				'server'  => [],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'en',
			],
			'withGet' => [
				'server'  => [],
				'get'     => ['lang' => 'de'],
				'default' => 'en',
				'assert'  => 'de',
			],
			'withPipe' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'en-gb'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'en-gb',
			],
			'withoutPipe' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'fr',
			],
			'withQuality1' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,de'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'de',
			],
			'withQuality2' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,de;q=0.2'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'fr',
			],
			'withLangOverride' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,de;q=0.2'],
				'get'     => ['lang' => 'it'],
				'default' => 'en',
				'assert'  => 'it',
			],
			'withQualityAndPipe' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,de;q=0.2,nb-no;q=0.7'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'nb-no',
			],
			'withQualityAndInvalid' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'fr;q=0.5,bla;q=0.2,nb-no;q=0.7'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'nb-no',
			],
			'withQualityAndInvalid2' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'blu;q=0.9,bla;q=0.2,nb-no;q=0.7'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'nb-no',
			],
			'withQualityAndInvalidAndAbsolute' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'blu;q=0.9,de,nb-no;q=0.7'],
				'get'     => [],
				'default' => 'en',
				'assert'  => 'de',
			],
			'withInvalidGet' => [
				'server'  => ['HTTP_ACCEPT_LANGUAGE' => 'blu;q=0.9,nb-no;q=0.7'],
				'get'     => ['lang' => 'blu'],
				'default' => 'en',
				'assert'  => 'nb-no',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataDetectLanguage')]
	public function testDetectLanguage(array $server, array $get, string $default, string $assert): void
	{
		self::assertEquals($assert, L10n::detectLanguage($server, $get, $default));
	}

	public static function dataNormaliseLocale(): array
	{
		return [
			'lookup returns supported locale' => [
				'locale' => 'de-DE',
				'server' => [],
				'assert' => 'de',
			],
			'valid unsupported locale falls back to language only' => [
				'locale' => 'zu-ZA',
				'server' => [],
				'assert' => 'zu',
			],
			'invalid locale falls back to default' => [
				'locale' => '***',
				'server' => [],
				'assert' => 'en-US',
			],
			'unknown language falls back to default' => [
				'locale' => 'zz-ZZ',
				'server' => [],
				'assert' => 'en-US',
			],
			'null locale falls back to system default' => [
				'locale' => null,
				'server' => [],
				'assert' => 'en-US',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataNormaliseLocale')]
	public function testNormaliseLocale(?string $locale, array $server, string $assert): void
	{
		unset($_GET['lang']);

		$l10n = $this->createL10n($server);

		self::assertSame($assert, $l10n->normaliseLocale($locale));
	}

	private function createL10n(array $server): L10n
	{
		$config = $this->createMock(IManageConfigValues::class);
		$config->method('get')
			->willReturnCallback(static function (string $cat, ?string $key = null) {
				if ($cat === 'system' && $key === 'language') {
					return 'en-US';
				}

				if ($cat === 'addons') {
					return [];
				}

				return null;
			});

		$session = $this->createMock(IHandleSessions::class);
		$session->method('get')
			->willReturnCallback(static function (string $name) {
				if ($name === 'authenticated') {
					return false;
				}

				return null;
			});

		$dba = $this->createMock(Database::class);

		return new L10n($config, $dba, $session, $server, []);
	}
}
