<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Util;

/**
 * Util class for filesystem manipulation
 */
class FileSystem
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
	 */
	public function createDir(string $file)
	{
		$dirname = null;
		$pos = strpos($file, '://');

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
				throw new \UnexpectedValueException(sprintf('Directory "%s" cannot get created: ' . $this->errorMessage, $dirname));
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
	 * @return resource the open stream rssource
	 *
	 * @throws \UnexpectedValueException
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
			throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ' . $this->errorMessage, $url));
		}

		return $stream;
	}

	private function customErrorHandler($code, $msg)
	{
		$this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);
	}
}
