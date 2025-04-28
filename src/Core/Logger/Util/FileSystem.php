<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Logger\Util;

use Friendica\Core\Logger\Exception\LoggerUnusableException;

/**
 * Util class for filesystem manipulation for Logger classes
 */
class FileSystem implements FileSystemUtil
{
	/**
	 * @var string a error message
	 */
	private $errorMessage;

	/**
	 * Creates a directory based on a file, which gets accessed
	 *
	 * @param string $file The file
	 *
	 * @return string The directory name (empty if no directory is found, like urls)
	 *
	 * @throws LoggerUnusableException
	 */
	public function createDir(string $file): string
	{
		$dirname = null;
		$pos     = strpos($file, '://');

		if (!$pos) {
			$dirname = realpath(dirname($file));
		}

		if (substr($file, 0, 7) === 'file://') {
			$dirname = realpath(dirname(substr($file, 7)));
		}

		if (isset($dirname) && !is_dir($dirname)) {
			set_error_handler([$this, 'customErrorHandler']);
			$status = mkdir($dirname, 0777, true);
			restore_error_handler();

			if (!$status && !is_dir($dirname)) {
				throw new LoggerUnusableException(sprintf('Directory "%s" cannot get created: ' . $this->errorMessage, $dirname));
			}

			return $dirname;
		} elseif (isset($dirname) && is_dir($dirname)) {
			return $dirname;
		} else {
			return '';
		}
	}

	/**
	 * Creates a stream based on a URL (could be a local file or a real URL)
	 *
	 * @param string $url The file/url
	 *
	 * @return resource the open stream resource
	 *
	 * @throws LoggerUnusableException
	 */
	public function createStream(string $url)
	{
		$directory = $this->createDir($url);
		set_error_handler([$this, 'customErrorHandler']);
		if (!empty($directory)) {
			$url = $directory . DIRECTORY_SEPARATOR . pathinfo($url, PATHINFO_BASENAME);
		}

		$stream = fopen($url, 'ab');
		restore_error_handler();

		if (!is_resource($stream)) {
			throw new LoggerUnusableException(sprintf('The stream or file "%s" could not be opened: ' . $this->errorMessage, $url));
		}

		return $stream;
	}

	private function customErrorHandler($code, $msg)
	{
		$this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);
	}
}
