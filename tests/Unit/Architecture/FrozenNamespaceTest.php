<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The top-level buckets `src/Object/`, `src/Factory/` and `src/Collection/` are frozen:
 * they are being folded into the feature packages, so they may shrink but never grow.
 *
 * New factories, collections and typed objects belong next to their feature
 * (`src/<Feature>/Factory/`, `src/<Feature>/Collection/`, ...).
 *
 * @see https://github.com/friendica/friendica/issues/15981
 * @see doc/en/developer/php-architecture.md
 */
class FrozenNamespaceTest extends TestCase
{
	/**
	 * Number of PHP files each frozen bucket had when it was frozen.
	 * Lower a number when you fold one of its files into a feature package - never raise it.
	 */
	private const FROZEN = [
		'src/Object'     => 64,
		'src/Factory'    => 30,
		'src/Collection' => 4,
	];

	/**
	 * @return array<array{string, int}>
	 */
	public static function frozenBucketProvider(): array
	{
		$cases = [];

		foreach (self::FROZEN as $path => $count) {
			$cases[] = [$path, $count];
		}

		return $cases;
	}

	#[DataProvider('frozenBucketProvider')]
	public function testFrozenBucketDoesNotGrow(string $path, int $frozenCount): void
	{
		$actualCount = count($this->phpFilesIn(dirname(__DIR__, 3) . '/' . $path));

		$this->assertLessThanOrEqual(
			$frozenCount,
			$actualCount,
			sprintf(
				'%s is frozen at %d files but has %d. Put new classes into the feature package they belong to '
				. '(src/<Feature>/Factory/, src/<Feature>/Collection/, ...) - see doc/en/developer/php-architecture.md.',
				$path,
				$frozenCount,
				$actualCount,
			),
		);

		$this->assertSame(
			$frozenCount,
			$actualCount,
			sprintf(
				'%s is down to %d files - lower its entry in %s::FROZEN to lock the progress in.',
				$path,
				$actualCount,
				self::class,
			),
		);
	}

	/**
	 * @return string[]
	 */
	private function phpFilesIn(string $directory): array
	{
		$files = [];

		/** @var \SplFileInfo $file */
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
			if ($file->isFile() && $file->getExtension() === 'php') {
				$files[] = $file->getPathname();
			}
		}

		return $files;
	}
}
