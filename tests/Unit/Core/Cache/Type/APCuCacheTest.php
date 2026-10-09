<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Cache\Type;

use Friendica\Core\Cache\Exception\InvalidCacheDriverException;
use Friendica\Core\Cache\Type\APCuCache;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Availability checks only, the cache operations are covered by tests/src/Core/Cache/APCuCacheTest.php.
 * Separate processes, because php-mock can't mock functions which were already called in the namespace (e.g. by the legacy APCu tests).
 */
#[RunTestsInSeparateProcesses]
class APCuCacheTest extends TestCase
{
	use PHPMock;

	private const NAMESPACE = 'Friendica\Core\Cache\Type';

	/**
	 * @param array<string, string|false> $ini
	 */
	private function mockEnvironment(bool $loaded, array $ini, string|false $version): void
	{
		$this->getFunctionMock(self::NAMESPACE, 'extension_loaded')->expects(self::any())->willReturn($loaded);
		$this->getFunctionMock(self::NAMESPACE, 'ini_get')->expects(self::any())->willReturnCallback(fn (string $option): string|false => $ini[$option] ?? false);
		$this->getFunctionMock(self::NAMESPACE, 'phpversion')->expects(self::any())->willReturn($version);
	}

	/**
	 * @return array<string, array{bool, array<string, string|false>, string|false, bool}>
	 */
	public static function dataAvailability(): array
	{
		$enabled = ['apc.enabled' => '1', 'apc.enable_cli' => '1'];

		return [
			'available'             => [true, $enabled, '5.1.24', true],
			'extension missing'     => [false, $enabled, false, false],
			'disabled'              => [true, ['apc.enabled' => '0', 'apc.enable_cli' => '1'], '5.1.24', false],
			'disabled for CLI'      => [true, ['apc.enabled' => '1', 'apc.enable_cli' => '0'], '5.1.24', false],
			'version too old'       => [true, $enabled, '5.0.9', false],
			'version not available' => [true, $enabled, false, false],
		];
	}

	/**
	 * @param array<string, string|false> $ini
	 */
	#[DataProvider('dataAvailability')]
	public function testIsAvailable(bool $loaded, array $ini, string|false $version, bool $expected): void
	{
		$this->mockEnvironment($loaded, $ini, $version);

		self::assertSame($expected, APCuCache::isAvailable());
	}

	public function testConstructorThrowsIfUnavailable(): void
	{
		$this->mockEnvironment(false, [], false);

		$this->expectException(InvalidCacheDriverException::class);

		new APCuCache('friendica.local');
	}
}
