<?php
/**
 * SPDX-FileCopyrightText: 2010-2026 the Friendica project
 *
 * SPDX-License-Identifier: CC0-1.0
 */

declare(strict_types=1);

return \Rector\Config\RectorConfig::configure()
	->withPaths([
		__DIR__ . '/config',
		__DIR__ . '/mod',
		__DIR__ . '/src',
		__DIR__ . '/static',
		__DIR__ . '/tests',
		__DIR__ . '/view/php',
		__DIR__ . '/view/theme',
	])
	// Rector switches to an in-memory cache on CI, keep the file cache so CI can persist it.
	->withCache(
		cacheDirectory: sys_get_temp_dir() . '/rector_cached_files',
		cacheClass: \Rector\Caching\ValueObject\Storage\FileCacheStorage::class,
	)
	->withIndent("\t", 4)
	->withPhpVersion(80200)
	->withTypeCoverageLevel(6)
	// ->withDeadCodeLevel(0)
	// ->withCodeQualityLevel(0)
	->withPhpSets()
	->withSets([
		\Rector\PHPUnit\Set\PHPUnitSetList::COMPOSER_BASED,
		\Rector\PHPUnit\Set\PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
	])
;
