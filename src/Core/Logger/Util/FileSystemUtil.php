<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Util;

use Friendica\Core\Logger\Exception\LoggerUnusableException;

/**
 * interface for Util class for filesystem manipulation for Logger classes
 *
 * @internal
 */
interface FileSystemUtil
{
	/**
	 * Creates a directory based on a file, which gets accessed
	 *
	 * @param string $file The file
	 *
	 * @return string The directory name (empty if no directory is found, like urls)
	 *
	 * @throws LoggerUnusableException
	 */
	public function createDir(string $file): string;

	/**
	 * Creates a stream based on a URL (could be a local file or a real URL)
	 *
	 * @param string $url The file/url
	 *
	 * @return resource the open stream resource
	 *
	 * @throws LoggerUnusableException
	 */
	public function createStream(string $url);
}
