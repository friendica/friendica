<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Moderation\Report;

use Friendica\App;
use Friendica\Module\Moderation\Report\Create;
use Friendica\Test\MockedTestCase;

class CreateTest extends MockedTestCase
{
	public function testGetReturnPathFallsBackForMissingReturn(): void
	{
		$module = $this->createModule($this->createStub(App\BaseURL::class));

		self::assertSame('moderation/reports', $this->invokeGetReturnPath($module, []));
	}

	public function testGetReturnPathKeepsRelativePath(): void
	{
		$module = $this->createModule($this->createStub(App\BaseURL::class));

		self::assertSame('network?foo=bar', $this->invokeGetReturnPath($module, ['return' => 'network?foo=bar']));
	}

	public function testGetReturnPathFallsBackForExternalUrl(): void
	{
		$baseUrl = $this->createMock(App\BaseURL::class);
		$baseUrl->expects(self::once())
			->method('isLocalUrl')
			->with('https://example.net/network?foo=bar')
			->willReturn(false);

		$module = $this->createModule($baseUrl);

		self::assertSame('moderation/reports', $this->invokeGetReturnPath($module, ['return' => 'https://example.net/network?foo=bar']));
	}

	public function testGetReturnPathConvertsLocalAbsoluteUrlToRelativePath(): void
	{
		$baseUrl = $this->createMock(App\BaseURL::class);
		$baseUrl->expects(self::once())
			->method('isLocalUrl')
			->with('https://friendica.local/network?foo=bar')
			->willReturn(true);
		$baseUrl->expects(self::once())
			->method('getPath')
			->willReturn('');

		$module = $this->createModule($baseUrl);

		self::assertSame('network?foo=bar', $this->invokeGetReturnPath($module, ['return' => 'https://friendica.local/network?foo=bar']));
	}

	public function testGetReturnPathStripsLocalBasePathFromAbsoluteUrl(): void
	{
		$baseUrl = $this->createMock(App\BaseURL::class);
		$baseUrl->expects(self::once())
			->method('isLocalUrl')
			->with('https://friendica.local/friendica/network?foo=bar')
			->willReturn(true);
		$baseUrl->expects(self::once())
			->method('getPath')
			->willReturn('/friendica');

		$module = $this->createModule($baseUrl);

		self::assertSame('network?foo=bar', $this->invokeGetReturnPath($module, ['return' => 'https://friendica.local/friendica/network?foo=bar']));
	}

	private function createModule(App\BaseURL $baseUrl): Create
	{
		$reflectionClass = new \ReflectionClass(Create::class);
		/** @var Create $module */
		$module = $reflectionClass->newInstanceWithoutConstructor();

		$baseUrlProperty = new \ReflectionProperty(\Friendica\BaseModule::class, 'baseUrl');
		$baseUrlProperty->setValue($module, $baseUrl);

		return $module;
	}

	private function invokeGetReturnPath(Create $module, array $request): string
	{
		$method = new \ReflectionMethod($module, 'getReturnPath');

		return $method->invoke($module, $request);
	}
}
