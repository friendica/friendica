<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Network\HTTPClient\Client;

use Friendica\Network\HTTPClient\Client\ExplicitFileCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use PHPUnit\Framework\TestCase;

class ExplicitFileCookieJarTest extends TestCase
{
	private string $filename;

	protected function setUp(): void
	{
		parent::setUp();

		$this->filename = sys_get_temp_dir() . '/cookiejar-test-' . bin2hex(random_bytes(8));
	}

	protected function tearDown(): void
	{
		if (file_exists($this->filename)) {
			unlink($this->filename);
		}

		parent::tearDown();
	}

	private static function createJar(string $filename): ExplicitFileCookieJar
	{
		$jar = new ExplicitFileCookieJar($filename);
		$jar->setCookie(new SetCookie([
			'Name'    => 'session',
			'Value'   => 'abc',
			'Domain'  => 'example.org',
			'Expires' => time() + 3600,
		]));

		return $jar;
	}

	/**
	 * Destroying the jar must not write the file, a failed write would throw outside of any try block.
	 */
	public function testDestructorDoesNotSave(): void
	{
		$jar = self::createJar($this->filename);
		unset($jar);

		self::assertFileDoesNotExist($this->filename);
	}

	public function testDestructorDoesNotThrowOnUnwritablePath(): void
	{
		$jar = self::createJar(sys_get_temp_dir() . '/missing-' . bin2hex(random_bytes(8)) . '/jar');
		unset($jar);

		$this->expectNotToPerformAssertions();
	}

	public function testSavedCookiesAreLoaded(): void
	{
		self::createJar($this->filename)->save($this->filename);

		$jar = new ExplicitFileCookieJar($this->filename);

		self::assertSame('abc', $jar->getCookieByName('session')?->getValue());
	}

	public function testSaveThrowsOnUnwritablePath(): void
	{
		$filename = sys_get_temp_dir() . '/missing-' . bin2hex(random_bytes(8)) . '/jar';

		$this->expectException(\RuntimeException::class);

		@self::createJar($filename)->save($filename);
	}
}
