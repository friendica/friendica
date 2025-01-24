<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Util;

use Friendica\Util\BasePath;
use PHPUnit\Framework\TestCase;

class BasePathTest extends TestCase
{
	public static function getDataPaths(): array
	{
		$basePath   = dirname(__DIR__, 3);
		$configPath = $basePath . DIRECTORY_SEPARATOR . 'config';

		return [
			'fullPath' => [
				'server'   => [],
				'baseDir'  => $configPath,
				'expected' => $configPath,
			],
			'relative' => [
				'server'   => [],
				'baseDir'  => 'config',
				'expected' => $configPath,
			],
			'document_root' => [
				'server' => [
					'DOCUMENT_ROOT' => $configPath,
				],
				'baseDir'  => '/noooop',
				'expected' => $configPath,
			],
			'pwd' => [
				'server' => [
					'PWD' => $configPath,
				],
				'baseDir'  => '/noooop',
				'expected' => $configPath,
			],
			'no_overwrite' => [
				'server' => [
					'DOCUMENT_ROOT' => $basePath,
					'PWD'           => $basePath,
				],
				'baseDir'  => 'config',
				'expected' => $configPath,
			],
			'no_overwrite_if_invalid' => [
				'server' => [
					'DOCUMENT_ROOT' => '/nopopop',
					'PWD'           => $configPath,
				],
				'baseDir'  => '/noatgawe22fafa',
				'expected' => $configPath,
			]
		];
	}

	/**
	 * Test the basepath determination
	 * @dataProvider getDataPaths
	 */
	public function testDetermineBasePath(array $server, string $baseDir, string $expected): void
	{
		$basepath = new BasePath($baseDir, $server);
		self::assertEquals($expected, $basepath->getPath());
	}

	/**
	 * Test the basepath determination with a complete wrong path
	 */
	public function testFailedBasePath(): void
	{
		$basepath = new BasePath('/now23452sgfgas', []);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('\'/now23452sgfgas\' is not a valid basepath');

		$basepath->getPath();
	}
}
