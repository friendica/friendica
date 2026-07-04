<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util;

class BasePath
{
	/**
	 * @param string $baseDir The default base path
	 * @param array  $server  server arguments
	 */
	public function __construct(private readonly string $baseDir, private readonly array $server = []) {}

	/**
	 * Returns the base Friendica filesystem path without trailing slash
	 *
	 * It first checks for the internal variable, then for DOCUMENT_ROOT and
	 * finally for PWD
	 *
	 * @return string
	 *
	 * @throws \Exception if directory isn't usable
	 */
	public function getPath()
	{
		$baseDir = $this->baseDir;
		$server  = $this->server;

		if ((!$baseDir || !is_dir($baseDir)) && !empty($server['DOCUMENT_ROOT'])) {
			$baseDir = $server['DOCUMENT_ROOT'];
		}

		if ((!$baseDir || !is_dir($baseDir)) && !empty($server['PWD'])) {
			$baseDir = $server['PWD'];
		}

		$baseDir = self::getRealPath($baseDir);

		if (!is_dir($baseDir)) {
			throw new \Exception(sprintf('\'%s\' is not a valid basepath', $baseDir));
		}

		return rtrim($baseDir, '/');
	}

	/**
	 * Returns a normalized file path
	 *
	 * This is a wrapper for the "realpath" function.
	 * That function cannot detect the real path when some folders aren't readable.
	 * Since this could happen with some hosters we need to handle this.
	 *
	 * @param string $path The path that is about to be normalized
	 * @return string normalized path - when possible
	 */
	public static function getRealPath($path)
	{
		$normalized = realpath($path);

		if (!is_bool($normalized)) {
			return $normalized;
		} else {
			return $path;
		}
	}
}
