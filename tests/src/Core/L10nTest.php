<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core;

use Friendica\Core\L10n;
use Friendica\Test\MockedTestCase;

class L10nTest extends MockedTestCase
{
	public function dataDetectLanguage()
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

	/**
	 * @dataProvider dataDetectLanguage
	 */
	public function testDetectLanguage(array $server, array $get, string $default, string $assert)
	{
		self::assertEquals($assert, L10n::detectLanguage($server, $get, $default));
	}
}
