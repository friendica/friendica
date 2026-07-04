<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core;

use Dice\Dice;
use Friendica\App\BaseURL;
use Friendica\Core\System;
use Friendica\DI;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
	private function useBaseUrl()
	{
		$baseUrl = \Mockery::mock(BaseURL::class);
		$baseUrl->shouldReceive('getHost')->andReturn('friendica.local')->once();
		$dice = \Mockery::mock(Dice::class);
		$dice->shouldReceive('create')->with(BaseURL::class)->andReturn($baseUrl);

		DI::init($dice, true);
	}

	private function assertGuid($guid, $length, $prefix = '')
	{
		$length -= strlen((string) $prefix);
		self::assertMatchesRegularExpression("/^" . $prefix . "[a-z0-9]{" . $length . "}?$/", $guid);
	}

	public function testGuidWithoutParameter(): void
	{
		$this->useBaseUrl();
		$guid = System::createGUID();
		self::assertGuid($guid, 16);
	}

	public function testGuidWithSize32(): void
	{
		$this->useBaseUrl();
		$guid = System::createGUID(32);
		self::assertGuid($guid, 32);
	}

	public function testGuidWithSize64(): void
	{
		$this->useBaseUrl();
		$guid = System::createGUID(64);
		self::assertGuid($guid, 64);
	}

	public function testGuidWithPrefix(): void
	{
		$guid = System::createGUID(23, 'test');
		self::assertGuid($guid, 23, 'test');
	}
}
